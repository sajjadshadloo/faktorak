<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Faktorak_Support_Modal {
    private const DONATION_CARD_NUMBER = '6219-8619-0909-7437';
    private const DONATION_CARD_RAW    = '6219861909097437';
    private const DONATION_BANK        = 'بلو بانک';
    private const DONATION_HOLDER      = 'سجـاد شــادلو ';

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
    }

    private function can_show() {
        return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
    }

    private function is_faktorak_screen( $hook ) {
        if ( false !== strpos( (string) $hook, 'shipping-invoice-settings' ) || false !== strpos( (string) $hook, 'faktorak-manual-invoices' ) ) {
            return true;
        }

        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        return in_array( $page, array( 'shipping-invoice-settings', 'faktorak-manual-invoices', 'faktorak-manual-invoices-list' ), true );
    }

    public function enqueue( $hook ) {
        if ( ! $this->can_show() || ! $this->is_faktorak_screen( $hook ) ) {
            return;
        }

        add_action( 'admin_head', array( $this, 'styles' ) );
    }

    public function styles() {
        self::print_styles();
    }

    public static function render_admin_tab() {
        self::print_support_tab();
    }

    private static function setting( $constant, $fallback = '' ) {
        return defined( $constant ) ? (string) constant( $constant ) : $fallback;
    }

    private static function clean_handle( $value ) {
        return ltrim( trim( (string) $value ), '@' );
    }

    private static function support_data() {
        $name     = self::setting( 'FAKTORAK_SUPPORT_NAME', 'سجاد شادلو' );
        $site     = self::setting( 'FAKTORAK_SUPPORT_SITE', 'https://sajjadshadloo.ir' );
        $phone    = trim( self::setting( 'FAKTORAK_SUPPORT_PHONE' ) );
        $telegram = self::clean_handle( self::setting( 'FAKTORAK_SUPPORT_TELEGRAM', 'sajjadshadloo' ) );
        $bale     = self::clean_handle( self::setting( 'FAKTORAK_SUPPORT_BALE', 'sajjadshadloo' ) );
        $whatsapp = preg_replace( '/\D+/', '', self::setting( 'FAKTORAK_SUPPORT_WHATSAPP' ) );

        return compact( 'name', 'site', 'phone', 'telegram', 'bale', 'whatsapp' );
    }

    private static function developer_image_url() {
        if ( defined( 'FAKTORAK_PLUGIN_URL' ) ) {
            return trailingslashit( FAKTORAK_PLUGIN_URL ) . 'assets/images/Developers.jpg';
        }

        return plugins_url( '../assets/images/Developers.jpg', __FILE__ );
    }

    private static function print_styles() {
        ?>
        <style>
        .faktorak-support-tab,.faktorak-support-tab *{font-family:'iranyekan',Tahoma,Arial,sans-serif!important;box-sizing:border-box}
        .faktorak-support-tab{display:grid;gap:18px;direction:rtl}
        .faktorak-support-card{background:#111827;color:#fff;border-radius:22px;padding:24px;box-shadow:0 18px 48px rgba(15,23,42,.16);position:relative;overflow:hidden}
        .faktorak-support-card:before{content:"";position:absolute;left:-80px;bottom:-100px;width:260px;height:260px;border-radius:999px;background:rgba(255,255,255,.06)}
        .faktorak-support-card-inner{position:relative;display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,.95fr);gap:24px;align-items:center}
        .faktorak-support-kicker{display:inline-flex;align-items:center;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.08);border-radius:999px;padding:6px 11px;color:rgba(255,255,255,.82);font-size:12px;font-weight:800;margin-bottom:12px}
        .faktorak-support-card h2{margin:0;color:#fff;font-size:20px;line-height:1.7}
        .faktorak-support-card p{margin:8px 0 0;color:rgba(255,255,255,.76);font-size:13px;line-height:2}
        .faktorak-support-person{display:flex;align-items:center;gap:13px;margin-top:16px}
        .faktorak-support-avatar{width:58px;height:58px;border-radius:18px;object-fit:cover;display:block;flex:0 0 auto;border:2px solid rgba(255,255,255,.24);box-shadow:0 12px 28px rgba(0,0,0,.18)}
        .faktorak-support-person strong{display:block;color:#fff;font-size:15px;margin-bottom:2px}
        .faktorak-support-person a{color:#93c5fd;text-decoration:none;font-size:12px}
        .faktorak-support-links{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
        .faktorak-support-link{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 12px;border:1px solid rgba(255,255,255,.14);border-radius:14px;background:rgba(255,255,255,.08);color:#fff;text-decoration:none;font-size:12px;font-weight:800;transition:background .15s ease,transform .15s ease}
        .faktorak-support-link:hover{background:rgba(255,255,255,.14);color:#fff;transform:translateY(-1px)}
        .faktorak-support-link.is-muted{color:rgba(255,255,255,.48);cursor:not-allowed}
        .faktorak-support-link-main{display:flex;align-items:center;gap:9px;min-width:0}
        .faktorak-support-icon{width:28px;height:28px;border-radius:10px;background:#fff;display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto}
        .faktorak-support-icon svg{width:18px;height:18px;display:block}
        .faktorak-support-link small{font-weight:500;color:rgba(255,255,255,.68);direction:ltr;text-align:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .faktorak-support-note{margin-top:12px;color:rgba(255,255,255,.68);font-size:12px;line-height:1.9}
        .faktorak-support-panel{background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:22px;box-shadow:0 16px 42px rgba(15,23,42,.06)}
        .faktorak-donation-wrap{max-width:920px;margin:0 auto}
        .faktorak-donation-header{text-align:center;margin-bottom:22px}
        .faktorak-donation-heart{display:inline-flex;align-items:center;justify-content:center;width:60px;height:60px;background:#ffe4e6;border-radius:50%;margin-bottom:12px}
        .faktorak-donation-header h3{font-size:19px;font-weight:900;color:#111827;margin:0 0 8px}
        .faktorak-donation-header p{font-size:13px;color:#64748b;max-width:650px;margin:0 auto;line-height:2}
        .faktorak-donation-container{display:flex;gap:26px;margin-top:22px;flex-wrap:wrap;justify-content:center;align-items:center}
        .faktorak-bank-card{background:linear-gradient(135deg,#0284c7,#0369a1);color:#fff;border-radius:20px;padding:24px;width:330px;height:200px;position:relative;box-shadow:0 18px 44px rgba(3,105,161,.28);overflow:hidden;display:flex;flex-direction:column;justify-content:space-between}
        .faktorak-bank-card:before{content:"";position:absolute;top:-42px;right:-42px;width:160px;height:160px;border-radius:999px;background:rgba(255,255,255,.08)}
        .faktorak-bank-top{display:flex;align-items:flex-start;justify-content:space-between;position:relative}
        .faktorak-bank-name{font-size:14px;font-weight:900;color:rgba(255,255,255,.94)}
        .faktorak-card-chip{width:40px;height:30px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:7px;margin-top:12px}
        .faktorak-card-number{font-family:'Courier New',monospace!important;font-size:20px;font-weight:900;letter-spacing:2.5px;text-align:center;direction:ltr;text-shadow:1px 1px 2px rgba(0,0,0,.2)}
        .faktorak-card-bottom{display:flex;justify-content:space-between;align-items:flex-end;font-size:12px;position:relative}
        .faktorak-card-label{color:rgba(255,255,255,.62);font-size:10px;margin-bottom:3px}
        .faktorak-card-holder{font-weight:900;font-size:13px}
        .faktorak-donation-details{flex:1;display:grid;gap:12px;min-width:300px;max-width:430px}
        .faktorak-donation-info{background:#f8fafc;border:2px solid #e2e8f0;border-radius:14px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;gap:14px}
        .faktorak-donation-label{display:block;font-size:11px;font-weight:900;color:#64748b;margin-bottom:5px}
        .faktorak-donation-value{font-family:'Courier New',monospace!important;font-size:13px;font-weight:900;color:#111827;direction:ltr}
        .faktorak-copy-donation{border:0;border-radius:12px;background:#0ea5e9;color:#fff;padding:10px 15px;font-size:13px;font-weight:900;cursor:pointer;white-space:nowrap;box-shadow:0 12px 26px rgba(14,165,233,.2);transition:transform .16s ease,background .16s ease}
        .faktorak-copy-donation:hover{background:#0284c7;color:#fff;transform:translateY(-1px)}
        @media(max-width:860px){.faktorak-support-card-inner{grid-template-columns:1fr}.faktorak-donation-container{align-items:stretch}.faktorak-bank-card,.faktorak-donation-details{max-width:none;width:100%}}
        @media(max-width:560px){.faktorak-support-links{grid-template-columns:1fr}.faktorak-donation-info{display:block}.faktorak-copy-donation{margin-top:10px;width:100%}}
        </style>
        <?php
    }

    private static function print_support_tab() {
        $data = self::support_data();
        ?>
        <div class="faktorak-support-tab">
            <section class="faktorak-support-card">
                <div class="faktorak-support-card-inner">
                    <div>
                        <span class="faktorak-support-kicker">پشتیبانی و پیشنهاد توسعه</span>
                        <h2>ارتباط با توسعه‌دهنده فاکتورک</h2>
                        <p>برای گزارش مشکل، پیشنهاد قابلیت جدید یا توسعه اختصاصی افزونه‌های ووکامرس از مسیرهای ارتباطی روبه‌رو پیام بدهید.</p>

                        <div class="faktorak-support-person">
                            <img class="faktorak-support-avatar" src="<?php echo esc_url( self::developer_image_url() ); ?>" alt="<?php echo esc_attr( $data['name'] ); ?>">
                            <div>
                                <strong><?php echo esc_html( $data['name'] ); ?></strong>
                                <a href="<?php echo esc_url( $data['site'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( preg_replace( '#^https?://#', '', untrailingslashit( $data['site'] ) ) ); ?></a>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="faktorak-support-links">
                            <?php self::contact_link( 'وب‌سایت', preg_replace( '#^https?://#', '', untrailingslashit( $data['site'] ) ), $data['site'], 'site' ); ?>
                            <?php self::contact_link( 'تلگرام', $data['telegram'] ? '@' . $data['telegram'] : '', $data['telegram'] ? 'https://t.me/' . $data['telegram'] : '', 'telegram' ); ?>
                            <?php self::contact_link( 'بله', $data['bale'] ? '@' . $data['bale'] : '', $data['bale'] ? 'https://ble.ir/' . $data['bale'] : '', 'bale' ); ?>
                            <?php self::contact_link( 'واتساپ', $data['whatsapp'] ? '+' . $data['whatsapp'] : '', $data['whatsapp'] ? 'https://wa.me/' . $data['whatsapp'] : '', 'whatsapp' ); ?>
                        </div>
                        <div class="faktorak-support-note">برای پاسخ سریع‌تر، نام سایت، نسخه فاکتورک و توضیح کوتاه خطا یا درخواست را همراه پیام ارسال کنید.</div>
                    </div>
                </div>
            </section>

            <section class="faktorak-support-panel">
                <?php self::print_donation_block(); ?>
            </section>
        </div>

        <script>
        (function(){
            document.querySelectorAll('[data-faktorak-copy]').forEach(function(button){
                button.addEventListener('click', function(){
                    const value = button.getAttribute('data-faktorak-copy') || '';
                    const original = button.textContent;
                    function done(){
                        button.textContent = 'کپی شد';
                        setTimeout(function(){ button.textContent = original; }, 1600);
                    }
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(value).then(done);
                    } else {
                        const input = document.createElement('input');
                        input.value = value;
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        document.body.removeChild(input);
                        done();
                    }
                });
            });
        })();
        </script>
        <?php
    }

    private static function print_donation_block() {
        ?>
        <div class="faktorak-donation-wrap">
            <div class="faktorak-donation-header">
                <div class="faktorak-donation-heart" aria-hidden="true">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="#f43f5e" xmlns="http://www.w3.org/2000/svg"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                </div>
                <h3>حمایت مالی از توسعه‌دهنده</h3>
                <p>اگر فاکتورک برای شما مفید بوده، حمایت مالی شما به توسعه امکانات بیشتر و تداوم به‌روزرسانی‌ها کمک می‌کند.</p>
            </div>

            <div class="faktorak-donation-container">
                <div class="faktorak-bank-card">
                    <div>
                        <div class="faktorak-bank-top">
                            <span class="faktorak-bank-name"><?php echo esc_html( self::DONATION_BANK ); ?></span>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="rgba(255,255,255,.72)" stroke-width="1.5"/><path d="M12 6v12M6 12h12" stroke="rgba(255,255,255,.72)" stroke-width="1.5" stroke-linecap="round"/></svg>
                        </div>
                        <div class="faktorak-card-chip"></div>
                    </div>
                    <div class="faktorak-card-number"><?php echo esc_html( str_replace( '-', ' ', self::DONATION_CARD_NUMBER ) ); ?></div>
                    <div class="faktorak-card-bottom">
                        <div>
                            <div class="faktorak-card-label">نام دارنده کارت</div>
                            <div class="faktorak-card-holder"><?php echo esc_html( self::DONATION_HOLDER ); ?></div>
                        </div>
                        <div>
                            <div class="faktorak-card-label">اعتبار</div>
                            <div style="font-family:monospace!important;font-weight:900">** / **</div>
                        </div>
                    </div>
                </div>

                <div class="faktorak-donation-details">
                    <div class="faktorak-donation-info">
                        <div>
                            <span class="faktorak-donation-label">شماره کارت</span>
                            <span class="faktorak-donation-value"><?php echo esc_html( self::DONATION_CARD_NUMBER ); ?></span>
                        </div>
                        <button type="button" class="faktorak-copy-donation" data-faktorak-copy="<?php echo esc_attr( self::DONATION_CARD_RAW ); ?>">کپی شماره کارت</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function contact_link( $label, $value, $url, $icon ) {
        $content = '<span class="faktorak-support-link-main"><span class="faktorak-support-icon">' . self::contact_icon( $icon ) . '</span><span>' . esc_html( $label ) . '</span></span>';

        if ( '' === $value || '' === $url ) {
            echo '<span class="faktorak-support-link is-muted">' . $content . '<small>تنظیم نشده</small></span>';
            return;
        }

        echo '<a class="faktorak-support-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . $content . '<small>' . esc_html( $value ) . '</small></a>';
    }

    private static function contact_icon( $type ) {
        $icons = array(
            'site'     => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="#2563eb" stroke-width="1.7"/><path d="M3.6 9h16.8M3.6 15h16.8M12 3c2.1 2.4 3.2 5.4 3.2 9S14.1 18.6 12 21c-2.1-2.4-3.2-5.4-3.2-9S9.9 5.4 12 3Z" stroke="#2563eb" stroke-width="1.7" stroke-linecap="round"/></svg>',
            'phone'    => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13.5 2C13.5 2 15.8335 2.21213 18.8033 5.18198C21.7731 8.15183 21.9853 10.4853 21.9853 10.4853" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"/><path d="M14.207 5.53564C14.207 5.53564 15.197 5.81849 16.6819 7.30341C18.1668 8.78834 18.4497 9.77829 18.4497 9.77829" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"/><path d="M15.1007 15.0272L14.5569 14.5107L15.1007 15.0272ZM15.5562 14.5477L16.1 15.0642H16.1L15.5562 14.5477ZM17.9728 14.2123L17.5987 14.8623H17.5987L17.9728 14.2123ZM19.8833 15.312L19.5092 15.962L19.8833 15.312ZM20.4217 18.7584L20.9655 19.2749L20.4217 18.7584ZM19.0011 20.254L18.4573 19.7375L19.0011 20.254ZM17.6763 20.9631L17.7499 21.7095L17.6763 20.9631ZM7.81536 16.4752L8.35915 15.9587L7.81536 16.4752ZM3.00289 6.96594L2.25397 7.00613L2.25397 7.00613L3.00289 6.96594ZM9.47752 8.50311L10.0213 9.01963H10.0213L9.47752 8.50311ZM9.63424 5.6931L10.2466 5.26012L9.63424 5.6931ZM8.37326 3.90961L7.76086 4.3426V4.3426L8.37326 3.90961ZM5.26145 3.60864L5.80524 4.12516L5.26145 3.60864ZM3.69185 5.26114L3.14806 4.74462L3.14806 4.74462L3.69185 5.26114ZM11.0631 13.0559L11.6069 12.5394L11.0631 13.0559ZM15.6445 15.5437L16.1 15.0642L15.0124 14.0312L14.5569 14.5107L15.6445 15.5437ZM17.5987 14.8623L19.5092 15.962L20.2575 14.662L18.347 13.5623L17.5987 14.8623ZM19.8779 18.2419L18.4573 19.7375L19.5449 20.7705L20.9655 19.2749L19.8779 18.2419ZM17.6026 20.2167C16.1676 20.3584 12.4233 20.2375 8.35915 15.9587L7.27157 16.9917C11.7009 21.655 15.9261 21.8895 17.7499 21.7095L17.6026 20.2167ZM8.35915 15.9587C4.48303 11.8778 3.83285 8.43556 3.75181 6.92574L2.25397 7.00613C2.35322 8.85536 3.1384 12.6403 7.27157 16.9917L8.35915 15.9587ZM9.7345 9.32159L10.0213 9.01963L8.93372 7.9866L8.64691 8.28856L9.7345 9.32159ZM10.2466 5.26012L8.98565 3.47663L7.76086 4.3426L9.02185 6.12608L10.2466 5.26012ZM4.71766 3.09213L3.14806 4.74462L4.23564 5.77765L5.80524 4.12516L4.71766 3.09213ZM9.1907 8.80507C8.64691 8.28856 8.64622 8.28929 8.64552 8.29002C8.64528 8.29028 8.64458 8.29102 8.64411 8.29152C8.64316 8.29254 8.64219 8.29357 8.64121 8.29463C8.63924 8.29675 8.6372 8.29896 8.6351 8.30127C8.63091 8.30588 8.62646 8.31087 8.62178 8.31625C8.61243 8.32701 8.60215 8.33931 8.59116 8.3532C8.56918 8.38098 8.54431 8.41512 8.51822 8.45588C8.46591 8.53764 8.40917 8.64531 8.36112 8.78033C8.26342 9.0549 8.21018 9.4185 8.27671 9.87257C8.40742 10.7647 8.99198 11.9644 10.5193 13.5724L11.6069 12.5394C10.1793 11.0363 9.82761 10.1106 9.76086 9.65511C9.72866 9.43536 9.76138 9.31957 9.77432 9.28321C9.78159 9.26277 9.78635 9.25709 9.78169 9.26437C9.77944 9.26789 9.77494 9.27451 9.76738 9.28407C9.76359 9.28885 9.75904 9.29437 9.7536 9.30063C9.75088 9.30375 9.74793 9.30706 9.74476 9.31056C9.74317 9.31231 9.74152 9.3141 9.73981 9.31594C9.73896 9.31686 9.73809 9.31779 9.7372 9.31873C9.73676 9.3192 9.73608 9.31992 9.73586 9.32015C9.73518 9.32087 9.7345 9.32159 9.1907 8.80507ZM10.5193 13.5724C12.0422 15.1757 13.1923 15.806 14.0698 15.9485C14.5201 16.0216 14.8846 15.9632 15.1606 15.8544C15.2955 15.8012 15.4022 15.7387 15.4823 15.6819C15.5223 15.6535 15.5556 15.6266 15.5824 15.6031C15.5959 15.5913 15.6077 15.5803 15.618 15.5703C15.6232 15.5654 15.628 15.5606 15.6324 15.5562C15.6346 15.554 15.6367 15.5518 15.6387 15.5497C15.6397 15.5487 15.6407 15.5477 15.6417 15.5467C15.6422 15.5462 15.6429 15.5454 15.6431 15.5452C15.6438 15.5444 15.6445 15.5437 15.1007 15.0272C14.5569 14.5107 14.5576 14.51 14.5583 14.5093C14.5585 14.509 14.5592 14.5083 14.5596 14.5078C14.5605 14.5069 14.5614 14.506 14.5623 14.5051C14.5641 14.5033 14.5658 14.5015 14.5674 14.4998C14.5708 14.4965 14.574 14.4933 14.577 14.4904C14.583 14.4846 14.5885 14.4796 14.5933 14.4754C14.6028 14.467 14.6099 14.4616 14.6145 14.4584C14.6239 14.4517 14.6229 14.454 14.6102 14.459C14.5909 14.4666 14.5 14.4987 14.3103 14.4679C13.9077 14.4025 13.0391 14.0472 11.6069 12.5394L10.5193 13.5724ZM8.98565 3.47663C7.97206 2.04305 5.94384 1.80119 4.71766 3.09213L5.80524 4.12516C6.32808 3.57471 7.24851 3.61795 7.76086 4.3426L8.98565 3.47663ZM3.75181 6.92574C3.73038 6.52644 3.90425 6.12654 4.23564 5.77765L3.14806 4.74462C2.61221 5.30877 2.20493 6.09246 2.25397 7.00613L3.75181 6.92574ZM18.4573 19.7375C18.1783 20.0313 17.8864 20.1887 17.6026 20.2167L17.7499 21.7095C18.497 21.6357 19.1016 21.2373 19.5449 20.7705L18.4573 19.7375ZM10.0213 9.01963C10.9889 8.00095 11.0574 6.40678 10.2466 5.26012L9.02185 6.12608C9.44399 6.72315 9.37926 7.51753 8.93372 7.9866L10.0213 9.01963ZM19.5092 15.962C20.33 16.4345 20.4907 17.5968 19.8779 18.2419L20.9655 19.2749C22.2704 17.901 21.8904 15.6019 20.2575 14.662L19.5092 15.962ZM16.1 15.0642C16.4854 14.6584 17.086 14.5672 17.5987 14.8623L18.347 13.5623C17.2485 12.93 15.8861 13.1113 15.0124 14.0312L16.1 15.0642Z" fill="#1C274C"/></svg>',
            'telegram' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21.7 3.4 18.3 20c-.25 1.16-.92 1.44-1.86.9l-5.15-3.8-2.49 2.4c-.27.27-.5.5-1.03.5l.37-5.25 9.55-8.63c.42-.37-.09-.58-.64-.21L5.25 13.34.17 11.75c-1.1-.34-1.12-1.1.23-1.63L20.26 2.47c.92-.34 1.72.22 1.44.93Z" fill="#2AABEE"/></svg>',
            'bale'     => '<svg viewBox="0 0 24 24" fill="#48D0AB" aria-hidden="true"><path d="M11.425 23.987a12.218 12.218 0 0 1-2.95-.514 6.578 6.578 0 0 0-.336-.116C4.936 22.303 2.22 19.763.913 16.599a11.92 11.92 0 0 1-.9-4.063C.005 12.377.001 10.246 0 6.74 0 .71-.005 1.137.07.903.23.394.673.05 1.224.005c.421-.034.7.088 1.603.699.562.38 1.119.78 1.796 1.289.315.237.353.261.376.247l.35-.23c.58-.381 1.11-.677 1.7-.945A11.913 11.913 0 0 1 9.766.21a11.19 11.19 0 0 1 2.041-.2c1.14-.016 2.077.091 3.152.36 3.55.888 6.538 3.411 8.028 6.78.492 1.113.845 2.43.945 3.522.033.366.039.43.053.611.008.105.015.406.015.669 0 .783-.065 1.57-.169 2.064a5.474 5.474 0 0 0-.046.26c-.056.378-.214.987-.399 1.535-.205.613-.367.999-.684 1.633a11.95 11.95 0 0 1-2.623 3.436c-.44.396-.829.705-1.26 1.003-.647.445-1.307.812-2.039 1.134-.6.265-1.44.539-2.101.686a11.165 11.165 0 0 1-1.178.202 12.28 12.28 0 0 1-2.076.082zm-.61-5.92c.294-.06.678-.209.864-.337.144-.099.428-.376 2.064-2.013a161.8 161.8 0 0 1 1.764-1.753c.017 0 1.687-1.67 1.687-1.689 0-.02 1.64-1.648 1.661-1.648.01 0 .063-.047.118-.106.467-.495.682-.957.716-1.547.026-.433-.06-.909-.217-1.196a2.552 2.552 0 0 0-.983-1.024c-.281-.163-.512-.233-.888-.27-.306-.031-.688 0-.948.075-.243.07-.603.274-.853.481-.042.035-1.279 1.265-2.748 2.733l-2.671 2.67-1.093-1.09c-.6-.6-1.12-1.114-1.155-1.142a2.419 2.419 0 0 0-1.338-.51c-.404-.013-.91.09-1.224.25a2.89 2.89 0 0 0-.659.526c-.108.12-.287.357-.29.385-.003.03-.009.044-.065.16a2.312 2.312 0 0 0-.224.91c-.011.229-.01.265.019.491.045.353.24.781.51 1.115.05.063.97.992 2.044 2.064 1.507 1.505 1.98 1.97 2.074 2.039.327.24.683.388 1.101.456.182.03.5.016.734-.03z"/></svg>',
            'whatsapp' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.5 11.8a8.45 8.45 0 0 1-12.55 7.38L3 20.5l1.32-4.78A8.45 8.45 0 1 1 20.5 11.8Z" fill="#25D366"/><path d="M8.43 7.55c.18-.4.37-.41.54-.42h.46c.14 0 .36-.05.55.42.18.46.63 1.6.69 1.72.05.12.09.26.02.42-.07.15-.11.25-.22.38-.11.13-.24.29-.34.39-.11.11-.23.23-.1.46.13.23.58.96 1.24 1.55.85.76 1.57 1 1.8 1.12.23.12.37.1.5-.06.15-.18.58-.68.74-.92.15-.23.31-.19.52-.11.22.08 1.39.66 1.63.78.24.12.4.18.46.28.06.1.06.6-.14 1.18-.2.58-1.16 1.1-1.62 1.14-.42.04-.95.06-1.53-.1-.35-.1-.8-.26-1.38-.51-2.43-1.05-4.02-3.5-4.14-3.66-.12-.15-.99-1.32-.99-2.52 0-1.2.63-1.79.85-2.04Z" fill="#fff"/></svg>',
        );

        return isset( $icons[ $type ] ) ? $icons[ $type ] : $icons['site'];
    }
}

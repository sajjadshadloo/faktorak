<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ShippingInvoiceSettings {
    private $settings;
    private $default_settings;

    public function __construct() {
        $this->default_settings = array(
            'logo_url'                => '',
            'sender_name'             => get_bloginfo( 'name' ),
            'sender_address'          => '',
            'sender_postcode'         => '',
            'sender_phone'            => '',
            'sender_email'            => get_option( 'admin_email' ),
            'sender_url'              => home_url(),
            // پیش‌فرض جدید: قالب کلاسیک (سازگار با قدیمی‌ها)
            'invoice_template'        => 'classic',
            'signature_url'           => '',
            'enable_signature'        => 'no',
            'show_user_buttons'       => 'no',
            'enable_proforma_invoice' => 'no',
            'enable_checkout_map'     => 'no',
        );

        $this->settings = get_option( 'shipping_invoice_settings', $this->default_settings );
        if ( false === get_option( 'shipping_invoice_settings' ) ) {
            update_option( 'shipping_invoice_settings', $this->default_settings );
        }
    }

    /* Settings API */
    public function register_settings() {
        register_setting( 'shipping_invoice_options', 'shipping_invoice_settings', array( $this, 'sanitize_settings' ) );

        add_settings_section('shipping_invoice_section', '', '__return_false', 'shipping-invoice-settings');

        // ثبت فیلدها (رندر UI سفارشی در settings_page)
        add_settings_field( 'logo_url', '', array( $this, 'logo_url_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_name', '', array( $this, 'sender_name_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_address', '', array( $this, 'sender_address_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_postcode', '', array( $this, 'sender_postcode_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_phone', '', array( $this, 'sender_phone_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_email', '', array( $this, 'sender_email_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_url', '', array( $this, 'sender_url_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'invoice_template', '', array( $this, 'invoice_template_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'signature_url', '', array( $this, 'signature_url_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'enable_signature', '', array( $this, 'enable_signature_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'show_user_buttons', '', array( $this, 'show_user_buttons_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'enable_proforma_invoice', '', array( $this, 'enable_proforma_invoice_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'enable_checkout_map', '', array( $this, 'enable_checkout_map_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
    }

    public function sanitize_settings( $input ) {
        $s = array();
        $s['logo_url']                = esc_url_raw( $input['logo_url'] ?? '' );
        $s['sender_name']             = sanitize_text_field( $input['sender_name'] ?? '' );
        $s['sender_address']          = sanitize_textarea_field( $input['sender_address'] ?? '' );
        $s['sender_postcode']         = sanitize_text_field( $input['sender_postcode'] ?? '' );
        $s['sender_phone']            = sanitize_text_field( $input['sender_phone'] ?? '' );
        $s['sender_email']            = sanitize_email( $input['sender_email'] ?? '' );
        $s['sender_url']              = esc_url_raw( $input['sender_url'] ?? '' );

        // ✅ فقط مقادیر مجاز + نگاشت template-1 -> classic (سازگاری عقب‌رو)
        $allowed_templates = array('modern', 'classic', 'template-1');
        $selected_template = sanitize_text_field( $input['invoice_template'] ?? 'classic' );
        if ( ! in_array( $selected_template, $allowed_templates, true ) ) {
            $selected_template = 'classic';
        }
        if ( $selected_template === 'template-1' ) {
            $selected_template = 'classic';
        }
        $s['invoice_template'] = $selected_template;

        $s['signature_url']           = esc_url_raw( $input['signature_url'] ?? '' );
        $s['enable_signature']        = isset( $input['enable_signature'] ) ? 'yes' : 'no';
        $s['show_user_buttons']       = isset( $input['show_user_buttons'] ) ? 'yes' : 'no';
        $s['enable_proforma_invoice'] = isset( $input['enable_proforma_invoice'] ) ? 'yes' : 'no';
        $s['enable_checkout_map']     = isset( $input['enable_checkout_map'] ) ? 'yes' : 'no';
        return $s;
    }

    /* Settings Page (UI) */
    public function settings_page() { ?>
        <div class="wrap faktorak-scope">
            <h1>تنظیمات فاکتورک</h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'shipping_invoice_options' ); ?>

                <!-- کارت: تنظیمات فروشگاه و چاپ (max-width برای جلوگیری از اسکرول) -->
                <div class="fak-card" style="max-width:980px">
                    <h2 style="margin-top:0">تنظیمات فروشگاه و چاپ</h2>
                    <div class="fak-row">
                        <label>لوگوی فروشگاه<br><?php $this->logo_url_html(); ?></label>
                        <label>نام فرستنده<br><?php $this->sender_name_html(); ?></label>
                        <label>آدرس فرستنده<br><?php $this->sender_address_html(); ?></label>
                        <label>کدپستی فرستنده<br><?php $this->sender_postcode_html(); ?></label>
                        <label>تلفن فرستنده<br><?php $this->sender_phone_html(); ?></label>
                        <label>ایمیل فرستنده<br><?php $this->sender_email_html(); ?></label>
                        <label>وب‌سایت فرستنده<br><?php $this->sender_url_html(); ?></label>
                        <label>قالب فاکتور<br><?php $this->invoice_template_html(); ?></label>
                        <label>تصویر امضا/مهر<br><?php $this->signature_url_html(); ?></label>
                    </div>
                </div>

                <!-- کارت: سوییچرها (مثل قبل) -->
                <div class="fak-card" style="max-width:980px">
                    <h2 style="margin-top:0">فعال‌سازی قابلیت‌ها</h2>
                    <div class="fak-row">
                        <?php $this->enable_signature_html(); ?>
                        <?php $this->show_user_buttons_html(); ?>
                        <?php $this->enable_proforma_invoice_html(); ?>
                        <?php $this->enable_checkout_map_html(); ?>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>

            <hr>

            <!-- کارت: شورت‌کدهای ساده + کپی -->
            <div class="fak-card" style="max-width:980px">
                <h2 style="margin-top:0">شورت‌کدهای آماده</h2>
                <p style="color:#555;margin:0 0 16px">همین دو شورت‌کد را کپی کنید </p>

                <div class="fak-row">
                    <!-- فاکتور -->
                    <div class="fak-card">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                            <div>
                                <div style="font-weight:700;margin-bottom:6px">دکمهٔ فاکتور</div>
                                <div style="color:#6b7280;font-size:12px">در صفحهٔ مشاهده سفارش، بدون ID هم کار می‌کند.</div>
                            </div>
                            <button type="button" class="button fak-copy-btn" data-target="#fak-sc-invoice" style="width:auto !important">کپی</button>
                        </div>
                        <input id="fak-sc-invoice" type="text" readonly class="fak-input" value='[faktorak_invoice_button]' />
                    </div>

                    <!-- پیش فاکتور -->
                    <div class="fak-card">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                            <div>
                                <div style="font-weight:700;margin-bottom:6px">دکمهٔ پیش‌فاکتور</div>
                                <div style="color:#6b7280;font-size:12px">از سبد فعلی کاربر یک سفارش «پیش‌فاکتور» می‌سازد.</div>
                            </div>
                            <button type="button" class="button fak-copy-btn" data-target="#fak-sc-proforma" style="width:auto !important">کپی</button>
                        </div>
                        <input id="fak-sc-proforma" type="text" readonly class="fak-input" value='[faktorak_proforma_button]' />
                    </div>
                </div>
            </div>

            <script>
            (function(){
                document.addEventListener('click', function(e){
                    const btn = e.target.closest('.fak-copy-btn');
                    if(!btn) return;
                    const sel = btn.getAttribute('data-target');
                    const input = document.querySelector(sel);
                    if(!input) return;
                    input.select();
                    input.setSelectionRange(0, 99999);
                    try {
                        const ok = document.execCommand('copy');
                        btn.textContent = ok ? 'کپی شد!' : 'کپی نشد';
                    } catch(err) { btn.textContent = 'کپی نشد'; }
                    setTimeout(() => { btn.textContent = 'کپی'; }, 1200);
                }, false);
            })();
            </script>
        </div>
    <?php }

    /* Fields */
    public function logo_url_html() {
        $logo_url = $this->get_setting('logo_url'); ?>
        <input type="text" name="shipping_invoice_settings[logo_url]" id="logo_url" value="<?php echo esc_attr( $logo_url ); ?>" class="regular-text" />
        <input type="button" class="button" value="آپلود لوگو" id="upload_logo_button" />
        <p class="description">لینک لوگو را وارد کنید یا از دکمه آپلود استفاده کنید.</p>
        <?php if ( ! empty( $logo_url ) ) : ?>
            <p><img src="<?php echo esc_url( $logo_url ); ?>" alt="لوگو" style="max-width:150px;height:auto"></p>
        <?php endif; ?>
        <script>
        jQuery(function($){
            let mediaUploader;
            $('#upload_logo_button').on('click', function(e){
                e.preventDefault();
                if (mediaUploader){ mediaUploader.open(); return; }
                mediaUploader = wp.media({ title:'انتخاب لوگو', button:{ text:'انتخاب' }, multiple:false });
                mediaUploader.on('select', function(){
                    const a = mediaUploader.state().get('selection').first().toJSON();
                    $('#logo_url').val(a.url);
                });
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }

    public function sender_name_html()     { echo '<input type="text"  name="shipping_invoice_settings[sender_name]"    value="'.esc_attr($this->get_setting('sender_name')).'" class="regular-text" />'; }
    public function sender_address_html()  { echo '<textarea rows="3"   name="shipping_invoice_settings[sender_address]" class="large-text">'.esc_textarea($this->get_setting('sender_address')).'</textarea>'; }
    public function sender_postcode_html() { echo '<input type="text"  name="shipping_invoice_settings[sender_postcode]" value="'.esc_attr($this->get_setting('sender_postcode')).'" class="regular-text" />'; }
    public function sender_phone_html()    { echo '<input type="text"  name="shipping_invoice_settings[sender_phone]"   value="'.esc_attr($this->get_setting('sender_phone')).'" class="regular-text" />'; }
    public function sender_email_html()    { echo '<input type="email" name="shipping_invoice_settings[sender_email]"   value="'.esc_attr($this->get_setting('sender_email')).'" class="regular-text" />'; }
    public function sender_url_html()      { echo '<input type="url"   name="shipping_invoice_settings[sender_url]"     value="'.esc_attr($this->get_setting('sender_url')).'" class="regular-text" />'; }

    public function invoice_template_html() {
        // نگه‌داشت سازگاری با مقدار قدیمی template-1
        $current = $this->get_setting('invoice_template');
        if ($current === 'template-1') {
            $current = 'classic';
        }
        ?>
        <select name="shipping_invoice_settings[invoice_template]">
            <option value="modern" <?php selected($current, 'modern'); ?>>قالب مدرن (پیشنهادی)</option>
            <option value="classic" <?php selected($current, 'classic'); ?>>قالب کلاسیک</option>
        </select>
        <p class="description">قالب «مدرن» نسخه جدید با UI تمیز و پرینت بهینه است. «کلاسیک» همان قالب قبلی افزونه است.</p>
        <?php
    }

    public function signature_url_html() {
        $signature_url = $this->get_setting('signature_url'); ?>
        <input type="text" name="shipping_invoice_settings[signature_url]" id="signature_url" value="<?php echo esc_attr( $signature_url ); ?>" class="regular-text" />
        <input type="button" class="button" value="آپلود امضا" id="upload_signature_button" />
        <p class="description">لینک تصویر امضا/مهر را وارد کنید یا از دکمه آپلود استفاده کنید.</p>
        <?php if ( ! empty( $signature_url ) ) : ?>
            <p><img src="<?php echo esc_url( $signature_url ); ?>" alt="امضا" style="max-width:150px;height:auto"></p>
        <?php endif; ?>
        <script>
        jQuery(function($){
            let mediaUploader;
            $('#upload_signature_button').on('click', function(e){
                e.preventDefault();
                if (mediaUploader){ mediaUploader.open(); return; }
                mediaUploader = wp.media({ title:'انتخاب امضا', button:{ text:'انتخاب' }, multiple:false });
                mediaUploader.on('select', function(){
                    const a = mediaUploader.state().get('selection').first().toJSON();
                    $('#signature_url').val(a.url);
                });
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }

    /* سوییچرها */
    public function enable_signature_html()        { $this->render_switch('enable_signature',        'نمایش امضا در فاکتور'); }
    public function show_user_buttons_html()       { $this->render_switch('show_user_buttons',       'نمایش دکمه فاکتور در حساب کاربری مشتری'); }
    public function enable_proforma_invoice_html() { $this->render_switch('enable_proforma_invoice', 'نمایش خودکار دکمه «پیش‌فاکتور» در سبد خرید'); }
    public function enable_checkout_map_html()     { $this->render_switch('enable_checkout_map',     'نمایش نقشه در صفحه تسویه‌حساب'); }

    private function render_switch($key, $label){
        $checked = $this->get_setting($key) === 'yes' ? 'checked' : '';
        $id = 'fak_'.$key;
        echo '<label class="fak-switch" for="'.$id.'">
                <input id="'.$id.'" type="checkbox" name="shipping_invoice_settings['.$key.']" value="yes" '.$checked.' />
                <span class="track"><span class="thumb"></span></span>
                <span class="label">'.$label.'</span>
              </label>';
    }

    /* Helpers */
    public function get_setting( $key ) {
        if ( !isset($this->default_settings[$key]) ) return null;
        return isset( $this->settings[$key] ) ? $this->settings[$key] : $this->default_settings[$key];
    }
}

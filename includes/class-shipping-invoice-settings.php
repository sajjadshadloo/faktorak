<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Faktorak_Shipping_Invoice_Settings {
    private const OPTION_NAME        = 'faktorak_shipping_invoice_settings';
    private const LEGACY_OPTION_NAME = 'shipping_invoice_settings';

    private const OPTION_GROUP        = 'faktorak_shipping_invoice_options';
    private const LEGACY_OPTION_GROUP = 'shipping_invoice_options';

    private const SETTINGS_SECTION = 'faktorak_shipping_invoice_section';

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
            'admin_note'              => '',
            // پیش‌فرض جدید: قالب کلاسیک (سازگار با قدیمی‌ها)
            'invoice_template'        => 'classic',
            'signature_url'           => '',
            'enable_signature'        => 'no',
            'show_user_buttons'       => 'no',
            'enable_proforma_invoice' => 'no',
            'enable_checkout_map'     => 'no',
        );

        $this->maybe_migrate_legacy_option();

        $this->settings = get_option( self::OPTION_NAME, $this->default_settings );
        if ( false === get_option( self::OPTION_NAME ) ) {
            update_option( self::OPTION_NAME, $this->default_settings );
        }
    }

    private function maybe_migrate_legacy_option() {
        $legacy_value = get_option( self::LEGACY_OPTION_NAME );
        if ( false === $legacy_value ) {
            return;
        }

        if ( false !== get_option( self::OPTION_NAME ) ) {
            return;
        }

        update_option( self::OPTION_NAME, $legacy_value );
    }

    /* Settings API */
    public function register_settings() {
        register_setting( self::OPTION_GROUP, self::OPTION_NAME, array( $this, 'sanitize_settings' ) );

        add_settings_section( self::SETTINGS_SECTION, '', '__return_false', 'shipping-invoice-settings' );

        // ثبت فیلدها (رندر UI سفارشی در settings_page)
        add_settings_field( 'logo_url', '', array( $this, 'logo_url_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'sender_name', '', array( $this, 'sender_name_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'sender_address', '', array( $this, 'sender_address_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'sender_postcode', '', array( $this, 'sender_postcode_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'sender_phone', '', array( $this, 'sender_phone_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'sender_email', '', array( $this, 'sender_email_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'sender_url', '', array( $this, 'sender_url_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'admin_note', '', array( $this, 'admin_note_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'invoice_template', '', array( $this, 'invoice_template_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'signature_url', '', array( $this, 'signature_url_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'enable_signature', '', array( $this, 'enable_signature_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'show_user_buttons', '', array( $this, 'show_user_buttons_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'enable_proforma_invoice', '', array( $this, 'enable_proforma_invoice_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
        add_settings_field( 'enable_checkout_map', '', array( $this, 'enable_checkout_map_html' ), 'shipping-invoice-settings', self::SETTINGS_SECTION );
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
        $s['admin_note']              = sanitize_textarea_field( $input['admin_note'] ?? '' );

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
    public function settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
        if ( ! in_array( $active_tab, array( 'settings', 'orders_export', 'support' ), true ) ) {
            $active_tab = 'settings';
        }
        ?>
        <div class="wrap faktorak-scope faktorak-admin-page faktorak-settings-page" dir="rtl">
            <div class="faktorak-manual-header faktorak-settings-header">
                <h1 class="fak-page-title">تنظیمات فاکتورک</h1>
                <p>تنظیمات چاپ، خروجی سفارشات، فاکتور دستی و لیست فاکتورها را از یک پیشخوان یکپارچه مدیریت کنید.</p>
            </div>
            <?php if ( function_exists( 'faktorak_render_admin_nav' ) ) { faktorak_render_admin_nav( $active_tab ); } ?>
            <?php if ( 'orders_export' === $active_tab ) : ?>
                <?php
                if ( class_exists( 'Faktorak_Order_Export' ) ) {
                    Faktorak_Order_Export::render_admin_page();
                } else {
                    echo '<div class="notice notice-error"><p>ماژول خروجی سفارشات در دسترس نیست.</p></div>';
                }
                ?>
            <?php elseif ( 'support' === $active_tab ) : ?>
                <?php
                if ( class_exists( 'Faktorak_Support_Modal' ) ) {
                    Faktorak_Support_Modal::render_admin_tab();
                } else {
                    echo '<div class="notice notice-error"><p>بخش پشتیبانی در دسترس نیست.</p></div>';
                }
                ?>
            <?php else : ?>

            <form method="post" action="options.php" class="fak-settings-form">
                <?php settings_fields( self::OPTION_GROUP ); ?>

                <div class="fak-page-grid">
                    <div class="fak-main-col">
                        <div class="fak-card fak-settings-card">
                            <h2 class="fak-card-title">تنظیمات فروشگاه و چاپ</h2>
                            <div class="fak-row fak-settings-grid">
                                <label class="fak-field fak-field-logo"><span class="fak-field-label">لوگوی فروشگاه</span><?php $this->logo_url_html(); ?></label>
                                <label class="fak-field"><span class="fak-field-label">نام فرستنده</span><?php $this->sender_name_html(); ?></label>
                                <label class="fak-field"><span class="fak-field-label">آدرس فرستنده</span><?php $this->sender_address_html(); ?></label>
                                <label class="fak-field"><span class="fak-field-label">کدپستی فرستنده</span><?php $this->sender_postcode_html(); ?></label>
                                <label class="fak-field"><span class="fak-field-label">تلفن فرستنده</span><?php $this->sender_phone_html(); ?></label>
                                <label class="fak-field"><span class="fak-field-label">ایمیل فرستنده</span><?php $this->sender_email_html(); ?></label>
                                <label class="fak-field"><span class="fak-field-label">وب‌سایت فرستنده</span><?php $this->sender_url_html(); ?></label>
                                <label class="fak-field"><span class="fak-field-label">یادداشت مدیر برای فاکتور مدرن</span><?php $this->admin_note_html(); ?></label>
                                <label class="fak-field"><span class="fak-field-label">قالب فاکتور</span><?php $this->invoice_template_html(); ?></label>
                                <label class="fak-field"><span class="fak-field-label">تصویر امضا/مهر</span><?php $this->signature_url_html(); ?></label>
                            </div>
                        </div>
                    </div>

                    <div class="fak-side-col">
                        <div class="fak-card fak-settings-card">
                            <h2 class="fak-card-title">فعال‌سازی قابلیت‌ها</h2>
                            <div class="fak-row fak-features-grid">
                                <?php $this->enable_signature_html(); ?>
                                <?php $this->show_user_buttons_html(); ?>
                                <?php $this->enable_proforma_invoice_html(); ?>
                                <?php $this->enable_checkout_map_html(); ?>
                            </div>
                        </div>

                        <div class="fak-card fak-settings-card">
                            <h2 class="fak-card-title">شورت‌کدهای آماده</h2>
                            <p class="fak-section-note">همین دو شورت‌کد را کپی کنید</p>

                            <div class="fak-row fak-shortcodes-grid">
                                <div class="fak-card fak-shortcode-card">
                                    <div class="fak-shortcode-head">
                                        <div>
                                            <div class="fak-shortcode-title">دکمهٔ فاکتور</div>
                                            <div class="fak-shortcode-desc">در صفحهٔ مشاهده سفارش، بدون ID هم کار می‌کند.</div>
                                        </div>
                                        <button type="button" class="button fak-copy-btn fak-copy-btn--inline" data-target="#fak-sc-invoice">کپی</button>
                                    </div>
                                    <input id="fak-sc-invoice" type="text" readonly class="fak-input" value='[faktorak_invoice_button]' />
                                </div>

                                <div class="fak-card fak-shortcode-card">
                                    <div class="fak-shortcode-head">
                                        <div>
                                            <div class="fak-shortcode-title">دکمهٔ پیش‌فاکتور</div>
                                            <div class="fak-shortcode-desc">از سبد فعلی کاربر یک سفارش «پیش‌فاکتور» می‌سازد.</div>
                                        </div>
                                        <button type="button" class="button fak-copy-btn fak-copy-btn--inline" data-target="#fak-sc-proforma">کپی</button>
                                    </div>
                                    <input id="fak-sc-proforma" type="text" readonly class="fak-input" value='[faktorak_proforma_button]' />
                                </div>
                            </div>
                        </div>

                        <div class="fak-submit-wrap">
                            <?php submit_button(); ?>
                        </div>
                    </div>
                </div>
            </form>

            <?php endif; ?>
        </div>
    <?php }

    /* Fields */
    public function logo_url_html() {
        $logo_url = $this->get_setting('logo_url'); ?>
        <div class="fak-logo-uploader<?php echo ! empty( $logo_url ) ? ' has-logo' : ' is-empty'; ?>">
            <div class="fak-logo-main">
                <input type="text" name="<?php echo esc_attr( self::OPTION_NAME . '[logo_url]' ); ?>" id="logo_url" value="<?php echo esc_attr( $logo_url ); ?>" class="regular-text" />
                <input type="button" class="button" value="آپلود لوگو" id="upload_logo_button" />
                <p class="description">لینک لوگو را وارد کنید یا از دکمه آپلود استفاده کنید.</p>
            </div>

            <div class="fak-media-preview-wrap fak-logo-preview-wrap">
                <?php if ( ! empty( $logo_url ) ) : ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="لوگو" class="fak-media-preview">
                <?php else : ?>
                    <span class="fak-logo-preview-placeholder">پیش‌نمایش لوگو</span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function sender_name_html()     { echo '<input type="text"  name="' . esc_attr( self::OPTION_NAME . '[sender_name]' ) . '"    value="' . esc_attr( $this->get_setting( 'sender_name' ) ) . '" class="regular-text" />'; }
    public function sender_address_html()  { echo '<textarea rows="3"   name="' . esc_attr( self::OPTION_NAME . '[sender_address]' ) . '" class="large-text">' . esc_textarea( $this->get_setting( 'sender_address' ) ) . '</textarea>'; }
    public function sender_postcode_html() { echo '<input type="text"  name="' . esc_attr( self::OPTION_NAME . '[sender_postcode]' ) . '" value="' . esc_attr( $this->get_setting( 'sender_postcode' ) ) . '" class="regular-text" />'; }
    public function sender_phone_html()    { echo '<input type="text"  name="' . esc_attr( self::OPTION_NAME . '[sender_phone]' ) . '"   value="' . esc_attr( $this->get_setting( 'sender_phone' ) ) . '" class="regular-text" />'; }
    public function sender_email_html()    { echo '<input type="email" name="' . esc_attr( self::OPTION_NAME . '[sender_email]' ) . '"   value="' . esc_attr( $this->get_setting( 'sender_email' ) ) . '" class="regular-text" />'; }
    public function sender_url_html()      { echo '<input type="url"   name="' . esc_attr( self::OPTION_NAME . '[sender_url]' ) . '"     value="' . esc_attr( $this->get_setting( 'sender_url' ) ) . '" class="regular-text" />'; }
    public function admin_note_html()      { echo '<textarea rows="4" name="' . esc_attr( self::OPTION_NAME . '[admin_note]' ) . '" class="large-text">' . esc_textarea( $this->get_setting( 'admin_note' ) ) . '</textarea><p class="description">این متن در بخش «یادداشت فروشنده» قالب مدرن نمایش داده می‌شود.</p>'; }

    public function invoice_template_html() {
        // نگه‌داشت سازگاری با مقدار قدیمی template-1
        $current = $this->get_setting('invoice_template');
        if ($current === 'template-1') {
            $current = 'classic';
        }
        ?>
        <select name="<?php echo esc_attr( self::OPTION_NAME . '[invoice_template]' ); ?>">
            <option value="modern" <?php selected($current, 'modern'); ?>>قالب مدرن (پیشنهادی)</option>
            <option value="classic" <?php selected($current, 'classic'); ?>>قالب کلاسیک</option>
        </select>
        <p class="description">قالب «مدرن» نسخه جدید با UI تمیز و پرینت بهینه است. «کلاسیک» همان قالب قبلی افزونه است.</p>
        <?php
    }

    public function signature_url_html() {
        $signature_url = $this->get_setting('signature_url'); ?>
        <input type="text" name="<?php echo esc_attr( self::OPTION_NAME . '[signature_url]' ); ?>" id="signature_url" value="<?php echo esc_attr( $signature_url ); ?>" class="regular-text" />
        <input type="button" class="button" value="آپلود امضا" id="upload_signature_button" />
        <p class="description">لینک تصویر امضا/مهر را وارد کنید یا از دکمه آپلود استفاده کنید.</p>
        <?php if ( ! empty( $signature_url ) ) : ?>
            <p class="fak-media-preview-wrap"><img src="<?php echo esc_url( $signature_url ); ?>" alt="امضا" class="fak-media-preview"></p>
        <?php endif; ?>
        <?php
    }

    /* سوییچرها */
    public function enable_signature_html()        { $this->render_switch('enable_signature',        'نمایش امضا در فاکتور'); }
    public function show_user_buttons_html()       { $this->render_switch('show_user_buttons',       'نمایش دکمه فاکتور در حساب کاربری مشتری'); }
    public function enable_proforma_invoice_html() { $this->render_switch('enable_proforma_invoice', 'نمایش خودکار دکمه «پیش‌فاکتور» در سبد خرید'); }
    public function enable_checkout_map_html()     { $this->render_switch('enable_checkout_map',     'نمایش نقشه در صفحه تسویه‌حساب'); }

    private function render_switch($key, $label){
        $id   = 'fak_' . $key;
        $name = self::OPTION_NAME . '[' . $key . ']';

        printf(
            '<label class="fak-switch" for="%1$s"><input id="%1$s" type="checkbox" name="%2$s" value="yes" %3$s /><span class="track"><span class="thumb"></span></span><span class="label">%4$s</span></label>',
            esc_attr( $id ),
            esc_attr( $name ),
            checked( $this->get_setting( $key ), 'yes', false ),
            esc_html( $label )
        );
    }

    /* Helpers */
    public function get_setting( $key ) {
        if ( !isset($this->default_settings[$key]) ) return null;
        return isset( $this->settings[$key] ) ? $this->settings[$key] : $this->default_settings[$key];
    }
}

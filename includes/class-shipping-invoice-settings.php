<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShippingInvoiceSettings {
    private $settings;
    private $default_settings;

    public function __construct() {
        $this->default_settings = array(
            'logo_url'           => '',
            'sender_name'        => get_bloginfo( 'name' ),
            'sender_address'     => '',
            'sender_postcode'    => '',
            'sender_phone'       => '',
            'sender_email'       => get_option( 'admin_email' ),
            'sender_url'         => home_url(),
            'invoice_template'   => 'template-1',
            'signature_url'      => '',
            'enable_signature'   => 'no',
            'show_user_buttons'  => 'no'
        );

        $this->settings = get_option( 'shipping_invoice_settings', $this->default_settings );
        if ( false === get_option( 'shipping_invoice_settings' ) ) {
            update_option( 'shipping_invoice_settings', $this->default_settings );
        }
    }

    public function register_settings() {
        register_setting( 'shipping_invoice_options', 'shipping_invoice_settings', array( $this, 'sanitize_settings' ) );
        add_settings_section( 'shipping_invoice_section', 'تنظیمات برچسب پستی و فاکتور', null, 'shipping-invoice-settings' );

        add_settings_field( 'logo_url', 'لوگوی فروشگاه', array( $this, 'logo_url_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_name', 'نام فرستنده', array( $this, 'sender_name_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_address', 'آدرس فرستنده', array( $this, 'sender_address_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_postcode', 'کدپستی فرستنده', array( $this, 'sender_postcode_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_phone', 'تلفن فرستنده', array( $this, 'sender_phone_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_email', 'ایمیل فرستنده', array( $this, 'sender_email_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'sender_url', 'وب‌سایت فرستنده', array( $this, 'sender_url_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'invoice_template', 'قالب فاکتور', array( $this, 'invoice_template_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'signature_url', 'تصویر امضا/مهر', array( $this, 'signature_url_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'enable_signature', 'فعال‌سازی امضا', array( $this, 'enable_signature_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
        add_settings_field( 'show_user_buttons', 'نمایش دکمه فاکتور در حساب کاربری مشتریان', array( $this, 'show_user_buttons_html' ), 'shipping-invoice-settings', 'shipping_invoice_section' );
    }

    public function sanitize_settings( $input ) {
        $sanitized = array();
        $sanitized['logo_url']         = esc_url_raw( $input['logo_url'] );
        $sanitized['sender_name']      = sanitize_text_field( $input['sender_name'] );
        $sanitized['sender_address']   = sanitize_textarea_field( $input['sender_address'] );
        $sanitized['sender_postcode']  = sanitize_text_field( $input['sender_postcode'] );
        $sanitized['sender_phone']     = sanitize_text_field( $input['sender_phone'] );
        $sanitized['sender_email']     = sanitize_email( $input['sender_email'] );
        $sanitized['sender_url']       = esc_url_raw( $input['sender_url'] );
        $sanitized['invoice_template'] = sanitize_text_field( $input['invoice_template'] );
        $sanitized['signature_url']    = esc_url_raw( $input['signature_url'] );
        $sanitized['enable_signature'] = isset( $input['enable_signature'] ) ? 'yes' : 'no';
        $sanitized['show_user_buttons'] = isset( $input['show_user_buttons'] ) ? 'yes' : 'no';
        return $sanitized;
    }

    public function settings_page() {
        ?>
        <div class="wrap faktorak-scope">
            <h1>تنظیمات فاکتورک</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'shipping_invoice_options' );
                do_settings_sections( 'shipping-invoice-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function logo_url_html() {
        $logo_url = isset( $this->settings['logo_url'] ) ? $this->settings['logo_url'] : '';
        ?>
        <input type="text" name="shipping_invoice_settings[logo_url]" id="logo_url" value="<?php echo esc_attr( $logo_url ); ?>" class="regular-text" />
        <input type="button" class="button" value="آپلود لوگو" id="upload_logo_button" />
        <p class="description">لینک لوگو را وارد کنید یا از دکمه آپلود استفاده کنید.</p>
        <?php if ( ! empty( $logo_url ) ) : ?>
            <p><img src="<?php echo esc_url( $logo_url ); ?>" alt="لوگوی فعلی" style="max-width: 150px; height: auto;"></p>
        <?php endif; ?>
        <script>
            jQuery(document).ready(function($) {
                var mediaUploader;
                $('#upload_logo_button').click(function(e) {
                    e.preventDefault();
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media({
                        title: 'انتخاب لوگو',
                        button: { text: 'انتخاب' },
                        multiple: false
                    });
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#logo_url').val(attachment.url);
                    });
                    mediaUploader.open();
                });
            });
        </script>
        <?php
    }

    public function sender_name_html() {
        $sender_name = isset( $this->settings['sender_name'] ) ? $this->settings['sender_name'] : '';
        echo '<input type="text" name="shipping_invoice_settings[sender_name]" value="' . esc_attr( $sender_name ) . '" class="regular-text" />';
    }
    public function sender_address_html() {
        $sender_address = isset( $this->settings['sender_address'] ) ? $this->settings['sender_address'] : '';
        echo '<textarea name="shipping_invoice_settings[sender_address]" rows="3" class="large-text">' . esc_textarea( $sender_address ) . '</textarea>';
    }
    public function sender_postcode_html() {
        $sender_postcode = isset( $this->settings['sender_postcode'] ) ? $this->settings['sender_postcode'] : '';
        echo '<input type="text" name="shipping_invoice_settings[sender_postcode]" value="' . esc_attr( $sender_postcode ) . '" class="regular-text" />';
    }
    public function sender_phone_html() {
        $sender_phone = isset( $this->settings['sender_phone'] ) ? $this->settings['sender_phone'] : '';
        echo '<input type="text" name="shipping_invoice_settings[sender_phone]" value="' . esc_attr( $sender_phone ) . '" class="regular-text" />';
    }
    public function sender_email_html() {
        $sender_email = isset( $this->settings['sender_email'] ) ? $this->settings['sender_email'] : '';
        echo '<input type="email" name="shipping_invoice_settings[sender_email]" value="' . esc_attr( $sender_email ) . '" class="regular-text" />';
    }
    public function sender_url_html() {
        $sender_url = isset( $this->settings['sender_url'] ) ? $this->settings['sender_url'] : '';
        echo '<input type="url" name="shipping_invoice_settings[sender_url]" value="' . esc_attr( $sender_url ) . '" class="regular-text" />';
    }
    public function invoice_template_html() {
        $template = isset( $this->settings['invoice_template'] ) ? $this->settings['invoice_template'] : 'template-1';
        echo '<select name="shipping_invoice_settings[invoice_template]">
                    <option value="template-1" ' . selected( $template, 'template-1', false ) . '>قالب 1</option>
                </select>';
    }
    public function signature_url_html() {
        $signature_url = isset( $this->settings['signature_url'] ) ? $this->settings['signature_url'] : '';
        ?>
        <input type="text" name="shipping_invoice_settings[signature_url]" id="signature_url" value="<?php echo esc_attr( $signature_url ); ?>" class="regular-text" />
        <input type="button" class="button" value="آپلود امضا" id="upload_signature_button" />
        <p class="description">لینک تصویر امضا یا مهر را وارد کنید یا از دکمه آپلود استفاده کنید.</p>
        <?php if ( ! empty( $signature_url ) ) : ?>
            <p><img src="<?php echo esc_url( $signature_url ); ?>" alt="امضای فعلی" style="max-width: 150px; height: auto;"></p>
        <?php endif; ?>
        <script>
            jQuery(document).ready(function($) {
                var mediaUploader;
                $('#upload_signature_button').click(function(e) {
                    e.preventDefault();
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media({
                        title: 'انتخاب امضا',
                        button: { text: 'انتخاب' },
                        multiple: false
                    });
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#signature_url').val(attachment.url);
                    });
                    mediaUploader.open();
                });
            });
        </script>
        <?php
    }
    public function enable_signature_html() {
        $enable_signature = isset( $this->settings['enable_signature'] ) ? $this->settings['enable_signature'] : 'no';
        ?>
        <input type="checkbox" name="shipping_invoice_settings[enable_signature]" value="yes" <?php checked( $enable_signature, 'yes' ); ?> />
        <label>نمایش امضا در فاکتور</label>
        <?php
    }
    public function show_user_buttons_html() {
        $show_user_buttons = isset( $this->settings['show_user_buttons'] ) ? $this->settings['show_user_buttons'] : 'no';
        ?>
        <input type="checkbox" name="shipping_invoice_settings[show_user_buttons]" value="yes" <?php checked( $show_user_buttons, 'yes' ); ?> />
        <label>نمایش دکمه فاکتور در حساب کاربری مشتریان</label>
        <?php
    }

    public function get_setting( $key ) {
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $this->default_settings[ $key ];
    }
}
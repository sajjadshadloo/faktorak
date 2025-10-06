<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * تابع اصلی برای بارگذاری هوک‌های افزونه
 */
function faktorak_initialize() {
    $settings = new ShippingInvoiceSettings();

    // هوک‌های عمومی
    add_action('admin_menu', 'shipping_invoice_add_menu');
    add_action('admin_init', 'shipping_invoice_register_settings');
    add_action('add_meta_boxes', 'shipping_invoice_add_order_metabox');
    add_action('admin_enqueue_scripts', 'faktorak_enqueue_admin_styles');
    add_action('template_redirect', 'shipping_invoice_display_content');
    add_action('init', 'shipping_invoice_rewrite_rules');
    add_filter('query_vars', 'shipping_invoice_query_vars');
    add_action('wp_enqueue_scripts', 'faktorak_enqueue_frontend_styles');

    // شورت‌کدها
    add_shortcode( 'faktorak_invoice_button', 'faktorak_invoice_button_shortcode' );
    add_shortcode( 'faktorak_proforma_button', 'faktorak_proforma_invoice_button_shortcode' );

    // پیش‌فاکتور
    add_action('init', 'faktorak_register_proforma_invoice_order_status');
    add_filter('wc_order_statuses', 'faktorak_add_proforma_invoice_to_order_statuses');
    add_action('template_redirect', 'faktorak_handle_create_proforma_invoice');

    // گزینه‌های شرطی
    if ( $settings->get_setting('show_user_buttons') === 'yes' ) {
        add_action('woocommerce_order_details_after_order_table', 'shipping_invoice_add_user_buttons_frontend', 10, 1);
    }
    if ( $settings->get_setting('enable_proforma_invoice') === 'yes' ) {
        add_action('woocommerce_proceed_to_checkout', 'faktorak_add_proforma_invoice_button_on_cart', 21);
    }
    if ( $settings->get_setting('enable_checkout_map') === 'yes' ) {
        add_action('wp_enqueue_scripts', 'faktorak_enqueue_map_assets');
        add_action('woocommerce_after_order_notes', 'faktorak_display_checkout_map');
        add_action('woocommerce_checkout_create_order', 'faktorak_save_map_location', 20, 2);
        add_action('woocommerce_admin_order_data_after_billing_address', 'faktorak_display_location_in_admin');
    }
}
add_action('plugins_loaded', 'faktorak_initialize', 20);


/*----------------------------
  ADMIN FUNCTIONS
----------------------------*/

function shipping_invoice_add_menu() {
    $settings = new ShippingInvoiceSettings();
    add_menu_page(
        'تنظیمات فاکتورک', 'فاکتورک', 'manage_options', 'shipping-invoice-settings',
        array($settings, 'settings_page'), 'dashicons-media-document', 80
    );
}

function shipping_invoice_register_settings() {
    $settings = new ShippingInvoiceSettings();
    $settings->register_settings();
}

function shipping_invoice_add_order_metabox() {
    $screen = get_current_screen();
    if ($screen && ($screen->id === 'shop_order' || $screen->id === 'woocommerce_page_wc-orders')) {
        add_meta_box(
            'shipping_invoice_metabox', 'فـاکتـورک', 'shipping_invoice_metabox_callback',
            null, 'side', 'default'
        );
    }
}

function shipping_invoice_metabox_callback($post_or_order) {
    $order_id = is_a($post_or_order, 'WC_Order') ? $post_or_order->get_id() : $post_or_order->ID;
    $shipping_label_url = add_query_arg(array('shipping_label' => 'true', 'order_id' => $order_id, 'context' => 'admin'), home_url());
    $invoice_url       = add_query_arg(array('invoice' => 'true', 'order_id' => $order_id, 'context' => 'admin'), home_url());
    ?>
    <div class="faktorak-scope" style="display:flex;flex-direction:column;gap:10px;padding:15px 0;">
        <a href="<?php echo esc_url($shipping_label_url); ?>" target="_blank" class="button button-primary faktorak-btn">برچسب پستی</a>
        <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="button button-primary faktorak-btn">مشاهده فاکتور</a>
    </div>
    <?php
}

function faktorak_enqueue_admin_styles($hook) {
    // فونت
    wp_enqueue_style('faktorak-custom-fonts', plugin_dir_url(__FILE__) . '../assets/css/custom-fonts.css', array(), '3.0');

    // استایل‌ها
    $css = "
    /* فقط دکمه‌های افزونه خودمان */
    .faktorak-scope a.faktorak-btn{
        width:100% !important;
        text-align:center;
        background:blue;
        color:#fff !important;
        font-weight:300;
        padding:15px;
        border-radius:12px;
        display:inline-block;
        text-decoration:none;
    }
    .faktorak-scope a.faktorak-btn:hover{
        background:blue;
        color:#fff !important;
        filter:brightness(0.9);
    }

    /* هاور کل باکس دکمه‌های صفحه سفارش کاربر */
    .shipping-invoice-buttons.faktorak-scope:hover{
        background:blue;
        border-radius:12px;
    }

    /* کارت‌ها (با حداکثر عرض برای جلوگیری از اسکرول) */
    .fak-card{border:1px solid #e5e7eb;border-radius:12px;padding:16px;background:#fff;max-width:980px}
    .fak-card + .fak-card{margin-top:12px}
    .fak-row{display:grid;grid-template-columns:1fr;gap:14px}

    /* فیلدها و لیبل‌ها (طبق استایلی که دادی) */
    .fak-row input, .fak-row textarea{
        padding:10px 15px;border-radius:12px;margin-top:10px;margin-bottom:5px;border-color:#e5e7eb;border-width:1px;border-style:solid
    }
    .fak-row label{font-size:15px !important}
    input#upload_signature_button, input#upload_logo_button{
        margin-top:12px;padding:10px 15px;background:#333;border-radius:12px;border:none;color:#fff
    }
    .fak-row select{
        margin-top:10px !important;border-color:#e5e5e5 !important;border-radius:8px !important
    }
    .fak-input{margin-top:10px;width:100%;direction:ltr;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px}

    /* سوییچر (Toggle) — برنگشته و فعاله */
    .fak-switch{display:inline-flex;align-items:center;gap:10px}
    .fak-switch input{display:none}
    .fak-switch .track{
        position:relative;width:48px;height:26px;border-radius:999px;
        background:#e5e7eb;transition:all .2s ease;cursor:pointer
    }
    .fak-switch .thumb{
        position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:999px;background:#fff;transition:all .2s ease;box-shadow:0 1px 2px rgba(0,0,0,.2)
    }
	div#shipping_invoice_metabox {
            border-radius: 12px;
            padding: 5px;
            border-color: #e5e5e5;
        }
        #shipping_invoice_metabox .faktorak-scope a.faktorak-btn {
            padding: 7px;
        }
    .fak-switch input:checked + .track{background:#2563eb}
    .fak-switch input:checked + .track .thumb{left:25px}
    .fak-switch .label{font-size:13px;color:#374151}
    ";
    wp_add_inline_style('faktorak-custom-fonts', $css);
}


/*----------------------------
  PUBLIC FUNCTIONS
----------------------------*/

function shipping_invoice_add_user_buttons_frontend($order) {
    if (!$order || !is_a($order, 'WC_Order')) return;
    $order_id = $order->get_id();

    // ✅ توکن امنیتی لینک فاکتور
    $token = wp_create_nonce('faktorak_inv_' . $order_id);

    $invoice_url = add_query_arg(
        array(
            'invoice'         => 'true',
            'order_id'        => $order_id,
            'context'         => 'user',
            'referrer'        => urlencode(wc_get_account_endpoint_url('orders')),
            'faktorak_token'  => $token,
        ),
        home_url()
    );
    ?>
    <div class="shipping-invoice-buttons faktorak-scope" style="margin:20px 0;display:flex;flex-direction:column;gap:10px;">
        <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="button faktorak-btn">مشاهده فاکتور</a>
    </div>
    <?php
}

function shipping_invoice_display_content() {
    if (isset($_GET['shipping_label']) && $_GET['shipping_label'] === 'true' && isset($_GET['order_id'])) {
        include_once plugin_dir_path(__FILE__) . '../templates/shipping-label.php';
        exit;
    }
    if (isset($_GET['invoice']) && $_GET['invoice'] === 'true' && isset($_GET['order_id'])) {

        // ✅ اعتبارسنجی توکن برای فرانت (ادمین معاف است)
        $is_admin_ctx = isset($_GET['context']) && $_GET['context'] === 'admin';
        if ( ! $is_admin_ctx ) {
            $order_id = absint($_GET['order_id']);
            $token    = isset($_GET['faktorak_token']) ? sanitize_text_field(wp_unslash($_GET['faktorak_token'])) : '';

            if ( empty($order_id) || empty($token) || ! wp_verify_nonce($token, 'faktorak_inv_' . $order_id) ) {
                wp_die(__('دسترسی غیرمجاز به فاکتور.', 'Factork'));
            }
        }

        // ✅ انتخاب قالب فاکتور (مدرن/کلاسیک) بر اساس تنظیمات
        $settings = new ShippingInvoiceSettings();
        $tpl = $settings->get_setting('invoice_template');
        if ($tpl === 'template-1') { $tpl = 'classic'; } // سازگاری با مقدار قدیمی
        $template_file = ($tpl === 'modern') ? 'invoice-template-modern.php' : 'invoice-template.php';

        include_once plugin_dir_path(__FILE__) . '../templates/' . $template_file;
        exit;
    }
}

function shipping_invoice_rewrite_rules() {
    add_rewrite_rule('^shipping-label/?$', 'index.php?shipping_label=true', 'top');
    add_rewrite_rule('^invoice/?$', 'index.php?invoice=true', 'top');
}

function shipping_invoice_query_vars($vars) {
    $vars[] = 'shipping_label';
    $vars[] = 'invoice';
    $vars[] = 'order_id';
    return $vars;
}

function faktorak_enqueue_frontend_styles() {
    wp_enqueue_style('faktorak-custom-fonts', plugin_dir_url(__FILE__) . '../assets/css/custom-fonts.css', array(), '3.0');

    $css = "
    .faktorak-scope a.faktorak-btn{
        width:100% !important;
        text-align:center;
        background:blue;
        color:#fff !important;
        font-weight:300;
        padding:15px;
        border-radius:12px;
        display:inline-block;
        text-decoration:none;
    }
    .faktorak-scope a.faktorak-btn:hover{
        background:blue;
        color:#fff !important;
        filter:brightness(0.9);
    }
    .shipping-invoice-buttons.faktorak-scope:hover{
        background:blue;
        border-radius:12px;
    }";
    wp_add_inline_style('faktorak-custom-fonts', $css);
}


/*----------------------------
  SHORTCODE FUNCTIONS
----------------------------*/

function faktorak_invoice_button_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'order_id' => 0,
            'text'     => __('مشاهده فاکتور', 'Factork'),
            'class'    => 'button faktorak-invoice-btn faktorak-btn',
            'icon'     => ''
        ),
        $atts,
        'faktorak_invoice_button'
    );

    $order_id = absint($atts['order_id']);
    if (!$order_id) {
        if (is_wc_endpoint_url('view-order')) {
            $order_id = absint(get_query_var('view-order'));
        } elseif (is_wc_endpoint_url('order-received')) {
            $order_id = absint(get_query_var('order-received'));
        }
    }
    if (!$order_id) return '';
    $order = wc_get_order($order_id);
    if (!$order) return '';

    if (!is_user_logged_in() && is_wc_endpoint_url('order-received')) {
        $order_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
        if ($order->get_order_key() !== $order_key) return '';
    }

    // ✅ توکن امنیتی لینک فاکتور
    $token = wp_create_nonce('faktorak_inv_' . $order_id);

    $url = add_query_arg(array(
        'invoice'        => 'true',
        'order_id'       => $order_id,
        'context'        => 'frontend',
        'faktorak_token' => $token,
    ), home_url());

    $icon_html = '';
    if (!empty($atts['icon'])) {
        $icon_html = '<img src="' . esc_url($atts['icon']) . '" alt="' . esc_attr($atts['text']) . '" class="pymdashboardviword-invoice-icon">';
    }

    $link = sprintf(
        '<a href="%s" target="_blank" rel="noopener" class="%s">%s%s</a>',
        esc_url($url),
        esc_attr($atts['class']),
        $icon_html,
        esc_html($atts['text'])
    );

    return '<div class="faktorak-scope">' . $link . '</div>';
}

function faktorak_proforma_invoice_button_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'order_id' => 0,
        'text'     => __( 'صدور پیش‌فاکتور', 'Factork' ),
        'class'    => 'button faktorak-proforma-btn faktorak-btn',
    ), $atts, 'faktorak_proforma_button' );

    $order_id = absint( $atts['order_id'] );
    if ( $order_id ) {
        // ✅ توکن امنیتی برای لینک پیش‌فاکتورِ سفارش موجود
        $token = wp_create_nonce('faktorak_inv_' . $order_id);
        $url = add_query_arg( array(
            'invoice'        => 'true',
            'order_id'       => $order_id,
            'is_proforma'    => 'true',
            'context'        => 'frontend',
            'faktorak_token' => $token,
        ), home_url() );
    } else {
        $url = wp_nonce_url(
            add_query_arg( 'create_proforma_invoice', 'true', wc_get_cart_url() ),
            'faktorak_create_proforma'
        );
    }

    $link = sprintf(
        '<a href="%s" target="_blank" rel="noopener" class="%s">%s</a>',
        esc_url( $url ),
        esc_attr( $atts['class'] ),
        esc_html( $atts['text'] )
    );

    return '<div class="faktorak-scope">' . $link . '</div>';
}


/*----------------------------
  PROFORMA INVOICE FUNCTIONS
----------------------------*/

function faktorak_register_proforma_invoice_order_status() {
    register_post_status('wc-proforma-invoice', array(
        'label' => 'پیش فاکتور',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('پیش فاکتور <span class="count">(%s)</span>', 'پیش فاکتورها <span class="count">(%s)</span>', 'Factork')
    ));
}

function faktorak_add_proforma_invoice_to_order_statuses($order_statuses) {
    $new_order_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-proforma-invoice'] = 'پیش فاکتور';
        }
    }
    return $new_order_statuses;
}

function faktorak_add_proforma_invoice_button_on_cart() {
    if (WC()->cart->is_empty()) return;
    $url = wp_nonce_url(add_query_arg('create_proforma_invoice', 'true', wc_get_cart_url()), 'faktorak_create_proforma');
    echo '<div class="faktorak-scope" style="margin-top:15px;text-align:right;"><a href="' . esc_url($url) . '" class="button faktorak-btn">دریافت پیش فاکتور</a></div>';
}

function faktorak_handle_create_proforma_invoice() {
    if (!isset($_GET['create_proforma_invoice']) || $_GET['create_proforma_invoice'] !== 'true') return;

    if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'faktorak_create_proforma')) {
        wp_die('خطای امنیتی. لطفاً دوباره تلاش کنید.');
    }
    if (WC()->cart->is_empty()) {
        wp_redirect(wc_get_cart_url());
        exit;
    }
    try {
        $order = wc_create_order();
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $order->add_product($product, $cart_item['quantity'], array('variation' => $cart_item['variation']));
        }
        if (is_user_logged_in()) {
            $user_id  = get_current_user_id();
            $customer = new WC_Customer($user_id);
            $order->set_customer_id($user_id);
            $order->set_address($customer->get_billing(), 'billing');
            $order->set_address($customer->get_shipping(), 'shipping');
        }
        $order->calculate_totals();
        $order->update_status('proforma-invoice', 'پیش فاکتور توسط کاربر از سبد خرید ایجاد شد.', true);
        $order_id = $order->get_id();
        if ($order_id) {
            // ✅ ریدایرکت به فاکتورِ پیش‌فاکتور با توکن امنیتی
            $token = wp_create_nonce('faktorak_inv_' . $order_id);
            $invoice_url = add_query_arg(array(
                'invoice'        => 'true',
                'order_id'       => $order_id,
                'context'        => 'proforma',
                'is_proforma'    => 'true',
                'faktorak_token' => $token,
            ), home_url());
            wp_redirect($invoice_url);
            exit;
        }
    } catch (Exception $e) {
        wc_add_notice('خطا در ایجاد پیش فاکتور: ' . $e->getMessage(), 'error');
        wp_redirect(wc_get_cart_url());
        exit;
    }
}


/*----------------------------
  CHECKOUT MAP FUNCTIONS
----------------------------*/

function faktorak_enqueue_map_assets() {
    if (is_checkout()) {
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet/dist/leaflet.js', [], null, true);
        wp_enqueue_style('leaflet-geocoder-css', 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css');
        wp_enqueue_script('leaflet-geocoder-js', 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js', ['leaflet-js'], null, true);
        wp_enqueue_style('leaflet-locate-css', 'https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.css');
        wp_enqueue_script('leaflet-locate-js', 'https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.js', ['leaflet-js'], null, true);

        /* استایل‌های UI نقشه (طبق خواسته تو) */
        $map_css = "
        .leaflet-control-geocoder.leaflet-bar.leaflet-control-geocoder-expanded.leaflet-control{
            margin:0;border:none;padding:5px 0 5px 5px;border-radius:12px;
        }
        .leaflet-control-geocoder-form input[type='search']{ font-size:13px; }
        .fb-map-message{ font-size:14px; font-family:inherit !important; }
        button.leaflet-control-geocoder-icon{ padding:0; border:none; }
        ";
        wp_add_inline_style('leaflet-geocoder-css', $map_css);

        $inline_script = "
        document.addEventListener('DOMContentLoaded', function(){
            if (document.getElementById('fb-map')) {
                const map = L.map('fb-map').setView([35.6892, 51.3890], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                let marker;
                map.on('click', function(e){
                    if(marker) map.removeLayer(marker);
                    marker = L.marker(e.latlng).addTo(map);
                    document.querySelector('#checkout_latlng').value = e.latlng.lat + ',' + e.latlng.lng;
                });

                const geocoder = L.Control.geocoder({
                    collapsed:false, defaultMarkGeocode:false,
                    placeholder:'جستجو شهر یا آدرس...', errorMessage:'هیچ نتیجه‌ای پیدا نشد',
                    geocoder:L.Control.Geocoder.nominatim({ geocodingQueryParams:{ 'accept-language':'fa', 'countrycodes':'ir' } })
                }).on('markgeocode', function(e){
                    const latlng = e.geocode.center;
                    map.setView(latlng, 14);
                    if(marker) map.removeLayer(marker);
                    marker = L.marker(latlng).addTo(map);
                    document.querySelector('#checkout_latlng').value = latlng.lat + ',' + latlng.lng;
                }).addTo(map);

                L.control.locate({
                    position:'topleft', flyTo:true, keepCurrentZoomLevel:false,
                    strings:{ title:'مکان فعلی من' }
                }).addTo(map);
            }
        });";
        wp_add_inline_script('leaflet-geocoder-js', $inline_script);
    }
}

function faktorak_display_checkout_map($checkout){
    echo '<div class="fb-map-message">می‌توانید برای ارسال مطمئن‌تر، لوکیشن خود را روی نقشه ثبت کنید.</div>';
    echo '<div id="fb-map" style="height:400px;width:100%;"></div>';
    echo '<input type="hidden" id="checkout_latlng" name="checkout_latlng">';
}

function faktorak_save_map_location($order, $data){
    if(!empty($_POST['checkout_latlng'])){
        $order->update_meta_data('_delivery_location', sanitize_text_field($_POST['checkout_latlng']));
    }
}

function faktorak_display_location_in_admin($order){
    $location = $order->get_meta('_delivery_location');
    if($location){
        $maps_url = 'https://www.google.com/maps?q=' . $location;
        echo '<p><strong>لوکیشن ارسال: </strong><br><a href="'.esc_url($maps_url).'" target="_blank">برای دیدن لوکیشن ارسال کلیک کنید</a></p>';
        echo '<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.urlencode($maps_url).'" alt="QR Code" />';
    }
}


/*--------------------------------------------
  PROFORMA TO ORDER CONVERSION FUNCTIONS
----------------------------------------------*/

function faktorak_add_convert_order_action( $actions ) {
    global $theorder;
    if ( $theorder && $theorder->has_status( 'proforma-invoice' ) ) {
        $actions['faktorak_convert_to_payable'] = __( 'تبدیل به سفارش قابل پرداخت', 'Factork' );
    }
    return $actions;
}
add_filter( 'woocommerce_order_actions', 'faktorak_add_convert_order_action' );

function faktorak_process_convert_order_action( $order ) {
    $order->update_status( 'pending', __( 'پیش فاکتور توسط مدیر به سفارش قابل پرداخت تبدیل شد.', 'Factork' ) );
    WC()->payment_gateways();
    WC()->shipping();
    WC()->mailer()->customer_invoice( $order );
    $order->add_order_note( __( 'ایمیل فاکتور به همراه لینک پرداخت برای مشتری ارسال شد.', 'Factork' ) );
}
add_action( 'woocommerce_order_action_faktorak_convert_to_payable', 'faktorak_process_convert_order_action' );

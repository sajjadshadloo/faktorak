<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*----------------------------
  ADMIN FUNCTIONS
----------------------------*/

/**
 * افزودن منوی تنظیمات افزونه در پیشخوان
 */
function shipping_invoice_add_menu() {
    $settings = new ShippingInvoiceSettings();
    add_menu_page(
        'تنظیمات فاکتورک',
        'فاکتورک',
        'manage_options',
        'shipping-invoice-settings',
        array( $settings, 'settings_page' ),
        'dashicons-media-document',
        80
    );
}
add_action('admin_menu', 'shipping_invoice_add_menu');

/**
 * ثبت تنظیمات افزونه
 */
function shipping_invoice_register_settings() {
    $settings = new ShippingInvoiceSettings();
    $settings->register_settings();
}
add_action('admin_init', 'shipping_invoice_register_settings');

/**
 * ایجاد متاباکس در صفحه سفارش (ادمین) برای دکمه‌های برچسب پستی و فاکتور
 */
function shipping_invoice_add_order_metabox() {
    $screen = get_current_screen();
    if ( $screen && ( $screen->id === 'shop_order' || $screen->id === 'woocommerce_page_wc-orders' ) ) {
        add_meta_box(
            'shipping_invoice_metabox',
            'فـاکتـورک',
            'shipping_invoice_metabox_callback',
            null,
            'side',
            'default'
        );
    }
}
add_action( 'add_meta_boxes', 'shipping_invoice_add_order_metabox' );

/**
 * محتوای متاباکس سفارش (ادمین)
 */
function shipping_invoice_metabox_callback( $post_or_order ) {
    $order_id = is_a( $post_or_order, 'WC_Order' ) ? $post_or_order->get_id() : $post_or_order->ID;
    $shipping_label_url = add_query_arg(
        array( 'shipping_label' => 'true', 'order_id' => $order_id, 'context' => 'admin' ),
        home_url()
    );
    $invoice_url = add_query_arg(
        array( 'invoice' => 'true', 'order_id' => $order_id, 'context' => 'admin' ),
        home_url()
    );
    ?>
    <div class="faktorak-scope" style="display: flex; flex-direction: column; gap: 10px; padding: 15px 0;">
        <a href="<?php echo esc_url( $shipping_label_url ); ?>" target="_blank" class="button button-primary">برچسب پستی</a>
        <a href="<?php echo esc_url( $invoice_url ); ?>" target="_blank" class="button button-primary">مشاهده فاکتور</a>
    </div>
    <?php
}

/**
 * بارگذاری استایل‌های فونت سفارشی در پیشخوان
 */
function faktorak_enqueue_admin_styles( $hook ) {
    wp_enqueue_style(
        'faktorak-custom-fonts',
        plugin_dir_url( __FILE__ ) . '../assets/css/custom-fonts.css',
        array(),
        '2.4' // نسخه جدید برای جلوگیری از کش
    );
}
add_action( 'admin_enqueue_scripts', 'faktorak_enqueue_admin_styles' );

/*----------------------------
  PUBLIC FUNCTIONS
----------------------------*/

/**
 * نمایش دکمه فاکتور در صفحه سفارش کاربر (My Account) برای مشتریان
 */
function shipping_invoice_add_user_buttons_frontend( $order ) {
    if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
        return;
    }

    // بررسی تنظیمات نمایش دکمه‌ها
    $settings = new ShippingInvoiceSettings();
    $show_user_buttons = $settings->get_setting( 'show_user_buttons' );
    if ( $show_user_buttons !== 'yes' ) {
        return;
    }

    $order_id = $order->get_id();
    $invoice_url = add_query_arg(
        array( 'invoice' => 'true', 'order_id' => $order_id, 'context' => 'user', 'referrer' => urlencode( wc_get_account_endpoint_url( 'orders' ) ) ),
        home_url()
    );
    ?>
    <div class="shipping-invoice-buttons faktorak-scope" style="margin: 20px 0; display: flex; flex-direction: column; gap: 10px;">
        <a href="<?php echo esc_url( $invoice_url ); ?>" target="_blank" class="button">مشاهده فاکتور</a>
    </div>
    <?php
}
add_action( 'woocommerce_order_details_after_order_table', 'shipping_invoice_add_user_buttons_frontend', 10, 1 );

/**
 * نمایش محتوای فاکتور یا برچسب پستی در فرانت‌اند بر اساس پارامترهای URL
 */
function shipping_invoice_display_content() {
    if ( isset( $_GET['shipping_label'] ) && $_GET['shipping_label'] === 'true' && isset( $_GET['order_id'] ) ) {
        include_once plugin_dir_path( __FILE__ ) . '../templates/shipping-label.php';
        exit;
    }
    if ( isset( $_GET['invoice'] ) && $_GET['invoice'] === 'true' && isset( $_GET['order_id'] ) ) {
        include_once plugin_dir_path( __FILE__ ) . '../templates/invoice-template.php';
        exit;
    }
}
add_action( 'template_redirect', 'shipping_invoice_display_content' );

/**
 * قوانین بازنویسی برای دسترسی به لینک‌های برچسب پستی و فاکتور
 */
function shipping_invoice_rewrite_rules() {
    add_rewrite_rule( '^shipping-label/?$', 'index.php?shipping_label=true', 'top' );
    add_rewrite_rule( '^invoice/?$', 'index.php?invoice=true', 'top' );
}
add_action( 'init', 'shipping_invoice_rewrite_rules' );

/**
 * ثبت متغیرهای کوئری سفارشی
 */
function shipping_invoice_query_vars( $vars ) {
    $vars[] = 'shipping_label';
    $vars[] = 'invoice';
    $vars[] = 'order_id';
    return $vars;
}
add_filter( 'query_vars', 'shipping_invoice_query_vars' );

/**
 * بارگذاری استایل‌های فونت در فرانت‌اند
 */
function faktorak_enqueue_frontend_styles() {
    if ( is_account_page() ) {
        wp_enqueue_style(
            'faktorak-custom-fonts',
            plugin_dir_url( __FILE__ ) . '../assets/css/custom-fonts.css',
            array(),
            '2.4' // نسخه جدید برای جلوگیری از کش
        );
    }
}
add_action( 'wp_enqueue_scripts', 'faktorak_enqueue_frontend_styles' );
<?php
/*
 Plugin Name: فاکتورک (Faktorak)
 Plugin URI: https://sajjadshadloo.ir/product/faktorak-plugin/
 Description: افزونه فاکتور و برچسب پستی برای فروشگاه‌های ووکامرسی - این افزونه امکانات چاپ فاکتور، برچسب پستی و مدیریت سفارشات را فراهم می‌کند.
 Version: 1.5.0
 Author: سجاد شادلو
 Author URI: https://sajjadshadloo.ir
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: faktorak
Domain Path: /languages
 Requires at least: 5.0
 Tested up to: 6.9s
 WC requires at least: 3.0
 WC Tested up to: 9.0
 Tags: invoice, shipping label, woocommerce, invoice printing, label printing
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// تعریف مسیر افزونه
define( 'FAKTORAK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FAKTORAK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FAKTORAK_VERSION', '1.5.0' );
define( 'FAKTORAK_SUPPORT_NAME', 'سجاد شادلو' );
define( 'FAKTORAK_SUPPORT_SITE', 'https://sajjadshadloo.ir' );
define( 'FAKTORAK_SUPPORT_PHONE', '' );
define( 'FAKTORAK_SUPPORT_TELEGRAM', 'sajjadshadloo' );
define( 'FAKTORAK_SUPPORT_BALE', 'sajjadshadloo' );
define( 'FAKTORAK_SUPPORT_WHATSAPP', '09381385284' );
define( 'FAKTORAK_PLUGIN_MANAGER_URL', 'https://sajjadshadloo.ir' );
define( 'FAKTORAK_PLUGIN_MANAGER_TOKEN', 'a7c40685de9f13b2d98c42e5b71a603f' );

// فراخوانی فایل‌های مورد نیاز
require_once FAKTORAK_PLUGIN_DIR . 'includes/class-shipping-invoice-settings.php';
require_once FAKTORAK_PLUGIN_DIR . 'includes/class-faktorak-order-export.php';
require_once FAKTORAK_PLUGIN_DIR . 'includes/class-faktorak-support-modal.php';
require_once FAKTORAK_PLUGIN_DIR . 'includes/class-faktorak-plugin-manager-client.php';
require_once FAKTORAK_PLUGIN_DIR . 'includes/functions.php';

// بررسی وجود ووکامرس در زمان مناسب
add_action( 'plugins_loaded', 'faktorak_check_woocommerce' );
function faktorak_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__( 'افزونه فاکتورک نیازمند ووکامرس است. لطفاً ووکامرس را نصب و فعال کنید.', 'faktorak' ) . '</p></div>';
        });
        return;
    }

    // راه‌اندازی تنظیمات افزونه
    new Faktorak_Shipping_Invoice_Settings();
}

add_action( 'plugins_loaded', 'faktorak_load_support_modal', 30 );
function faktorak_load_support_modal() {
    if ( is_admin() && class_exists( 'Faktorak_Support_Modal' ) ) {
        new Faktorak_Support_Modal();
    }
}

add_action( 'plugins_loaded', 'faktorak_load_plugin_manager_client', 30 );
function faktorak_load_plugin_manager_client() {
    if ( is_admin() && class_exists( 'Faktorak_Plugin_Manager_Client' ) ) {
        new Faktorak_Plugin_Manager_Client();
    }
}

// ✅ سازگاری با HPOS (WooCommerce High-Performance Order Storage) — فقط همین یک‌بار
add_action('before_woocommerce_init', function () {
    if ( class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables', __FILE__, true
        );
    }
});

// ✅ فلش ریرایت فقط موقع فعال/غیرفعال‌سازی افزونه (برای مسیرهای invoice/shipping-label)
register_activation_hook(__FILE__, function () {
    if ( function_exists('faktorak_initialize') ) { faktorak_initialize(); }
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

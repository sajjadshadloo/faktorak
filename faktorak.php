<?php
/*
Plugin Name: فاکتورک (Factorak)
Plugin URI: https://sajjadshadloo.ir/product/faktorak-plugin/
Description: افزونه فاکتور و برچسب پستی برای فروشگاه‌های ووکامرسی - این افزونه امکانات چاپ فاکتور، برچسب پستی و مدیریت سفارشات را فراهم می‌کند.
Version: 1.3.1
Author: سجاد شادلو
Author URI: https://sajjadshadloo.ir
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: faktorak
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.8
WC requires at least: 3.0
WC Tested up to: 9.0
Tags: invoice, shipping label, woocommerce, invoice printing, label printing
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

// تعریف مسیر افزونه
define( 'FAKTORAK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// فراخوانی فایل‌های مورد نیاز
require_once FAKTORAK_PLUGIN_DIR . 'includes/class-shipping-invoice-settings.php';
require_once FAKTORAK_PLUGIN_DIR . 'includes/functions.php';

// بررسی وجود ووکامرس در زمان مناسب
add_action( 'plugins_loaded', 'faktorak_check_woocommerce' );
function faktorak_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . __( 'افزونه فاکتورک نیازمند ووکامرس است. لطفاً ووکامرس را نصب و فعال کنید.', 'faktorak' ) . '</p></div>';
        });
        return;
    }

    // راه‌اندازی تنظیمات افزونه
    new ShippingInvoiceSettings();
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

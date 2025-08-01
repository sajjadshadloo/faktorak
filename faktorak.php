<?php
/*
Plugin Name: Factork (فاکتورک) - سجاد شادلو
Plugin URI: https://sajjadshadloo.ir/product/factork-plugin/
Description: افزونه فاکتور و برچسب پستی برای فروشگاه‌های ووکامرسی - این افزونه امکانات چاپ فاکتور، برچسب پستی و مدیریت سفارشات را فراهم می‌کند.
Version: 1.3
Author: سجاد شادلو
Author URI: https://sajjadshadloo.ir
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: Factork
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.6
WC requires at least: 3.0
WC Tested up to: 9.0
Tags: invoice, shipping label, woocommerce, invoice printing, label printing
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
            echo '<div class="error"><p>' . __( 'افزونه فاکتورک نیازمند ووکامرس است. لطفاً ووکامرس را نصب و فعال کنید.', 'Factork' ) . '</p></div>';
        });
        return;
    }

    // اعلام سازگاری با HPOS
    add_action( 'before_woocommerce_init', function() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    });

    // راه‌اندازی تنظیمات افزونه
    new ShippingInvoiceSettings();
}



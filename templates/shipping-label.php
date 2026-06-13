<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order = wc_get_order($order_id);
if ( ! $order ) { wp_die( esc_html__( 'سفارش یافت نشد.', 'faktorak' ) ); }

$settings         = new Faktorak_Shipping_Invoice_Settings();
$logo_url         = $settings->get_setting('logo_url');
$sender_name      = $settings->get_setting('sender_name');
$sender_address   = $settings->get_setting('sender_address');
$sender_postcode  = $settings->get_setting('sender_postcode');
$sender_phone     = $settings->get_setting('sender_phone');

// دریافت اطلاعات آدرس گیرنده و تفکیک آنها
$recipient_name      = $order->get_formatted_billing_full_name();
$country_code        = $order->get_billing_country();
$state_code          = $order->get_billing_state();
$states              = WC()->countries->get_states( $country_code );
$recipient_state     = isset( $states[ $state_code ] ) ? $states[ $state_code ] : $state_code;
$recipient_city      = $order->get_billing_city();
$recipient_address_1 = $order->get_billing_address_1();
$recipient_address_2 = $order->get_billing_address_2();
$full_address        = $recipient_address_1 . ( $recipient_address_2 ? ' - ' . $recipient_address_2 : '' );
$recipient_phone     = $order->get_billing_phone();
$recipient_postcode  = $order->get_billing_postcode();

// فرمت تاریخ
$order_date = $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'Y-m-d' ) : '';
$print_date = wc_format_datetime( new WC_DateTime(), 'Y-m-d H:i' );

// تعیین URL برگشت
$context  = isset($_GET['context']) ? $_GET['context'] : '';
$back_url = home_url();
if ( $context === 'admin' ) {
    $back_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
} elseif ( $context === 'user' ) {
    $back_url = wc_get_endpoint_url( 'view-order', $order_id, wc_get_account_endpoint_url( 'orders' ) );
}

// دریافت لوکیشن از متادیتای سفارش
$location = $order->get_meta('_delivery_location');
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa-IR">
<head>
    <title>برچسب پستی (<?php echo esc_html($order_id); ?>)</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php wp_print_styles( array( 'faktorak-custom-fonts', 'faktorak-shipping-label' ) ); ?>
    <?php wp_print_head_scripts(); ?>
</head>
<body class="faktorak-scope">
    <div class="label-wrapper">
        <div class="label-inner">

            <div class="shop-logo">
                <?php if ( ! empty( $logo_url ) ) : ?>
                    <div class="logo-box">
                        <img class="faktorak-no-lazy no-lazy" src="<?php echo esc_url($logo_url); ?>" alt="لوگو" loading="eager" decoding="sync" fetchpriority="high" data-no-lazy="1">
                    </div>
                <?php endif; ?>
            </div>

            <table class="table-bordered">
                <tbody>
                    <tr>
                        <td style="width:50%; vertical-align: top;">
                            <div class="component full-name">
                                <span class="content"><span class="title">نام گیرنده:</span> <span><?php echo esc_html($recipient_name); ?></span></span>
                            </div>
                            <?php if ( ! empty( $recipient_state ) ) : ?>
                            <div class="component state">
                                <span class="content"><span class="title">استان:</span> <span><?php echo esc_html($recipient_state); ?></span></span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $recipient_city ) ) : ?>
                            <div class="component city">
                                <span class="content"><span class="title">شهر:</span> <span><?php echo esc_html($recipient_city); ?></span></span>
                            </div>
                            <?php endif; ?>
                            <div class="component recipient">
                                <span class="content"><span class="title">آدرس:</span> <span><?php echo esc_html($full_address); ?></span></span>
                            </div>
                            <?php if ( ! empty( $recipient_postcode ) ) : ?>
                            <div class="component postcode">
                                <span class="content"><span class="title">کدپستی:</span> <span><?php echo esc_html($recipient_postcode); ?></span></span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $recipient_phone ) ) : ?>
                            <div class="component phone">
                                <span class="content"><span class="title">تلفن:</span> <span><?php echo esc_html($recipient_phone); ?></span></span>
                            </div>
                            <?php endif; ?>
                            <?php if ( $location ) :
                                $maps_url = 'https://www.google.com/maps?q=' . rawurlencode( $location ); ?>
                                <div class="barcode-recipient" style="margin-top: 15px;">
                                    <div class="faktorak-qr" data-qr="<?php echo esc_attr( esc_url( $maps_url ) ); ?>" data-qr-size="90"></div>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td style="width:50%; vertical-align: top;">
                            <div class="component title">
                                <span class="content"><span class="title">فرستنده:</span> <span><?php echo esc_html($sender_name); ?></span></span>
                            </div>
                            <?php if ( ! empty( $sender_address ) ) : ?>
                            <div class="component address">
                                <span class="content"><span class="title">آدرس:</span> <span><?php echo esc_html($sender_address); ?></span></span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $sender_postcode ) ) : ?>
                            <div class="component shop-postcode">
                                <span class="content"><span class="title">کدپستی:</span> <span><?php echo esc_html($sender_postcode); ?></span></span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $sender_phone ) ) : ?>
                            <div class="component shop-phone">
                                <span class="content"><span class="title">تلفن:</span> <span><?php echo esc_html($sender_phone); ?></span></span>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="order-info">
                            <span><span class="title">شماره سفارش:</span><span> <?php echo esc_html($order_id); ?></span></span>
                            <span>|</span>
                            <span><span class="title">تاریخ سفارش:</span><span dir="ltr"> <?php echo esc_html($order_date); ?></span></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="print-buttons">
        <a href="#" class="button" onclick="window.print()">چاپ</a>
        <a href="<?php echo esc_url($back_url); ?>" class="button">بازگشت به سفارش</a>
    </div>
    <?php wp_print_footer_scripts(); ?>
</body>
</html>

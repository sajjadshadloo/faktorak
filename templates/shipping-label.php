<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order = wc_get_order($order_id);
if ( ! $order ) {
    wp_die('سفارش یافت نشد.');
}

$settings         = new ShippingInvoiceSettings();
$logo_url         = $settings->get_setting('logo_url');
$sender_name      = $settings->get_setting('sender_name');
$sender_address   = $settings->get_setting('sender_address');
$sender_postcode  = $settings->get_setting('sender_postcode');
$sender_phone     = $settings->get_setting('sender_phone');
$sender_email     = $settings->get_setting('sender_email');
$sender_url       = $settings->get_setting('sender_url');

$recipient_name    = $order->get_formatted_billing_full_name();
$recipient_address = $order->get_billing_address_1() . ( $order->get_billing_address_2() ? ', ' . $order->get_billing_address_2() : '' ) . ', ' . $order->get_billing_city();
$recipient_phone   = $order->get_billing_phone();
$recipient_postcode= $order->get_billing_postcode();
$order_date        = $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'Y-m-d H:i' ) : '';
$print_date        = wc_format_datetime( new WC_DateTime(), 'Y-m-d H:i' );

// تعیین URL برگشت
$context = isset($_GET['context']) ? $_GET['context'] : '';
$back_url = home_url(); // پیش‌فرض
if ( $context === 'admin' ) {
    $back_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
} elseif ( $context === 'user' ) {
    $back_url = wc_get_endpoint_url( 'view-order', $order_id, wc_get_account_endpoint_url( 'orders' ) );
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa-IR">
<head>
    <title>برچسب پستی (<?php echo esc_html($order_id); ?>)</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?php echo esc_url( plugin_dir_url(__FILE__) . '../assets/css/custom-fonts.css' ); ?>" type="text/css" />

    <style>
        html, body {
            margin: 0;
            padding: 0;
            line-height: 34px;
            font-size: 14px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .label-wrapper {
            background: repeating-linear-gradient(135deg, #CF2004 0px, #CF2004 15px, transparent 15px, transparent 25px, #3EA8F2 25px, #3EA8F2 40px, transparent 40px, transparent 50px);
            margin: 20px auto;
            max-width: 1000px;
            padding: 10px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .label-inner {
            background: #FFF;
            padding: 20px;
            border: 1px solid #000;
            font-family: 'iranyekan', sans-serif !important;
        }
        .shop-logo {
            text-align: right;
            border-bottom: 1px solid #000;
            padding: 15px 0;
            margin-bottom: 20px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .shop-logo img {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }
        .table-bordered {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #000;
            padding: 10px;
            vertical-align: top;
            font-family: 'iranyekan', sans-serif !important;
        }
        .title {
            font-weight: bold;
            font-family: 'iranyekan', sans-serif !important;
        }
        .order-info {
            text-align: center;
            white-space: nowrap;
            font-weight: bold;
            font-family: 'iranyekan', sans-serif !important;
        }
        .order-info span {
            margin: 0 10px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .print-buttons {
            text-align: center;
            margin-top: 20px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .button {
            background: #FF6347;
            color: #FFF;
            text-align: center;
            border-radius: 2px;
            line-height: 46px;
            cursor: pointer;
            padding: 0 15px;
            display: inline-block;
            border: none;
            text-decoration: none;
            margin: 0 5px;
            font-family: 'iranyekan', sans-serif !important;
        }
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .print-buttons {
                display: none !important;
            }
        }
    </style>
</head>
<body class="faktorak-scope">
    <div class="label-wrapper">
        <div class="label-inner">
            <div class="shop-logo">
                <?php if ( ! empty( $logo_url ) ) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="لوگو">
                <?php endif; ?>
            </div>
            <table class="table-bordered">
                <tbody>
                    <tr>
                        <td style="width:50%;">
                            <div class="component recipient">
                                <span class="content">
                                    <span class="title">گیرنده:</span>
                                    <span> <?php echo esc_html($recipient_address); ?></span>
                                </span>
                            </div>
                            <div class="component full-name">
                                <span class="content">
                                    <span class="title">نام:</span>
                                    <span> <?php echo esc_html($recipient_name); ?></span>
                                </span>
                            </div>
                            <?php if ( ! empty( $recipient_postcode ) ) : ?>
                            <div class="component postcode">
                                <span class="content">
                                    <span class="title">کدپستی:</span>
                                    <span> <?php echo esc_html($recipient_postcode); ?></span>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $recipient_phone ) ) : ?>
                            <div class="component phone">
                                <span class="content">
                                    <span class="title">تلفن:</span>
                                    <span> <?php echo esc_html($recipient_phone); ?></span>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="component order-date">
                                <span class="content">
                                    <span class="title">تاریخ سفارش:</span>
                                    <span dir="ltr"> <?php echo esc_html($order_date); ?></span>
                                </span>
                            </div>
                        </td>
                        <td style="width:50%;">
                            <div class="component title">
                                <span class="content">
                                    <span class="title">فرستنده:</span>
                                    <span> <?php echo esc_html($sender_name); ?></span>
                                </span>
                            </div>
                            <?php if ( ! empty( $sender_address ) ) : ?>
                            <div class="component address">
                                <span class="content">
                                    <span class="title">آدرس:</span>
                                    <span> <?php echo esc_html($sender_address); ?></span>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $sender_postcode ) ) : ?>
                            <div class="component shop-postcode">
                                <span class="content">
                                    <span class="title">کدپستی:</span>
                                    <span> <?php echo esc_html($sender_postcode); ?></span>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $sender_phone ) ) : ?>
                            <div class="component shop-phone">
                                <span class="content">
                                    <span class="title">تلفن:</span>
                                    <span> <?php echo esc_html($sender_phone); ?></span>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $sender_email ) ) : ?>
                            <div class="component shop-email">
                                <span class="content">
                                    <span class="title">ایمیل:</span>
                                    <span> <?php echo esc_html($sender_email); ?></span>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $sender_url ) ) : ?>
                            <div class="component url">
                                <span class="content">
                                    <span class="title">وب‌سایت:</span>
                                    <span> <?php echo esc_html($sender_url); ?></span>
                                </span>
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
                            <span><span class="title">تاریخ چاپ:</span><span dir="ltr"> <?php echo esc_html($print_date); ?></span></span>
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
</body>
</html>
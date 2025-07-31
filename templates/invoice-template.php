<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order = wc_get_order($order_id);
if ( ! $order ) {
    wp_die('سفارش یافت نشد.');
}

$settings = new ShippingInvoiceSettings();

$logo_url         = $settings->get_setting('logo_url');
$sender_name      = $settings->get_setting('sender_name');
$sender_address   = $settings->get_setting('sender_address');
$sender_postcode  = $settings->get_setting('sender_postcode');
$sender_phone     = $settings->get_setting('sender_phone');
$sender_email     = $settings->get_setting('sender_email');
$sender_url       = $settings->get_setting('sender_url');
$signature_url    = $settings->get_setting('signature_url');
$enable_signature = $settings->get_setting('enable_signature');

$recipient_name    = $order->get_formatted_billing_full_name();
$recipient_address = $order->get_billing_address_1() . ( $order->get_billing_address_2() ? ', ' . $order->get_billing_address_2() : '' ) . ', ' . $order->get_billing_city();
$recipient_postcode= $order->get_billing_postcode();
$recipient_phone   = $order->get_billing_phone();
$order_date        = $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'Y-m-d H:i' ) : '';
$print_date        = wc_format_datetime( new WC_DateTime(), 'Y-m-d H:i' );

// تعیین URL برگشت بر اساس context
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
    <title>فاکتور (<?php echo esc_html($order_id); ?>)</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="stylesheet" href="<?php echo esc_url( plugin_dir_url(__FILE__) . '../assets/css/custom-fonts.css' ); ?>" type="text/css" />
    
    <style type="text/css">
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-size: 14px;
            color: #000;
            line-height: 1.8;
            font-family: 'iranyekan', sans-serif !important;
        }
        .container {
            width: 100%;
            margin: 20px auto;
            padding: 10px;
            border: 1px solid transparent;
            font-family: 'iranyekan', sans-serif !important;
        }
        table.shop-info {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 0 auto;
            text-align: center;
            border: 1px solid #000;
            font-family: 'iranyekan', sans-serif !important;
        }
        table.shop-info td {
            width: 33.3333%;
            vertical-align: middle;
            padding: 10px;
            text-align: center;
            font-family: 'iranyekan', sans-serif !important;
        }
        .shop-info tfoot td {
            background: rgba(72, 72, 72, 0.25);
            font-family: 'iranyekan', sans-serif !important;
        }
        .address-postcode-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 15px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .shop-logo img {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }
        .shop-logo {
            font-family: 'iranyekan', sans-serif !important;
        }
        .customer-info {
            border: 1px solid #000;
            padding: 15px;
            background: rgba(72, 72, 72, 0.25);
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
            font-family: 'iranyekan', sans-serif !important;
        }
        table.products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            text-align: center;
            font-family: 'iranyekan', sans-serif !important;
        }
        table.products-table th {
            background: rgba(72, 72, 72, 0.25);
            padding: 10px;
            border: 1px solid #000;
            font-family: 'iranyekan', sans-serif !important;
        }
        table.products-table td {
            border: 1px solid #000;
            padding: 10px;
            vertical-align: middle;
            font-family: 'iranyekan', sans-serif !important;
        }
        .total-table {
            width: 50%;
            margin-right: auto;
            margin-left: 0;
            border: 1px solid #000;
            border-collapse: collapse;
            text-align: center;
            margin-bottom: 20px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .total-table th, .total-table td {
            border: 1px solid #000;
            padding: 10px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .print-buttons {
            text-align: center;
            font-family: 'iranyekan', sans-serif !important;
        }
        .button {
            background: #FF6347;
            color: #FFF;
            text-decoration: none;
            display: inline-block;
            border-radius: 2px;
            padding: 5px 15px;
            margin: 0 5px;
            cursor: pointer;
            font-family: 'iranyekan', sans-serif !important;
        }
        .component .title {
            font-weight: bold;
            margin: 0 5px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .component .content {
            margin-left: 5px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .rtl .total-table {
            margin-right: 0;
            margin-left: auto;
        }
        th.total, th.shipping, th.final {
            background: #D1D1D1;
            font-family: 'iranyekan', sans-serif !important;
        }
        .signature-container {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin: 20px 20px 0 50px;
            font-family: 'iranyekan', sans-serif !important;
        }
        .signature-container .signature-label {
            margin-left: 10px; /* فاصله بین عنوان و تصویر */
            font-family: 'iranyekan', sans-serif !important;
        }
        .signature-container img {
            max-width: 100px;
            height: auto;
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
    <div class="template-1 container faktorak-scope">
        <table class="shop-info">
            <tbody>
                <tr>
                    <td>
                        <div class="component">
                            <span class="title">عنوان:</span> <?php echo esc_html($sender_name); ?>
                        </div>
                        <?php if ( ! empty( $sender_url ) ) : ?>
                        <div class="component">
                            <span class="title">وب‌سایت:</span> <?php echo esc_html($sender_url); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $sender_email ) ) : ?>
                        <div class="component">
                            <span class="title">ایمیل:</span> <?php echo esc_html($sender_email); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $sender_phone ) ) : ?>
                        <div class="component">
                            <span class="title">تلفن:</span> <?php echo esc_html($sender_phone); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="shop-logo">
                            <?php if ( ! empty( $logo_url ) ) : ?>
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="لوگو">
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="component">
                            <span class="title">تاریخ چاپ:</span> <?php echo esc_html($print_date); ?>
                        </div>
                        <div class="component">
                            <span class="title">شناسه سفارش:</span> <?php echo esc_html($order_id); ?>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">
                        <div class="address-postcode-container">
                            <?php if ( ! empty( $sender_address ) ) : ?>
                            <div class="component">
                                <span class="title">آدرس:</span> <?php echo esc_html($sender_address); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $sender_postcode ) ) : ?>
                            <div class="component">
                                <span class="title">کدپستی:</span> <?php echo esc_html($sender_postcode); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
        
        <div class="customer-info">
            <div class="component">
                <span class="title">گیرنده:</span> <?php echo esc_html($recipient_address); ?>
            </div>
            <div class="component">
                <span class="title">نام کامل:</span> <?php echo esc_html($recipient_name); ?>
            </div>
            <?php if ( ! empty( $recipient_postcode ) ) : ?>
            <div class="component">
                <span class="title">کدپستی:</span> <?php echo esc_html($recipient_postcode); ?>
            </div>
            <?php endif; ?>
            <?php if ( ! empty( $recipient_phone ) ) : ?>
            <div class="component">
                <span class="title">تلفن:</span> <?php echo esc_html($recipient_phone); ?>
            </div>
            <?php endif; ?>
            <div class="component">
                <span class="title">تاریخ سفارش:</span> <?php echo esc_html($order_date); ?>
            </div>
        </div>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th class="row">ردیف</th>
                    <th class="id">شناسه</th>
                    <th class="product">محصول</th>
                    <th class="price">قیمت</th>
                    <?php if ($show_tax_column) : ?>
                        <th class="tax-amount">مالیات</th>
                    <?php endif; ?>
                    <th class="quantity">تعداد</th>
                    <th class="total-amount">مبلغ کل</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $items = $order->get_items();
                $i = 1;
                foreach ($items as $item_id => $item) {
                    $product = $item->get_product();
                    $product_name = $item->get_name();
                    $product_id = $item->get_product_id();
                    $quantity = $item->get_quantity();
                    $price = $product ? wc_price($product->get_price()) : wc_price(0);
                    $tax_value = floatval($item->get_total_tax());
                    $tax = $tax_value > 0 ? wc_price($tax_value) : '';
                    $total = wc_price($item->get_total());
                    ?>
                    <tr class="<?php echo ($i % 2 == 0 ? 'even' : 'odd'); ?>">
                        <td class="row"><?php echo esc_html($i); ?></td>
                        <td class="id"><?php echo esc_html($product_id); ?></td>
                        <td class="product"><?php echo esc_html($product_name); ?></td>
                        <td class="price"><?php echo $price; ?></td>
                        <?php if ($show_tax_column) : ?>
                            <td class="tax-amount"><?php echo $tax; ?></td>
                        <?php endif; ?>
                        <td class="quantity"><?php echo esc_html($quantity); ?></td>
                        <td class="total-amount"><?php echo $total; ?></td>
                    </tr>
                    <?php
                    $i++;
                }
                ?>
            </tbody>
        </table>
        
        <div class="profit-wrapper">
            <div class="total-items">
                <span class="title">تعداد کل: </span>
                <span class="content"><?php echo count($items); ?></span>
            </div>
        </div>
        
        <table class="total-table">
            <tbody>
                <tr>
                    <th class="total">مبلغ کل</th>
                    <td class="total"><?php echo wc_price($order->get_subtotal()); ?></td>
                </tr>
                <?php if (floatval($order_total_tax) > 0) : ?>
                <tr>
                    <th class="tax">مالیات</th>
                    <td class="tax"><?php echo wc_price($order_total_tax); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th class="shipping">مبلغ حمل و نقل</th>
                    <td class="shipping"><?php echo esc_html($order->get_shipping_method() ?: 'رایگان'); ?></td>
                </tr>
                <tr>
                    <th class="final">مبلغ نهایی</th>
                    <td class="final"><?php echo wc_price($order->get_total()); ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ( $enable_signature === 'yes' && ! empty( $signature_url ) ) : ?>
        <div class="signature-container">
            <span class="signature-label">امضا:</span>
            <img src="<?php echo esc_url( $signature_url ); ?>" alt="امضا">
        </div>
        <?php endif; ?>
    </div>
    <div class="print-buttons">
        <a href="#" class="button" onclick="window.print()">چاپ این برگه</a>
        <a href="<?php echo esc_url($back_url); ?>" class="button">بازگشت</a>
    </div>
</body>
</html>
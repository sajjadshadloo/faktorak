<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order    = wc_get_order($order_id);
if ( ! $order ) { wp_die('سفارش یافت نشد.'); }

// پیش‌فاکتور یا فاکتور
$is_proforma  = ( isset($_GET['is_proforma']) && $_GET['is_proforma'] === 'true' );
$invoice_title = $is_proforma ? 'پیش فاکتور' : 'فاکتور';

// تنظیمات
$settings         = new ShippingInvoiceSettings();
$logo_url         = $settings->get_setting('logo_url');
$sender_name      = $settings->get_setting('sender_name');
$sender_address   = $settings->get_setting('sender_address');
$sender_postcode  = $settings->get_setting('sender_postcode');
$sender_phone     = $settings->get_setting('sender_phone');
$sender_email     = $settings->get_setting('sender_email');
$sender_url       = $settings->get_setting('sender_url');
$signature_url    = $settings->get_setting('signature_url');
$enable_signature = $settings->get_setting('enable_signature');

// گیرنده و سفارش
$recipient_name     = $order->get_formatted_billing_full_name();
$recipient_address  = $order->get_billing_address_1()
                        . ( $order->get_billing_address_2() ? '، ' . $order->get_billing_address_2() : '' )
                        . ( $order->get_billing_city() ? '، ' . $order->get_billing_city() : '' );
$recipient_postcode = $order->get_billing_postcode();
$recipient_phone    = $order->get_billing_phone();
$order_date         = $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'Y-m-d H:i' ) : '';
$print_date         = wc_format_datetime( new WC_DateTime(), 'Y-m-d H:i' );

// back URL
$context  = isset($_GET['context']) ? sanitize_text_field($_GET['context']) : '';
$back_url = home_url();
if ( $context === 'admin' ) {
    $back_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
} elseif ( $context === 'user' ) {
    $back_url = wc_get_endpoint_url( 'view-order', $order_id, wc_get_account_endpoint_url( 'orders' ) );
} elseif ( $context === 'proforma' ) {
    $back_url = wc_get_cart_url();
}

// ستون مالیات؟
$show_tax_column = floatval($order->get_total_tax()) > 0;

// اقلام
$items = $order->get_items();
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa-IR">
<head>
  <title><?php echo esc_html($invoice_title); ?> (<?php echo esc_html($order_id); ?>)</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <link rel="stylesheet" href="<?php echo esc_url( plugin_dir_url(__FILE__) . '../assets/css/custom-fonts.css' ); ?>" type="text/css" />

  <style>
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;font-size:14px;color:#000;line-height:1.8;font-family:'iranyekan',sans-serif !important}

    .container{width:95%;margin:20px auto;padding:10px;border:1px solid transparent}

    /* هدر سه‌ستونه */
    .header-grid{
      display:grid;
      grid-template-columns:1fr auto 1fr;
      align-items:center;
      gap:16px;
      padding:10px 0 16px;
      border-bottom:1px solid #e5e7eb;
      margin-bottom:16px;
    }
    .shop-meta{
      text-align:right;
      line-height:1.9;
    }
    .shop-meta .line{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
    .shop-name{font-size:18px;font-weight:700;margin:0 0 4px}
    .muted{color:#6b7280;font-size:12px}

    /* لوگو کاملاً منعطف برای هر نسبت تصویر */
    .logo-box{
      width:120px;
      height:90px;           /* نسبت منطقی برای اغلب لوگوها */
      border:1px solid #e5e7eb;
      border-radius:12px;
      background:#fff;
      display:flex;align-items:center;justify-content:center;
      margin:0 auto;
      overflow:hidden;       /* جلوگیری از بیرون‌زدگی */
    }
    .logo-box img{
      max-width:100%;
      max-height:100%;
      object-fit:contain;    /* بدون کشیدگی */
      display:block;
    }

    .order-meta{
      text-align:left;
      line-height:1.9;
    }
    .order-title{font-weight:700;font-size:16px;margin:0 0 4px}
    .badge{
      display:inline-block;
      background:#f3f4f6;border:1px dashed #e5e7eb;
      color:#6b7280;padding:4px 8px;border-radius:10px;font-size:12px;margin-left:6px
    }

    .invoice-title{text-align:center;font-size:22px;font-weight:800;margin:10px 0 20px}

    table.shop-info{
      width:100%;table-layout:fixed;border-collapse:collapse;margin:0 auto;text-align:center;border:1px solid #e5e5e5
    }
    table.shop-info td{width:33.3333%;vertical-align:middle;padding:10px;text-align:center}
    .shop-info tfoot td{background:e5e5e5}
    .address-postcode-container{display:flex;justify-content:center;align-items:center;gap:10px;padding:15px}

    .customer-info{
      border:1px solid #000;padding:15px;background:rgba(72,72,72,.25);
      margin:20px 0;display:flex;flex-wrap:wrap;gap:15px
    }
    .component .title{font-weight:700;margin:0 5px}
    .component .content{margin-left:5px}

    table.products-table{width:100%;border-collapse:collapse;margin-bottom:20px;text-align:center}
    table.products-table th{background:rgba(72,72,72,.25);padding:10px;border:1px solid #000}
    table.products-table td{border:1px solid #000;padding:10px;vertical-align:middle}

    .total-table{
      width:50%;
      margin-right:auto;margin-left:0;
      border:1px solid #000;border-collapse:collapse;text-align:center;margin-bottom:20px
    }
    .total-table th,.total-table td{border:1px solid #000;padding:10px}
    .rtl .total-table{margin-right:0;margin-left:auto}
    th.total,th.shipping,th.final{background:#d1d1d1}

    .signature-container{display:flex;align-items:center;justify-content:flex-end;margin:20px 20px 0 50px}
    .signature-label{margin-left:10px}
    .signature-container img{max-width:100px;height:auto}

    .print-buttons{text-align:center}
    .button{background:#FF6347;color:#fff;text-decoration:none;display:inline-block;border-radius:6px;padding:7px 16px;margin:0 5px;cursor:pointer;border:0}

    /* ریسپانسیوِ نمایش */
    @media screen and (max-width:768px){
      .container{width:100%;margin:10px auto;padding:5px}
      .header-grid{grid-template-columns:1fr;gap:10px;text-align:center}
      .shop-meta,.order-meta{text-align:center}
      .logo-box{margin:0 auto}
      table.shop-info td{display:block;width:100%;text-align:right}
      .address-postcode-container{flex-direction:column;align-items:flex-start}
      .customer-info{flex-direction:column}
      .products-table thead{display:none}
      .products-table,.products-table tbody,.products-table tr,.products-table td{display:block;width:100%}
      .products-table tr{margin-bottom:15px;border:1px solid #000}
      .products-table td{text-align:left;padding-right:50%;position:relative}
      .products-table td:before{
        content:attr(data-label);
        position:absolute;right:10px;width:45%;font-weight:700;text-align:right
      }
      .total-table{width:100%}
    }

    /* چاپ */
    @media print{
      *{-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important}
      .print-buttons{display:none !important}
      .container{border:none;margin:0 auto;width:100%}
    }
  </style>
</head>
<body class="faktorak-scope">
  <div class="template-1 container faktorak-scope">

    <!-- هدر جدید: سه ستونه -->
    <div class="header-grid">
      <!-- اطلاعات فروشگاه -->
      <div class="shop-meta">
        <h1 class="shop-name"><?php echo esc_html( $sender_name ); ?></h1>
        <?php if ( $sender_url ) : ?>
          <div class="line"><span class="title">وب‌سایت:</span><span class="muted"><?php echo esc_html( $sender_url ); ?></span></div>
        <?php endif; ?>
        <?php if ( $sender_email ) : ?>
          <div class="line"><span class="title">ایمیل:</span><span class="muted"><?php echo esc_html( $sender_email ); ?></span></div>
        <?php endif; ?>
        <?php if ( $sender_phone ) : ?>
          <div class="line"><span class="title">تلفن:</span><span class="muted"><?php echo esc_html( $sender_phone ); ?></span></div>
        <?php endif; ?>
      </div>

      <!-- لوگو منعطف -->
      <div class="logo-box">
        <?php if ( $logo_url ) : ?>
          <img src="<?php echo esc_url( $logo_url ); ?>" alt="لوگوی فروشگاه">
        <?php endif; ?>
      </div>

      <!-- متادیتای سفارش -->
      <div class="order-meta">
        <div class="order-title">
          <?php echo esc_html( $invoice_title ); ?>
          <span class="badge">شماره: <?php echo esc_html( $order_id ); ?></span>
        </div>
        <div class="muted">تاریخ سفارش: <?php echo esc_html( $order_date ); ?></div>
        <div class="muted">تاریخ چاپ: <?php echo esc_html( $print_date ); ?></div>
      </div>
    </div>



    <!-- اطلاعات مشتری -->
    <div class="customer-info">
      <div class="component"><span class="title">گیرنده:</span> <span class="content"><?php echo esc_html($recipient_address); ?></span></div>
      <div class="component"><span class="title">نام کامل:</span> <span class="content"><?php echo esc_html($recipient_name); ?></span></div>
      <?php if ( $recipient_postcode ) : ?>
        <div class="component"><span class="title">کدپستی:</span> <span class="content"><?php echo esc_html($recipient_postcode); ?></span></div>
      <?php endif; ?>
      <?php if ( $recipient_phone ) : ?>
        <div class="component"><span class="title">تلفن:</span> <span class="content"><?php echo esc_html($recipient_phone); ?></span></div>
      <?php endif; ?>
      <div class="component"><span class="title">تاریخ سفارش:</span> <span class="content"><?php echo esc_html($order_date); ?></span></div>
    </div>

    <!-- جدول اقلام -->
    <table class="products-table">
      <thead>
        <tr>
          <th class="row">ردیف</th>
          <th class="id">شناسه</th>
          <th class="product">محصول</th>
          <th class="price">قیمت</th>
          <?php if ( $show_tax_column ): ?>
            <th class="tax-amount">مالیات</th>
          <?php endif; ?>
          <th class="quantity">تعداد</th>
          <th class="total-amount">مبلغ کل</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $i=1;
        foreach ( $items as $item_id => $item ) {
          $product      = $item->get_product();
          $product_name = $item->get_name();
          $product_id   = $item->get_product_id();
          $quantity     = $item->get_quantity();

          // نمایش قیمت «محصول» به صورت واحد (از خود محصول) — مثل قبل
          $price        = $product ? wc_price($product->get_price()) : wc_price(0);

          // مالیات و جمع خط
          $tax_value    = floatval($item->get_total_tax());
          $tax          = $tax_value > 0 ? wc_price($tax_value) : '';
          $total        = wc_price($item->get_total());
          ?>
          <tr class="<?php echo ($i % 2 === 0) ? 'even' : 'odd'; ?>">
            <td data-label="ردیف"><?php echo esc_html($i); ?></td>
            <td data-label="شناسه"><?php echo esc_html($product_id); ?></td>
            <td data-label="محصول"><?php echo esc_html($product_name); ?></td>
            <td data-label="قیمت"><?php echo $price; ?></td>
            <?php if ( $show_tax_column ): ?>
              <td data-label="مالیات"><?php echo $tax; ?></td>
            <?php endif; ?>
            <td data-label="تعداد"><?php echo esc_html($quantity); ?></td>
            <td data-label="مبلغ کل"><?php echo $total; ?></td>
          </tr>
          <?php
          $i++;
        } ?>
      </tbody>
    </table>

    <div class="profit-wrapper" style="margin:10px 0 16px">
      <div class="total-items"><span class="title">تعداد کل:</span> <span class="content"><?php echo count($items); ?></span></div>
    </div>

    <!-- جمع‌ها -->
    <table class="total-table">
      <tbody>
        <tr>
          <th class="total">مبلغ کل</th>
          <td class="total"><?php echo wc_price($order->get_subtotal()); ?></td>
        </tr>
        <?php if ( $show_tax_column ): ?>
          <tr>
            <th class="tax">مالیات</th>
            <td class="tax"><?php echo wc_price($order->get_total_tax()); ?></td>
          </tr>
        <?php endif; ?>
        <tr>
          <th class="shipping">هزینه ارسال</th>
          <td class="shipping">
            <?php
              // حفظ سازگاری: اگر عدد هزینه صفر بود، روش را نمایش بده
              $ship_total = floatval($order->get_shipping_total());
              echo $ship_total > 0 ? wc_price($ship_total) : esc_html( $order->get_shipping_method() ?: 'رایگان' );
            ?>
          </td>
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

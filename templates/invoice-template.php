<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order    = wc_get_order($order_id);
if ( ! $order ) { wp_die( esc_html__( 'سفارش یافت نشد.', 'faktorak' ) ); }

// پیش‌فاکتور یا فاکتور
$is_proforma  = ( isset($_GET['is_proforma']) && $_GET['is_proforma'] === 'true' );
if ( ! $is_proforma ) {
    $is_proforma = $order->has_status( 'proforma-invoice' ) || 'proforma' === $order->get_meta( '_faktorak_doc_type' );
}
$invoice_title = $is_proforma ? 'پیش فاکتور' : 'فاکتور';

// تنظیمات
$settings         = new Faktorak_Shipping_Invoice_Settings();
$logo_url         = $settings->get_setting('logo_url');
$sender_name      = $settings->get_setting('sender_name');
$sender_address   = $settings->get_setting('sender_address');
$sender_postcode  = $settings->get_setting('sender_postcode');
$sender_phone     = $settings->get_setting('sender_phone');
$sender_email     = $settings->get_setting('sender_email');
$sender_url       = $settings->get_setting('sender_url');
$signature_url    = $settings->get_setting('signature_url');
$enable_signature = $settings->get_setting('enable_signature');
$admin_note       = trim( (string) $settings->get_setting( 'admin_note' ) );
$customer_note    = trim( (string) $order->get_customer_note() );

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
$payment_url = '';
if ( ! $order->is_paid() ) {
    $payment_url = faktorak_get_order_payment_url( $order_id, $context ? $context : 'frontend' );
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

  <?php wp_print_styles( array( 'faktorak-custom-fonts', 'faktorak-invoice-classic' ) ); ?>
  <?php wp_print_head_scripts(); ?>
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
          <img class="faktorak-no-lazy no-lazy" src="<?php echo esc_url( $logo_url ); ?>" alt="لوگوی فروشگاه" loading="eager" decoding="sync" fetchpriority="high" data-no-lazy="1">
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
    <div class="products-table-wrap">
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
          <tr class="<?php echo esc_attr( ($i % 2 === 0) ? 'even' : 'odd' ); ?>">
            <td data-label="ردیف"><?php echo esc_html($i); ?></td>
            <td data-label="شناسه"><?php echo esc_html($product_id); ?></td>
            <td data-label="محصول"><?php echo esc_html($product_name); ?></td>
            <td data-label="قیمت"><?php echo wp_kses_post( $price ); ?></td>
            <?php if ( $show_tax_column ): ?>
              <td data-label="مالیات"><?php echo wp_kses_post( $tax ); ?></td>
            <?php endif; ?>
            <td data-label="تعداد"><?php echo esc_html($quantity); ?></td>
            <td data-label="مبلغ کل"><?php echo wp_kses_post( $total ); ?></td>
          </tr>
          <?php
          $i++;
        } ?>
      </tbody>
    </table>
    </div>

    <div class="profit-wrapper" style="margin:10px 0 16px">
      <div class="total-items"><span class="title">تعداد کل:</span> <span class="content"><?php echo esc_html( (string) count($items) ); ?></span></div>
    </div>

    <!-- جمع‌ها -->
    <table class="total-table">
      <tbody>
        <tr>
          <th class="total">مبلغ کل</th>
          <td class="total"><?php echo wp_kses_post( wc_price( $order->get_subtotal() ) ); ?></td>
        </tr>
        <?php if ( $show_tax_column ): ?>
          <tr>
            <th class="tax">مالیات</th>
            <td class="tax"><?php echo wp_kses_post( wc_price( $order->get_total_tax() ) ); ?></td>
          </tr>
        <?php endif; ?>
        <tr>
          <th class="shipping">هزینه ارسال</th>
          <td class="shipping">
            <?php
              // حفظ سازگاری: اگر عدد هزینه صفر بود، روش را نمایش بده
              $ship_total = floatval($order->get_shipping_total());
              if ( $ship_total > 0 ) {
                  echo wp_kses_post( wc_price( $ship_total ) );
              } else {
                  echo esc_html( $order->get_shipping_method() ?: 'رایگان' );
              }
            ?>
          </td>
        </tr>
        <tr>
          <th class="final">مبلغ نهایی</th>
          <td class="final"><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></td>
        </tr>
      </tbody>
    </table>

    <?php if ( $admin_note || $customer_note ) : ?>
      <div class="faktorak-invoice-notes">
        <?php if ( $admin_note ) : ?>
          <p><strong>یادداشت فروشنده:</strong> <?php echo wp_kses_post( nl2br( esc_html( $admin_note ) ) ); ?></p>
        <?php endif; ?>
        <?php if ( $customer_note ) : ?>
          <p><strong>یادداشت مشتری:</strong> <?php echo wp_kses_post( nl2br( esc_html( $customer_note ) ) ); ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ( $enable_signature === 'yes' && ! empty( $signature_url ) ) : ?>
      <div class="signature-container">
        <span class="signature-label">امضا:</span>
        <img class="faktorak-no-lazy no-lazy" src="<?php echo esc_url( $signature_url ); ?>" alt="امضا" loading="eager" decoding="sync" data-no-lazy="1">
      </div>
    <?php endif; ?>

  </div>

  <div class="print-buttons">
    <a href="#" class="button" onclick="window.print()">چاپ این برگه</a>
    <?php if ( $payment_url ) : ?>
      <a href="<?php echo esc_url($payment_url); ?>" class="button">پرداخت</a>
    <?php endif; ?>
    <a href="<?php echo esc_url($back_url); ?>" class="button">بازگشت</a>
  </div>
  <?php wp_print_footer_scripts(); ?>
</body>
</html>

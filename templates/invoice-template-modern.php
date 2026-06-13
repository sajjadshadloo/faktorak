<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** دریافت سفارش از querystring */
$order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
$order    = $order_id ? wc_get_order($order_id) : null;
if ( ! $order ) {
    wp_die( esc_html__('سفارش پیدا نشد.', 'faktorak') );
}

$settings = new Faktorak_Shipping_Invoice_Settings();

/** اطلاعات فروشگاه از تنظیمات افزونه */
$store = array(
    'logo'     => $settings->get_setting('logo_url'),
    'name'     => $settings->get_setting('sender_name') ?: get_bloginfo('name'),
    'address'  => $settings->get_setting('sender_address'),
    'postcode' => $settings->get_setting('sender_postcode'),
    'phone'    => $settings->get_setting('sender_phone'),
    'email'    => $settings->get_setting('sender_email'),
    'url'      => $settings->get_setting('sender_url'),
);

/** نمایشِ «فاکتور فروش / پیش‌فاکتور» */
$is_proforma = ( isset($_GET['is_proforma']) && $_GET['is_proforma'] === 'true' );
if ( ! $is_proforma ) {
    $is_proforma = $order->has_status( 'proforma-invoice' ) || 'proforma' === $order->get_meta( '_faktorak_doc_type' );
}
$badge_text  = $is_proforma ? 'پیش‌فاکتور' : 'فاکتور فروش';

/** متادیتای سفارش */
$order_number  = $order->get_order_number();
$order_date    = wc_format_datetime( $order->get_date_created(), 'Y/m/d' );
$status_string = $order->is_paid() ? 'پرداخت‌شده' : 'در انتظار پرداخت';

$billing_name  = trim( $order->get_formatted_billing_full_name() );
$billing_phone = $order->get_billing_phone();
$billing_email = $order->get_billing_email();
$shipping_addr = $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address();
$pay_method    = $order->get_payment_method_title() ?: '—';
$ship_method   = $order->get_shipping_method() ?: '—';

/** محاسبات جمع‌ها */
$subtotal   = 0;
$discount   = 0;
foreach ( $order->get_items() as $it ) {
    $subtotal += (float) $it->get_subtotal();
    $discount += (float) $it->get_subtotal() - (float) $it->get_total();
}
$tax_total  = (float) $order->get_total_tax();
$shipping_t = (float) $order->get_shipping_total();
$total      = (float) $order->get_total();

/** QR برای صفحه مشاهده سفارش */
$view_url = wc_get_endpoint_url( 'view-order', $order_id, wc_get_page_permalink('myaccount') );
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa-IR">
<head>
  <title><?php echo esc_html( $badge_text ); ?> (<?php echo esc_html( $order_number ); ?>)</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php wp_print_styles( array( 'faktorak-custom-fonts', 'faktorak-invoice-modern' ) ); ?>
  <?php wp_print_head_scripts(); ?>
</head>
<body class="faktorak-scope">
<div class="faktorak-scope">
  <div class="faktorak-invoice" dir="rtl">
    <!-- اکشن‌ها (نمایش؛ در چاپ مخفی) -->
    <div class="fak-actions">
      <button onclick="window.print()" class="fak-btn">چاپ فاکتور</button>
      <?php
      $context  = isset($_GET['context']) ? sanitize_text_field(wp_unslash($_GET['context'])) : '';
      $referrer = isset($_GET['referrer']) ? wp_unslash($_GET['referrer']) : '';
      $payment_url = '';
      if ( ! $order->is_paid() ) {
          $payment_url = faktorak_get_order_payment_url( $order_id, $context ? $context : 'frontend' );
      }
      if ($context === 'admin') {
          $back_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
      } elseif ($referrer) {
          $back_url = $referrer;
      } else {
          $back_url = wc_get_endpoint_url( 'view-order', $order_id, wc_get_page_permalink('myaccount') );
      }
      ?>
      <?php if ( $payment_url ) : ?>
      <a href="<?php echo esc_url($payment_url); ?>" class="fak-btn">پرداخت</a>
      <?php endif; ?>
      <a href="<?php echo esc_url($back_url); ?>" class="fak-btn outline">بازگشت</a>
    </div>

    <!-- هدر -->
    <div class="fak-header">
      <div class="fak-brand">
        <?php if ( ! empty($store['logo']) ): ?>
          <img class="fak-logo faktorak-no-lazy no-lazy" src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>" loading="eager" decoding="sync" fetchpriority="high" data-no-lazy="1">
        <?php else: ?>
          <div class="fak-logo" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:12px">LOGO</div>
        <?php endif; ?>
        <div class="fak-store">
          <h1><?php echo esc_html( $store['name'] ); ?></h1>
          <div class="meta">
            <?php
              $host = $store['url'] ? wp_parse_url($store['url'], PHP_URL_HOST) : '';
              $line1 = array_filter([$store['address']]);
              $line2 = array_filter([
                  $store['postcode'] ? 'کدپستی ' . $store['postcode'] : '',
                  $store['phone'],
                  $store['email'],
                  $host
              ]);
              echo esc_html( implode(' | ', $line1) );
              if ( $line1 ) {
                  echo '<br>';
              }
              echo esc_html( implode(' | ', $line2) );
            ?>
          </div>
        </div>
      </div>
      <div class="fak-invoice-id">
        <div class="badge"><?php echo esc_html($badge_text); ?></div>
        <h2># <?php echo esc_html( $order_number ); ?></h2>
        <div class="meta">تاریخ: <?php echo esc_html( $order_date ); ?> &nbsp; | &nbsp; وضعیت پرداخت: <?php echo esc_html( $status_string ); ?></div>
      </div>
    </div>

    <div class="fak-brand-hr"></div>

    <!-- کارت‌ها -->
    <div class="fak-cards">
      <div class="fak-card">
        <h3>اطلاعات خریدار</h3>
        <div class="row">نام: <?php echo esc_html( $billing_name ?: '—' ); ?></div>
        <div class="row">موبایل: <?php echo esc_html( $billing_phone ?: '—' ); ?></div>
        <div class="row">ایمیل: <?php echo esc_html( $billing_email ?: '—' ); ?></div>
      </div>
      <div class="fak-card">
        <h3>آدرس ارسال</h3>
        <div class="row"><?php echo wp_kses_post( nl2br( $shipping_addr ?: '—' ) ); ?></div>
      </div>
      <div class="fak-card">
        <h3>خلاصه سفارش</h3>
        <div class="row">روش پرداخت: <?php echo esc_html( $pay_method ); ?></div>
        <div class="row">روش ارسال: <?php echo esc_html( $ship_method ); ?></div>
        <div class="row">تاریخ ثبت: <?php echo esc_html( $order_date ); ?></div>
      </div>
    </div>

    <!-- یادداشت و امضا -->
    <div class="fak-summary">
      <div class="fak-note">
        <?php
        $admin_note    = trim( (string) $settings->get_setting('admin_note') );
        $customer_note = trim( (string) $order->get_customer_note() );
        ?>
        <strong>یادداشت فروشنده:</strong>
        <div>
          <?php
          if ( $admin_note ) {
              echo wp_kses_post( nl2br( esc_html( $admin_note ) ) );
          } else {
              echo 'از خرید شما سپاسگزاریم.';
          }
          ?>
        </div>

        <?php if ( $customer_note ) : ?>
          <hr style="border:none;border-top:1px dashed #d1d5db;margin:10px 0;">
          <strong>یادداشت مشتری:</strong>
          <div><?php echo wp_kses_post( nl2br( esc_html( $customer_note ) ) ); ?></div>
        <?php endif; ?>
      </div>
      <div class="fak-stamp">
        <?php
        $signature_url    = $settings->get_setting('signature_url');
        $enable_signature = ($settings->get_setting('enable_signature') === 'yes');
        if ( $enable_signature && ! empty($signature_url) ): ?>
          <img class="faktorak-no-lazy no-lazy" src="<?php echo esc_url( $signature_url ); ?>" alt="امضا/مهر" loading="eager" decoding="sync" data-no-lazy="1">
          <div class="cap">امضا و مهر فروشنده</div>
        <?php else: ?>
          <div style="color:#9ca3af;font-size:12px;padding:22px 0">بدون امضا</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- جدول اقلام -->
    <div class="fak-items-wrap">
    <table class="fak-items">
      <thead>
        <tr>
          <th style="width:40%">شرح کالا</th>
          <th>تعداد</th>
          <th>قیمت واحد</th>
          <th>تخفیف</th>
          <th>مالیات</th>
          <th>قیمت کل</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( $order->get_items() as $item_id => $item ):
          $name       = $item->get_name();
          $qty        = max(1, (int) $item->get_quantity());
          $unit_price = (float) $item->get_subtotal() / $qty;
          $line_total = (float) $item->get_total();
          $line_tax   = (float) $item->get_total_tax();
          $line_disc  = (float) $item->get_subtotal() - (float) $item->get_total();
          $meta_html  = wc_display_item_meta( $item, array( 'echo' => false, 'separator' => ' | ' ) );
        ?>
        <tr>
          <td class="text-right">
            <?php echo esc_html( $name ); ?>
            <?php if ( $meta_html ): ?>
              <div style="color:var(--fak-muted); font-size:12px; margin-top:4px"><?php echo wp_kses_post( $meta_html ); ?></div>
            <?php endif; ?>
          </td>
          <td><?php echo esc_html( wc_format_decimal($qty, 0) ); ?></td>
          <td><?php echo wp_kses_post( wc_price($unit_price, array('currency' => $order->get_currency())) ); ?></td>
          <td>
            <?php
            if ( $line_disc ) {
                echo wp_kses_post( wc_price( $line_disc, array( 'currency' => $order->get_currency() ) ) );
            } else {
                echo '0';
            }
            ?>
          </td>
          <td>
            <?php
            if ( $line_tax ) {
                echo wp_kses_post( wc_price( $line_tax, array( 'currency' => $order->get_currency() ) ) );
            } else {
                echo '0';
            }
            ?>
          </td>
          <td><?php echo wp_kses_post( wc_price($line_total + $line_tax, array('currency' => $order->get_currency())) ); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <!-- جمع کل -->
    <div class="fak-total">
      <div class="spacer"></div>
      <div class="box">
        <div class="row"><div>جمع جزء</div><div><?php echo wp_kses_post( wc_price($subtotal, array('currency'=>$order->get_currency())) ); ?></div></div>
        <div class="row"><div>تخفیف</div><div><?php echo wp_kses_post( wc_price($discount, array('currency'=>$order->get_currency())) ); ?></div></div>
        <div class="row"><div>مالیات</div><div><?php echo wp_kses_post( wc_price($tax_total, array('currency'=>$order->get_currency())) ); ?></div></div>
        <div class="row"><div>هزینه ارسال</div><div><?php echo wp_kses_post( wc_price($shipping_t, array('currency'=>$order->get_currency())) ); ?></div></div>
        <div class="row total"><div>مبلغ قابل پرداخت</div><div><?php echo wp_kses_post( wc_price($total, array('currency'=>$order->get_currency())) ); ?></div></div>
      </div>
    </div>

    <!-- فوتر -->
    <div class="fak-footer">
      <div class="fak-qr">
        <div class="faktorak-qr" data-qr="<?php echo esc_attr( esc_url( $view_url ) ); ?>" data-qr-size="92"></div>
        <div>
          <div style="font-weight:600; margin-bottom:6px">پیگیری سفارش</div>
          <div class="meta">برای مشاهده جزئیات سفارش، QR را اسکن کنید یا به لینک سفارش مراجعه کنید.</div>
        </div>
      </div>
      <div class="fak-thanks">
        از خرید شما سپاسگزاریم 🌿<br>
        هر پرسشی داشتید با پشتیبانی تماس بگیرید:
        <?php echo esc_html( $store['phone'] ?: '—' ); ?>
      </div>
    </div>
  </div>
</div>
<?php wp_print_footer_scripts(); ?>
</body>
</html>

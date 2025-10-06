<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** دریافت سفارش از querystring */
$order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
$order    = $order_id ? wc_get_order($order_id) : null;
if ( ! $order ) {
    wp_die( esc_html__('سفارش پیدا نشد.', 'Factork') );
}

$settings = new ShippingInvoiceSettings();

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
$qr_src   = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode( $view_url );
?>
<div class="faktorak-scope">
  <div class="faktorak-invoice" dir="rtl">
    <!-- فونت افزونه -->
    <link rel="stylesheet" href="<?php echo esc_url( plugin_dir_url(__FILE__) . '../assets/css/custom-fonts.css' ); ?>" type="text/css" />

    <style>
      .faktorak-invoice{
        --fak-primary:#2563eb;
        --fak-border:#e5e7eb;
        --fak-text:#111827;
        --fak-muted:#6b7280;
        --fak-bg:#ffffff;
        --fak-soft:#f9fafb;
        font-family: 'iranyekan', sans-serif;
        color: var(--fak-text);
        background: var(--fak-bg);
        max-width: 900px;
        margin: 24px auto;
        padding: 24px;
        border: 1px solid var(--fak-border);
        border-radius: 14px;
      }
      .fak-header{
        display: grid;
        grid-template-columns: 1.2fr .8fr;
        gap: 16px;
        align-items: center;
        border-bottom:1px solid var(--fak-border);
        padding-bottom:16px;
        margin-bottom:16px;
      }
      .fak-brand{display:flex; gap:16px; align-items:center}
/* لوگو: نمایش درست برای همه‌ی نسبت‌ها و شکل‌ها */
.fak-logo{
  width: 72px;
  height: 72px;
  /* داخل یک کادر ثابت، بدون بُرش */
  object-fit: contain;       /* قبلاً cover بود و لوگو رو می‌برید */
  background: #fff;
  border: 1px solid var(--fak-border);
  border-radius: 12px;       /* گوشه‌های نرمِ کادر */
  padding: 6px;              /* نفس برای لوگوهای کشیده/طویل */
  display: block;            /* جلوگیری از فاصله‌های ناخواسته */
}      .fak-store h1{font-size:20px; margin:0 0 6px}
      .fak-store .meta{font-size:12px; color:var(--fak-muted); line-height:1.8}
      .fak-invoice-id{text-align:left;}
      .fak-invoice-id .badge{display:inline-block;background:var(--fak-soft);border:1px dashed var(--fak-border);color:var(--fak-muted); padding:6px 10px; border-radius:10px; font-size:12px; margin-bottom:8px}
      .fak-invoice-id h2{margin:0 0 4px; font-size:22px}
      .fak-invoice-id .meta{font-size:12px; color:var(--fak-muted)}
      .fak-cards{display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:16px;}
      .fak-card{border:1px solid var(--fak-border); border-radius:12px; padding:14px; background:#fff;}
      .fak-card h3{margin:0 0 10px; font-size:14px; color:var(--fak-muted)}
      .fak-card .row{font-size:13px; line-height:1.9}
      .fak-summary{display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:16px;}
      .fak-note{border:1px solid var(--fak-border); border-radius:12px; background:var(--fak-soft); padding:14px; font-size:13px; color:#374151;}
      .fak-stamp{border:1px dashed var(--fak-border); border-radius:12px; padding:12px; text-align:center; background:#fff;}
      .fak-stamp img{max-height:70px; object-fit:contain; display:block; margin:0 auto 6px}
      .fak-stamp .cap{font-size:12px; color:var(--fak-muted)}
      table.fak-items{width:100%; border-collapse:separate; border-spacing:0; overflow:hidden; border:1px solid var(--fak-border); border-radius:12px; background:#fff;}
      .fak-items thead th{background:var(--fak-soft); font-weight:600; font-size:13px; text-align:center; padding:12px; border-bottom:1px solid var(--fak-border);}
      .fak-items tbody td{font-size:13px; padding:12px; border-bottom:1px solid var(--fak-border); text-align:center; vertical-align:top;}
      .fak-items tbody tr:last-child td{border-bottom:none}
      .fak-items .text-right{text-align:right}
      .fak-total{display:grid; grid-template-columns:1fr 360px; gap:16px; margin-top:16px; align-items:start;}
      .fak-total .spacer{height:1px}
      .fak-total .box{border:1px solid var(--fak-border); border-radius:12px; padding:0; overflow:hidden; background:#fff;}
      .fak-total .box .row{display:grid; grid-template-columns:1fr auto; gap:12px; padding:12px 14px; font-size:14px; border-bottom:1px solid var(--fak-border);}
      .fak-total .box .row:last-child{border-bottom:none}
      .fak-total .box .row.total{background:var(--fak-soft); font-weight:700}
      .fak-footer{display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:16px; align-items:center;}
      .fak-qr{display:flex; align-items:center; gap:12px; border:1px dashed var(--fak-border); border-radius:12px; padding:12px;}
      .fak-qr img{width:92px; height:92px; border-radius:8px; background:#fff}
      .fak-qr .meta{font-size:12px; color:var(--fak-muted)}
      .fak-thanks{text-align:left; color:var(--fak-muted); font-size:12px;}
      .fak-brand-hr{height:4px; background:linear-gradient(90deg, var(--fak-primary), #60a5fa); border-radius:999px; margin:8px 0 16px 0;}
      .fak-actions{display:flex; gap:8px; justify-content:flex-start; margin-bottom:12px}
      .fak-btn{display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; background:var(--fak-primary); color:#fff; font-size:13px; border:0; cursor:pointer}
      .fak-btn.outline{background:#fff; color:var(--fak-primary); border:1px solid var(--fak-primary)}
      @media print{
        .fak-actions{display:none !important;}
        .faktorak-invoice{box-shadow:none; border:0; margin:0; padding:0;}
        @page{ size:A4; margin:10mm; }
      }
    </style>

    <!-- اکشن‌ها (نمایش؛ در چاپ مخفی) -->
    <div class="fak-actions">
      <button onclick="window.print()" class="fak-btn">چاپ فاکتور</button>
      <?php
      $context  = isset($_GET['context']) ? sanitize_text_field(wp_unslash($_GET['context'])) : '';
      $referrer = isset($_GET['referrer']) ? esc_url_raw(wp_unslash($_GET['referrer'])) : '';
      if ($context === 'admin') {
          $back_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
      } elseif ($referrer) {
          $back_url = $referrer;
      } else {
          $back_url = wc_get_endpoint_url( 'view-order', $order_id, wc_get_page_permalink('myaccount') );
      }
      ?>
      <a href="<?php echo esc_url($back_url); ?>" class="fak-btn outline">بازگشت</a>
    </div>

    <!-- هدر -->
    <div class="fak-header">
      <div class="fak-brand">
        <?php if ( ! empty($store['logo']) ): ?>
          <img class="fak-logo" src="<?php echo esc_url($store['logo']); ?>" alt="<?php echo esc_attr($store['name']); ?>">
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
              echo $line1 ? '<br>' : '';
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
        <strong>یادداشت فروشنده:</strong>
        <div>
          <?php
          $customer_note = trim( $order->get_customer_note() );
          echo $customer_note ? esc_html( $customer_note ) : 'از خرید شما سپاسگزاریم.';
          ?>
        </div>
      </div>
      <div class="fak-stamp">
        <?php
        $signature_url    = $settings->get_setting('signature_url');
        $enable_signature = ($settings->get_setting('enable_signature') === 'yes');
        if ( $enable_signature && ! empty($signature_url) ): ?>
          <img src="<?php echo esc_url( $signature_url ); ?>" alt="امضا/مهر">
          <div class="cap">امضا و مهر فروشنده</div>
        <?php else: ?>
          <div style="color:#9ca3af;font-size:12px;padding:22px 0">بدون امضا</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- جدول اقلام -->
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
          <td><?php echo $line_disc ? wp_kses_post( wc_price($line_disc, array('currency'=>$order->get_currency())) ) : '0'; ?></td>
          <td><?php echo $line_tax ? wp_kses_post( wc_price($line_tax, array('currency'=>$order->get_currency())) ) : '0'; ?></td>
          <td><?php echo wp_kses_post( wc_price($line_total + $line_tax, array('currency' => $order->get_currency())) ); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

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
        <img src="<?php echo esc_url( $qr_src ); ?>" alt="QR">
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

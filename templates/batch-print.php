<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa-IR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo esc_html( $batch_title ); ?></title>
  <?php wp_print_styles( $batch_style_handles ); ?>
  <?php wp_print_head_scripts(); ?>
  <style>
    :root {
      --faktorak-batch-bg: #f1f5f9;
      --faktorak-batch-card: #ffffff;
      --faktorak-batch-border: #d6e2f0;
      --faktorak-batch-title: #0f172a;
      --faktorak-batch-muted: #475569;
      --faktorak-batch-primary: #1d4ed8;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "iranyekan", Tahoma, sans-serif;
      background: var(--faktorak-batch-bg);
      color: #0f172a;
      line-height: 1.55;
    }

    .faktorak-batch-wrap {
      max-width: 1200px;
      margin: 0 auto;
      padding: 14px;
    }

    .faktorak-batch-toolbar {
      position: sticky;
      top: 0;
      z-index: 20;
      margin-bottom: 12px;
      padding: 12px;
      border: 1px solid var(--faktorak-batch-border);
      border-radius: 12px;
      background: var(--faktorak-batch-card);
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .faktorak-batch-toolbar h1 {
      margin: 0;
      font-size: 19px;
      color: var(--faktorak-batch-title);
    }

    .faktorak-batch-meta {
      color: var(--faktorak-batch-muted);
      font-size: 13px;
    }

    .faktorak-batch-toolbar-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .faktorak-batch-btn {
      border: 1px solid #bdd2ef;
      border-radius: 10px;
      background: #eff6ff;
      color: var(--faktorak-batch-primary);
      padding: 8px 12px;
      cursor: pointer;
      font-size: 13px;
      line-height: 1.2;
    }

    .faktorak-batch-btn.primary {
      border-color: #1e40af;
      background: #1d4ed8;
      color: #fff;
      font-weight: 600;
    }

    .faktorak-batch-limited {
      width: 100%;
      border-top: 1px dashed var(--faktorak-batch-border);
      padding-top: 8px;
      color: #92400e;
      font-size: 12px;
    }

    .faktorak-batch-item {
      margin: 0 0 12px;
      border: 1px solid var(--faktorak-batch-border);
      border-radius: 12px;
      overflow: hidden;
      background: var(--faktorak-batch-card);
    }

    .faktorak-batch-item-head {
      padding: 8px 12px;
      border-bottom: 1px solid #e5edf8;
      color: var(--faktorak-batch-muted);
      font-size: 12px;
      background: #f8fbff;
    }

    .faktorak-batch-item-body {
      background: #fff;
    }

    .print-buttons,
    .fak-actions {
      display: none !important;
    }

    @media (max-width: 820px) {
      .faktorak-batch-toolbar {
        position: static;
      }
      .faktorak-batch-toolbar-actions,
      .faktorak-batch-btn {
        width: 100%;
      }
    }

    @media print {
      body {
        background: #fff;
      }

      .faktorak-batch-wrap {
        max-width: none;
        margin: 0;
        padding: 0;
      }

      .faktorak-batch-toolbar {
        display: none !important;
      }

      .faktorak-batch-item {
        margin: 0;
        border: 0;
        border-radius: 0;
        break-inside: avoid-page;
        page-break-inside: avoid;
      }

      .faktorak-batch-item + .faktorak-batch-item {
        break-before: page;
        page-break-before: always;
      }

      .faktorak-batch-item-head {
        display: none;
      }
    }
  </style>
</head>
<body class="faktorak-scope">
  <div class="faktorak-batch-wrap faktorak-scope">
    <div class="faktorak-batch-toolbar">
      <div>
        <h1><?php echo esc_html( $batch_title ); ?></h1>
        <div class="faktorak-batch-meta">تعداد سند: <?php echo esc_html( (string) count( $documents ) ); ?></div>
      </div>
      <div class="faktorak-batch-toolbar-actions">
        <button type="button" class="faktorak-batch-btn primary" onclick="window.print()">چاپ همه</button>
        <button type="button" class="faktorak-batch-btn" onclick="window.close()">بستن</button>
      </div>
      <?php if ( ! empty( $batch_limited ) ) : ?>
        <div class="faktorak-batch-limited">برای حفظ کارایی، فقط 30 مورد اول چاپ شدند. برای بقیه، چاپ را در چند مرحله انجام دهید.</div>
      <?php endif; ?>
    </div>

    <?php foreach ( $documents as $index => $document ) : ?>
      <section class="faktorak-batch-item">
        <div class="faktorak-batch-item-head">
          <?php echo esc_html( '#' . $document['order_number'] . ' - ' . ( ( 'label' === $print_type ) ? 'برچسب' : 'فاکتور' ) ); ?>
        </div>
        <div class="faktorak-batch-item-body">
          <?php echo $document['markup']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
  <?php wp_print_footer_scripts(); ?>
</body>
</html>

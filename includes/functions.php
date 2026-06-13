<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * تابع اصلی برای بارگذاری هوک‌های افزونه
 */
function faktorak_initialize() {
    $settings = new Faktorak_Shipping_Invoice_Settings();

    // هوک‌های عمومی
    add_action('admin_menu', 'faktorak_shipping_invoice_add_menu');
    if ( class_exists( 'Faktorak_Order_Export' ) ) { Faktorak_Order_Export::init(); }
    add_action('admin_init', 'faktorak_shipping_invoice_register_settings');
    add_action('admin_notices', 'faktorak_platforms_promo_notice');
    add_action('wp_ajax_faktorak_dismiss_platforms_promo', 'faktorak_dismiss_platforms_promo');
    add_action('add_meta_boxes', 'faktorak_shipping_invoice_add_order_metabox');
    add_action('admin_enqueue_scripts', 'faktorak_enqueue_admin_styles');
    add_action( 'admin_post_faktorak_create_manual_invoice', 'faktorak_handle_create_manual_invoice' );
    add_action( 'admin_post_faktorak_delete_manual_invoice', 'faktorak_handle_delete_manual_invoice' );
    add_action( 'admin_post_faktorak_bulk_print_manual_invoices', 'faktorak_handle_bulk_print_manual_invoices' );
    add_action( 'admin_post_faktorak_bulk_print_orders', 'faktorak_handle_bulk_print_orders' );
    add_action( 'wp_ajax_faktorak_search_products', 'faktorak_ajax_search_products' );
    add_action( 'wp_ajax_faktorak_search_customers', 'faktorak_ajax_search_customers' );
    add_filter( 'manage_edit-shop_order_columns', 'faktorak_add_orders_list_column', 20 );
    add_action( 'manage_shop_order_posts_custom_column', 'faktorak_render_orders_list_column_legacy', 10, 2 );
    add_filter( 'manage_woocommerce_page_wc-orders_columns', 'faktorak_add_orders_list_column', 20 );
    add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'faktorak_render_orders_list_column_hpos', 10, 2 );
    add_filter( 'bulk_actions-edit-shop_order', 'faktorak_register_orders_bulk_print_actions' );
    add_filter( 'bulk_actions-woocommerce_page_wc-orders', 'faktorak_register_orders_bulk_print_actions' );
    add_filter( 'handle_bulk_actions-edit-shop_order', 'faktorak_handle_orders_bulk_print_action', 20, 3 );
    add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', 'faktorak_handle_orders_bulk_print_action', 20, 3 );
    add_action( 'template_redirect', 'faktorak_handle_manual_invoice_payment_link', 1 );
    add_action( 'template_redirect', 'faktorak_handle_proforma_payment_link', 1 );
    add_action('template_redirect', 'faktorak_shipping_invoice_display_content');
    add_action('init', 'faktorak_shipping_invoice_rewrite_rules');
    add_filter('query_vars', 'faktorak_shipping_invoice_query_vars');
    add_action('wp_enqueue_scripts', 'faktorak_enqueue_frontend_styles');

    // شورت‌کدها
    add_shortcode( 'faktorak_invoice_button', 'faktorak_invoice_button_shortcode' );
    add_shortcode( 'faktorak_proforma_button', 'faktorak_proforma_invoice_button_shortcode' );

    // پیش‌فاکتور
    add_action('init', 'faktorak_register_proforma_invoice_order_status');
    add_filter('wc_order_statuses', 'faktorak_add_proforma_invoice_to_order_statuses');
    add_action('template_redirect', 'faktorak_handle_create_proforma_invoice');
    add_action( 'woocommerce_payment_complete', 'faktorak_convert_proforma_to_official_after_payment', 20, 1 );
    add_action( 'woocommerce_order_status_changed', 'faktorak_convert_proforma_to_official_on_status_change', 20, 4 );

    // گزینه‌های شرطی
    if ( $settings->get_setting('show_user_buttons') === 'yes' ) {
        add_action('woocommerce_order_details_after_order_table', 'faktorak_shipping_invoice_add_user_buttons_frontend', 10, 1);
    }
    if ( $settings->get_setting('enable_proforma_invoice') === 'yes' ) {
        add_action('woocommerce_proceed_to_checkout', 'faktorak_add_proforma_invoice_button_on_cart', 21);
        add_action('woocommerce_before_checkout_form', 'faktorak_maybe_render_checkout_proforma_cta', 9);
    }
    if ( $settings->get_setting('enable_checkout_map') === 'yes' ) {
        add_action('wp_enqueue_scripts', 'faktorak_enqueue_map_assets');
        add_action('woocommerce_after_order_notes', 'faktorak_display_checkout_map');
        add_action('woocommerce_checkout_create_order', 'faktorak_save_map_location', 20, 2);
        add_action('woocommerce_admin_order_data_after_billing_address', 'faktorak_display_location_in_admin');
    }
}
add_action('plugins_loaded', 'faktorak_initialize', 20);

function faktorak_is_print_document_request() {
    $invoice_get = isset( $_GET['invoice'] ) ? sanitize_text_field( wp_unslash( $_GET['invoice'] ) ) : '';
    $label_get   = isset( $_GET['shipping_label'] ) ? sanitize_text_field( wp_unslash( $_GET['shipping_label'] ) ) : '';

    if ( 'true' === $invoice_get || 'true' === $label_get ) {
        return true;
    }

    if ( function_exists( 'get_query_var' ) ) {
        $invoice_qv = get_query_var( 'invoice', '' );
        $label_qv   = get_query_var( 'shipping_label', '' );
        if ( 'true' === $invoice_qv || 'true' === $label_qv ) {
            return true;
        }
    }

    return false;
}

function faktorak_disable_native_lazyload_for_print( $default, $tag_name, $context ) {
    if ( faktorak_is_print_document_request() ) {
        return false;
    }
    return $default;
}
add_filter( 'wp_lazy_loading_enabled', 'faktorak_disable_native_lazyload_for_print', 10, 3 );

function faktorak_disable_perfmatters_lazyload_for_print( $lazyload ) {
    if ( faktorak_is_print_document_request() ) {
        return false;
    }
    return $lazyload;
}
add_filter( 'perfmatters_lazyload', 'faktorak_disable_perfmatters_lazyload_for_print' );

function faktorak_perfmatters_lazyload_excluded_attributes( $attributes ) {
    if ( ! is_array( $attributes ) ) {
        return $attributes;
    }

    $attributes[] = 'class="faktorak-no-lazy';
    $attributes[] = "class='faktorak-no-lazy";
    $attributes[] = 'class="no-lazy';
    $attributes[] = "class='no-lazy";
    $attributes[] = 'data-no-lazy="1"';
    $attributes[] = "data-no-lazy='1'";
    return $attributes;
}
add_filter( 'perfmatters_lazyload_excluded_attributes', 'faktorak_perfmatters_lazyload_excluded_attributes' );

function faktorak_get_assets_url() {
    if ( defined( 'FAKTORAK_PLUGIN_URL' ) ) {
        return trailingslashit( FAKTORAK_PLUGIN_URL ) . 'assets/';
    }
    return plugin_dir_url( __FILE__ ) . '../assets/';
}

function faktorak_enqueue_custom_fonts_style() {
    $assets_url = faktorak_get_assets_url();

    if ( ! wp_style_is( 'faktorak-custom-fonts', 'registered' ) ) {
        wp_register_style(
            'faktorak-custom-fonts',
            $assets_url . 'css/custom-fonts.css',
            array(),
            defined( 'FAKTORAK_VERSION' ) ? FAKTORAK_VERSION : '1.0.0'
        );
    }

    wp_enqueue_style( 'faktorak-custom-fonts' );

    static $inline_added = false;
    if ( ! $inline_added ) {
        $font_url     = $assets_url . 'fonts/iranyekanwebregularfanum.woff';
        $inline_css   = "@font-face{font-family:'iranyekan';src:url('" . esc_url( $font_url ) . "') format('woff');font-weight:400;font-style:normal;font-display:swap;}";
        wp_add_inline_style( 'faktorak-custom-fonts', $inline_css );
        $inline_added = true;
    }
}


/*----------------------------
  ADMIN FUNCTIONS
----------------------------*/

function faktorak_shipping_invoice_add_menu() {
    $settings = new Faktorak_Shipping_Invoice_Settings();
    add_menu_page(
        'تنظیمات فاکتورک', 'فاکتورک', 'manage_options', 'shipping-invoice-settings',
        array($settings, 'settings_page'), 'dashicons-media-document', 80
    );
    add_submenu_page(
        'shipping-invoice-settings',
        'فاکتور دستی',
        'فاکتور دستی',
        'manage_woocommerce',
        'faktorak-manual-invoices',
        'faktorak_manual_invoice_page'
    );
    add_submenu_page(
        'shipping-invoice-settings',
        'لیست فاکتورها',
        'لیست فاکتورها',
        'manage_woocommerce',
        'faktorak-manual-invoices-list',
        'faktorak_manual_invoices_list_page'
    );
}

function faktorak_shipping_invoice_register_settings() {
    $settings = new Faktorak_Shipping_Invoice_Settings();
    $settings->register_settings();
}

function faktorak_render_admin_nav( $active = 'settings' ) {
    $items = array(
        'settings'      => array(
            'label' => 'تنظیمات فاکتور',
            'url'   => admin_url( 'admin.php?page=shipping-invoice-settings&tab=settings' ),
        ),
        'orders_export' => array(
            'label' => 'خروجی سفارشات',
            'url'   => admin_url( 'admin.php?page=shipping-invoice-settings&tab=orders_export' ),
        ),
        'manual'        => array(
            'label' => 'فاکتور دستی',
            'url'   => admin_url( 'admin.php?page=faktorak-manual-invoices' ),
        ),
        'list'          => array(
            'label' => 'لیست فاکتورها',
            'url'   => admin_url( 'admin.php?page=faktorak-manual-invoices-list' ),
        ),
        'support'       => array(
            'label' => 'پشتیبانی و حمایت',
            'url'   => admin_url( 'admin.php?page=shipping-invoice-settings&tab=support' ),
        ),
    );
    ?>
    <nav class="fak-tabs-nav" aria-label="تب‌های فاکتورک">
        <?php foreach ( $items as $key => $item ) : ?>
            <a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo $active === $key ? 'active' : ''; ?>">
                <?php echo esc_html( $item['label'] ); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

function faktorak_get_order_invoice_url( $order_id, $context = 'admin' ) {
    $order_id = absint( $order_id );
    if ( ! $order_id ) {
        return '';
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return '';
    }

    $is_proforma = $order->has_status( 'proforma-invoice' ) || 'proforma' === $order->get_meta( '_faktorak_doc_type' );
    $params      = array(
        'invoice'        => 'true',
        'order_id'       => $order_id,
        'context'        => sanitize_key( $context ),
        'faktorak_token' => wp_create_nonce( 'faktorak_inv_' . $order_id ),
    );
    if ( $is_proforma ) {
        $params['is_proforma'] = 'true';
    }

    return add_query_arg( $params, home_url() );
}

function faktorak_get_order_shipping_label_url( $order_id, $context = 'admin' ) {
    $order_id = absint( $order_id );
    if ( ! $order_id ) {
        return '';
    }

    return add_query_arg(
        array(
            'shipping_label' => 'true',
            'order_id'       => $order_id,
            'context'        => sanitize_key( $context ),
        ),
        home_url()
    );
}

function faktorak_get_order_payment_url( $order_id, $context = 'frontend' ) {
    $order_id = absint( $order_id );
    if ( ! $order_id ) {
        return '';
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return '';
    }

    $is_proforma = ( $order->has_status( 'proforma-invoice' ) || 'proforma' === $order->get_meta( '_faktorak_doc_type' ) );

    if ( $is_proforma ) {
        return add_query_arg(
            array(
                'faktorak_proforma_pay' => 'true',
                'order_id'              => $order_id,
                'context'               => sanitize_key( $context ),
                'key'                   => $order->get_order_key(),
            ),
            home_url()
        );
    }

    return $order->get_checkout_payment_url();
}

function faktorak_handle_proforma_payment_link() {
    if ( ! isset( $_GET['faktorak_proforma_pay'] ) || 'true' !== sanitize_text_field( wp_unslash( $_GET['faktorak_proforma_pay'] ) ) ) {
        return;
    }

    $order_id  = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    $order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
    if ( ! $order_id || ! $order_key ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز به لینک پرداخت.', 'faktorak' ) );
    }

    $order = wc_get_order( $order_id );
    $is_proforma = $order && ( $order->has_status( 'proforma-invoice' ) || 'proforma' === $order->get_meta( '_faktorak_doc_type' ) );
    if ( ! $order || ! $is_proforma || ! hash_equals( (string) $order->get_order_key(), (string) $order_key ) ) {
        wp_die( esc_html__( 'سفارش معتبر نیست.', 'faktorak' ) );
    }

    if ( $order->is_paid() ) {
        wp_safe_redirect( faktorak_get_order_invoice_url( $order_id, 'frontend' ) );
        exit;
    }

    if ( $order->has_status( 'proforma-invoice' ) ) {
        $order->update_status( 'pending', __( 'پیش‌فاکتور از طریق لینک پرداخت به سفارش قابل پرداخت تبدیل شد.', 'faktorak' ) );
    }

    $payment_url = $order->get_checkout_payment_url();
    if ( $payment_url ) {
        wp_safe_redirect( $payment_url );
        exit;
    }

    wp_die( esc_html__( 'امکان ساخت لینک پرداخت وجود ندارد.', 'faktorak' ) );
}

function faktorak_can_manage_manual_invoices() {
    return current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_shop_orders' );
}

function faktorak_handle_manual_invoice_payment_link() {
    if ( ! isset( $_GET['faktorak_manual_pay'] ) || 'true' !== sanitize_text_field( wp_unslash( $_GET['faktorak_manual_pay'] ) ) ) {
        return;
    }

    $order_id  = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    $order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
    if ( ! $order_id || ! $order_key ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز به لینک پرداخت.', 'faktorak' ) );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order || 'yes' !== $order->get_meta( '_faktorak_manual_invoice' ) || ! hash_equals( (string) $order->get_order_key(), (string) $order_key ) ) {
        wp_die( esc_html__( 'سفارش معتبر نیست.', 'faktorak' ) );
    }

    if ( $order->is_paid() ) {
        wp_safe_redirect( faktorak_get_order_invoice_url( $order_id, 'frontend' ) );
        exit;
    }

    if ( $order->has_status( 'proforma-invoice' ) ) {
        $order->update_status( 'pending', __( 'پیش‌فاکتور دستی از طریق لینک پرداخت به سفارش قابل پرداخت تبدیل شد.', 'faktorak' ) );
    }

    $payment_url = $order->get_checkout_payment_url();
    if ( $payment_url ) {
        wp_safe_redirect( $payment_url );
        exit;
    }

    wp_die( esc_html__( 'امکان ساخت لینک پرداخت وجود ندارد.', 'faktorak' ) );
}

function faktorak_find_user_by_phone( $phone ) {
    $phone = preg_replace( '/\D+/', '', (string) $phone );
    if ( '' === $phone ) {
        return 0;
    }

    $query = new WP_User_Query(
        array(
            'number'     => 1,
            'fields'     => 'ID',
            'meta_query' => array(
                array(
                    'key'     => 'billing_phone',
                    'value'   => $phone,
                    'compare' => 'LIKE',
                ),
            ),
        )
    );
    $ids = $query->get_results();
    return ! empty( $ids[0] ) ? (int) $ids[0] : 0;
}

function faktorak_find_existing_customer_user_id( $customer_user_id, $email, $phone, $first_name = '', $last_name = '' ) {
    $user_id = absint( $customer_user_id );
    if ( $user_id && get_user_by( 'id', $user_id ) ) {
        return $user_id;
    }

    if ( $email ) {
        $user = get_user_by( 'email', $email );
        if ( $user && ! empty( $user->ID ) ) {
            return (int) $user->ID;
        }
    }

    $phone_user_id = faktorak_find_user_by_phone( $phone );
    if ( $phone_user_id ) {
        return $phone_user_id;
    }

    $first_name = sanitize_text_field( $first_name );
    $last_name  = sanitize_text_field( $last_name );
    if ( '' !== $first_name && '' !== $last_name ) {
        $name_query = new WP_User_Query(
            array(
                'number'     => 2,
                'fields'     => 'ID',
                'meta_query' => array(
                    array(
                        'key'   => 'billing_first_name',
                        'value' => $first_name,
                    ),
                    array(
                        'key'   => 'billing_last_name',
                        'value' => $last_name,
                    ),
                ),
            )
        );
        $ids = $name_query->get_results();
        if ( 1 === count( $ids ) ) {
            return (int) $ids[0];
        }
    }

    return 0;
}

function faktorak_manual_invoice_page() {
    if ( ! faktorak_can_manage_manual_invoices() ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز.', 'faktorak' ) );
    }

    $created_order_id = isset( $_GET['created_order'] ) ? absint( $_GET['created_order'] ) : 0;
    $error_code       = isset( $_GET['faktorak_error'] ) ? sanitize_key( wp_unslash( $_GET['faktorak_error'] ) ) : '';
    ?>
    <div class="wrap faktorak-scope faktorak-admin-page faktorak-manual-wrap faktorak-list-page" dir="rtl">
        <div class="faktorak-manual-header">
            <h1 class="fak-page-title">فاکتور دستی</h1>
            <p>سند را سریع بساز، لینک را ارسال کن و پرداخت را روی همان سفارش پیگیری کن.</p>
        </div>
        <?php faktorak_render_admin_nav( 'manual' ); ?>

        <?php if ( $error_code ) : ?>
            <div class="notice notice-error"><p>
                <?php
                $messages = array(
                    'invalid_product' => 'محصول معتبر انتخاب نشده است.',
                    'no_items'        => 'حداقل یک محصول به فاکتور اضافه کنید.',
                    'create_failed'   => 'ایجاد فاکتور دستی با خطا مواجه شد. لطفاً دوباره تلاش کنید.',
                );
                echo esc_html( isset( $messages[ $error_code ] ) ? $messages[ $error_code ] : $messages['create_failed'] );
                ?>
            </p></div>
        <?php endif; ?>

        <?php
        if ( $created_order_id ) :
            $created_order = wc_get_order( $created_order_id );
            if ( $created_order && 'yes' === $created_order->get_meta( '_faktorak_manual_invoice' ) ) :
                $admin_invoice_url    = faktorak_get_order_invoice_url( $created_order_id, 'admin' );
                $admin_label_url      = faktorak_get_order_shipping_label_url( $created_order_id, 'admin' );
                $customer_invoice_url = faktorak_get_order_invoice_url( $created_order_id, 'frontend' );
                $payment_url          = faktorak_get_order_payment_url( $created_order_id, 'frontend' );
                $order_edit_url       = admin_url( 'post.php?post=' . $created_order_id . '&action=edit' );
                ?>
                <div class="notice notice-success faktorak-created-notice">
                    <p><strong>فاکتور دستی با موفقیت ایجاد شد.</strong></p>
                    <p>
                        <a class="button button-primary" href="<?php echo esc_url( $admin_invoice_url ); ?>" target="_blank" rel="noopener">مشاهده/چاپ فاکتور</a>
                        <a class="button" href="<?php echo esc_url( $admin_label_url ); ?>" target="_blank" rel="noopener">چاپ برچسب پستی</a>
                        <a class="button" href="<?php echo esc_url( $order_edit_url ); ?>">مشاهده سفارش</a>
                        <?php if ( ! $created_order->is_paid() && $payment_url ) : ?>
                            <a class="button faktorak-pay-btn" href="<?php echo esc_url( $payment_url ); ?>" target="_blank" rel="noopener">لینک پرداخت</a>
                        <?php endif; ?>
                    </p>
                    <p class="faktorak-created-link-block">
                        <label for="faktorak-created-invoice-link" class="faktorak-created-link-label">لینک ارسال به مشتری:</label>
                        <input id="faktorak-created-invoice-link" type="text" readonly class="fak-input" value="<?php echo esc_attr( $customer_invoice_url ); ?>">
                        <button type="button" class="button fak-copy-btn fak-copy-btn-top" data-target="#faktorak-created-invoice-link">کپی لینک</button>
                    </p>
                    <?php if ( ! $created_order->is_paid() && $payment_url ) : ?>
                    <p class="faktorak-created-link-block">
                        <label for="faktorak-created-payment-link" class="faktorak-created-link-label">لینک پرداخت برای ارسال به مشتری:</label>
                        <input id="faktorak-created-payment-link" type="text" readonly class="fak-input fak-input-payment" value="<?php echo esc_attr( $payment_url ); ?>">
                        <button type="button" class="button fak-copy-btn fak-copy-btn-top faktorak-pay-copy-btn" data-target="#faktorak-created-payment-link">کپی لینک پرداخت</button>
                    </p>
                    <?php endif; ?>
                </div>
                <?php
            endif;
        endif;
        ?>

        <form class="faktorak-manual-card fak-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'faktorak_create_manual_invoice', 'faktorak_manual_invoice_nonce' ); ?>
            <input type="hidden" name="action" value="faktorak_create_manual_invoice">
            <input type="hidden" id="faktorak-customer-user-id" name="customer_user_id" value="">

            <div class="faktorak-manual-grid">
                <div class="faktorak-panel">
                    <h2>اطلاعات سند</h2>
                    <label class="faktorak-field">
                        <span>نوع سند</span>
                        <select name="doc_type" required>
                            <option value="proforma">پیش‌فاکتور</option>
                            <option value="official">فاکتور رسمی</option>
                        </select>
                    </label>
                    <label class="faktorak-field">
                        <span>کاربر عضو (اختیاری)</span>
                        <input id="faktorak-customer-search" type="text" autocomplete="off" placeholder="نام/ایمیل/موبایل کاربر...">
                        <div id="faktorak-customer-results" class="faktorak-search-results is-hidden"></div>
                    </label>
                    <p class="description">با انتخاب کاربر عضو، فاکتور به همان حساب کاربری متصل می‌ماند.</p>
                </div>

                <div class="faktorak-panel">
                    <h2>مشخصات مشتری</h2>
                    <div class="faktorak-two-cols">
                        <label class="faktorak-field"><span>نام</span><input id="fak-billing-first-name" type="text" name="customer_first_name" required></label>
                        <label class="faktorak-field"><span>نام خانوادگی</span><input id="fak-billing-last-name" type="text" name="customer_last_name"></label>
                        <label class="faktorak-field"><span>موبایل</span><input id="fak-billing-phone" type="text" name="customer_phone"></label>
                        <label class="faktorak-field"><span>ایمیل</span><input id="fak-billing-email" type="email" name="customer_email"></label>
                        <label class="faktorak-field"><span>استان</span><input id="fak-billing-state" type="text" name="customer_state"></label>
                        <label class="faktorak-field"><span>شهر</span><input id="fak-billing-city" type="text" name="customer_city"></label>
                        <label class="faktorak-field"><span>کدپستی</span><input id="fak-billing-postcode" type="text" name="customer_postcode"></label>
                    </div>
                    <label class="faktorak-field"><span>آدرس</span><textarea id="fak-billing-address" rows="3" name="customer_address"></textarea></label>
                </div>
            </div>

            <div class="faktorak-products-card fak-card">
                <div class="faktorak-products-head">
                    <h2>اقلام فاکتور</h2>
                    <button type="button" class="button" id="faktorak-add-item">+ افزودن محصول</button>
                </div>
                <div class="faktorak-products-table-wrap">
                    <table class="widefat fixed striped faktorak-products-table">
                        <thead>
                            <tr>
                                <th>جستجوی محصول</th>
                                <th>نام</th>
                                <th>شناسه</th>
                                <th>قیمت واحد</th>
                                <th>تعداد</th>
                                <th>حذف</th>
                            </tr>
                        </thead>
                        <tbody id="faktorak-manual-items">
                            <tr class="faktorak-item-row">
                                <td>
                                    <input type="text" class="faktorak-product-search regular-text" autocomplete="off" placeholder="نام یا شناسه...">
                                    <input type="hidden" class="faktorak-product-id" name="product_id[]">
                                    <div class="faktorak-search-results is-hidden"></div>
                                </td>
                                <td><input type="text" class="faktorak-product-name regular-text" readonly></td>
                                <td><input type="text" class="faktorak-product-sku regular-text" readonly></td>
                                <td><input type="text" class="faktorak-product-price regular-text" readonly></td>
                                <td>
                                    <div class="faktorak-qty-control">
                                        <button type="button" class="button faktorak-qty-btn faktorak-qty-plus" aria-label="افزایش تعداد">+</button>
                                        <input type="number" class="small-text faktorak-qty-input" name="quantity[]" min="1" value="1" inputmode="numeric">
                                        <button type="button" class="button faktorak-qty-btn faktorak-qty-minus" aria-label="کاهش تعداد">-</button>
                                    </div>
                                </td>
                                <td><button type="button" class="button button-link-delete faktorak-remove-item">حذف</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="faktorak-manual-actions">
                <button type="submit" class="button button-primary button-large">ایجاد فاکتور دستی</button>
                <a class="button button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=faktorak-manual-invoices-list' ) ); ?>">لیست فاکتورها</a>
            </div>
        </form>
    </div>
    <?php
}

function faktorak_manual_invoices_list_page() {
    if ( ! faktorak_can_manage_manual_invoices() ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز.', 'faktorak' ) );
    }

    $paged         = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
    $perpage       = 20;
    $search        = isset( $_GET['faktorak_search'] ) ? sanitize_text_field( wp_unslash( $_GET['faktorak_search'] ) ) : '';
    $doc_type      = isset( $_GET['doc_type'] ) ? sanitize_key( wp_unslash( $_GET['doc_type'] ) ) : 'all';
    $status_filter = isset( $_GET['order_status'] ) ? sanitize_key( wp_unslash( $_GET['order_status'] ) ) : 'all';
    $notice        = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : '';
    $statuses      = wc_get_order_statuses();

    $meta_query = array(
        array(
            'key'   => '_faktorak_manual_invoice',
            'value' => 'yes',
        ),
    );

    if ( in_array( $doc_type, array( 'proforma', 'official' ), true ) ) {
        $meta_query[] = array(
            'key'   => '_faktorak_doc_type',
            'value' => $doc_type,
        );
    }

    $search_order_id = ltrim( $search, '#' );
    if ( $search && ! ctype_digit( $search_order_id ) && ! is_email( $search ) ) {
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_billing_first_name',
                'value'   => $search,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => '_billing_last_name',
                'value'   => $search,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => '_billing_phone',
                'value'   => $search,
                'compare' => 'LIKE',
            ),
        );
    }

    $args = array(
        'limit'      => $perpage,
        'paged'      => $paged,
        'orderby'    => 'date',
        'order'      => 'DESC',
        'paginate'   => true,
        'meta_query' => $meta_query,
    );
    if ( ctype_digit( $search_order_id ) ) {
        $args['include'] = array( absint( $search_order_id ) );
    } elseif ( is_email( $search ) ) {
        $args['billing_email'] = $search;
    }
    if ( 'all' !== $status_filter ) {
        $args['status'] = $status_filter;
    }

    $result      = wc_get_orders( $args );
    $orders      = ( is_object( $result ) && isset( $result->orders ) ) ? $result->orders : array();
    $total_pages = ( is_object( $result ) && isset( $result->max_num_pages ) ) ? max( 1, (int) $result->max_num_pages ) : 1;
    ?>
    <div class="wrap faktorak-scope faktorak-admin-page faktorak-manual-wrap faktorak-invoices-list-wrap" dir="rtl">
        <div class="faktorak-manual-header">
            <h1 class="fak-page-title">لیست فاکتورها</h1>
            <p>رصد، فیلتر و مدیریت فاکتورهای ایجادشده.</p>
        </div>
        <?php faktorak_render_admin_nav( 'list' ); ?>

        <?php if ( 'deleted' === $notice ) : ?>
            <div class="notice notice-success"><p>فاکتور دستی حذف شد.</p></div>
        <?php elseif ( 'delete_failed' === $notice ) : ?>
            <div class="notice notice-error"><p>حذف فاکتور دستی انجام نشد.</p></div>
        <?php endif; ?>

        <form method="get" class="faktorak-list-filters fak-card">
            <input type="hidden" name="page" value="faktorak-manual-invoices-list">
            <input type="text" name="faktorak_search" value="<?php echo esc_attr( $search ); ?>" placeholder="جستجو (شماره/ایمیل/نام)">
            <select name="doc_type">
                <option value="all" <?php selected( $doc_type, 'all' ); ?>>همه اسناد</option>
                <option value="official" <?php selected( $doc_type, 'official' ); ?>>فاکتور رسمی</option>
                <option value="proforma" <?php selected( $doc_type, 'proforma' ); ?>>پیش‌فاکتور</option>
            </select>
            <select name="order_status">
                <option value="all" <?php selected( $status_filter, 'all' ); ?>>همه وضعیت‌ها</option>
                <?php foreach ( $statuses as $status_key => $status_label ) : ?>
                    <?php $status_value = str_replace( 'wc-', '', $status_key ); ?>
                    <option value="<?php echo esc_attr( $status_value ); ?>" <?php selected( $status_filter, $status_value ); ?>><?php echo esc_html( $status_label ); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-primary">فیلتر</button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=faktorak-manual-invoices-list' ) ); ?>" class="button">پاک‌کردن</a>
        </form>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" target="_blank" class="faktorak-bulk-print-form">
            <?php wp_nonce_field( 'faktorak_bulk_print_manual_invoices', 'faktorak_bulk_print_nonce' ); ?>
            <input type="hidden" name="action" value="faktorak_bulk_print_manual_invoices">

            <div class="faktorak-bulk-tools fak-card">
                <div class="faktorak-bulk-tools__summary">
                    <strong>چاپ گروهی</strong>
                    <span class="faktorak-bulk-selected-count">0 انتخاب شده</span>
                </div>
                <div class="faktorak-bulk-tools__actions">
                    <button type="submit" class="button button-primary faktorak-bulk-submit" name="faktorak_bulk_print_type" value="invoice">چاپ فاکتورهای انتخاب‌شده</button>
                    <button type="submit" class="button faktorak-bulk-submit" name="faktorak_bulk_print_type" value="label">چاپ برچسب‌های انتخاب‌شده</button>
                </div>
            </div>

            <div class="faktorak-manual-card fak-card">
            <table class="widefat striped faktorak-list-table">
                <thead>
                    <tr>
                        <th class="faktorak-col-check">
                            <input type="checkbox" class="faktorak-bulk-check-all" aria-label="انتخاب همه">
                        </th>
                        <th>شماره سفارش</th>
                        <th>مشتری</th>
                        <th>نوع سند</th>
                        <th>وضعیت</th>
                        <th>مبلغ</th>
                        <th>تاریخ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $orders ) ) : ?>
                    <tr><td colspan="8">موردی یافت نشد.</td></tr>
                <?php else : ?>
                    <?php foreach ( $orders as $order ) : ?>
                        <?php
                        $order_id             = $order->get_id();
                        $row_doc_type         = $order->get_meta( '_faktorak_doc_type' );
                        $doc_label            = ( 'official' === $row_doc_type ) ? 'فاکتور رسمی' : 'پیش‌فاکتور';
                        $admin_invoice_url    = faktorak_get_order_invoice_url( $order_id, 'admin' );
                        $admin_label_url      = faktorak_get_order_shipping_label_url( $order_id, 'admin' );
                        $customer_invoice_url = faktorak_get_order_invoice_url( $order_id, 'frontend' );
                        $payment_url          = faktorak_get_order_payment_url( $order_id, 'frontend' );
                        $order_edit_url       = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
                        $customer_name        = trim( $order->get_formatted_billing_full_name() );
                        $delete_url           = wp_nonce_url(
                            add_query_arg(
                                array(
                                    'action'   => 'faktorak_delete_manual_invoice',
                                    'order_id' => $order_id,
                                ),
                                admin_url( 'admin-post.php' )
                            ),
                            'faktorak_delete_manual_invoice_' . $order_id
                        );
                        $modal_id               = 'faktorak-links-modal-' . $order_id;
                        $customer_link_input_id = 'faktorak-customer-link-' . $order_id;
                        $payment_link_input_id  = 'faktorak-payment-link-' . $order_id;
                        ?>
                        <tr class="faktorak-list-row">
                            <td class="faktorak-col-check" data-label="انتخاب">
                                <input type="checkbox" class="faktorak-bulk-item" name="order_ids[]" value="<?php echo esc_attr( $order_id ); ?>" aria-label="انتخاب سفارش <?php echo esc_attr( $order->get_order_number() ); ?>">
                            </td>
                            <td class="faktorak-col-order" data-label="شماره سفارش">#<?php echo esc_html( $order->get_order_number() ); ?></td>
                            <td class="faktorak-col-customer" data-label="مشتری"><?php echo esc_html( $customer_name ? $customer_name : '—' ); ?></td>
                            <td class="faktorak-col-doc" data-label="نوع سند"><?php echo esc_html( $doc_label ); ?></td>
                            <td class="faktorak-col-status" data-label="وضعیت"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
                            <td class="faktorak-col-total" data-label="مبلغ"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                            <td class="faktorak-col-date" data-label="تاریخ"><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'Y-m-d H:i' ) ); ?></td>
                            <td class="faktorak-col-actions" data-label="عملیات">
                                <button
                                    type="button"
                                    class="button button-small faktorak-open-links-modal"
                                    data-modal-target="#<?php echo esc_attr( $modal_id ); ?>"
                                >اطلاعات بیشتر</button>

                                <div id="<?php echo esc_attr( $modal_id ); ?>" class="faktorak-links-modal" hidden>
                                    <div class="faktorak-links-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $modal_id ); ?>-title">
                                        <div class="faktorak-links-modal__head">
                                            <h3 id="<?php echo esc_attr( $modal_id ); ?>-title">جزئیات سفارش #<?php echo esc_html( $order->get_order_number() ); ?></h3>
                                            <button type="button" class="faktorak-modal-close" aria-label="بستن">×</button>
                                        </div>

                                        <div class="faktorak-links-modal__actions">
                                            <a href="<?php echo esc_url( $admin_invoice_url ); ?>" target="_blank" rel="noopener" class="button">چاپ فاکتور</a>
                                            <a href="<?php echo esc_url( $admin_label_url ); ?>" target="_blank" rel="noopener" class="button">چاپ برچسب</a>
                                            <a href="<?php echo esc_url( $order_edit_url ); ?>" class="button">مشاهده سفارش</a>
                                            <?php if ( ! $order->is_paid() && $payment_url ) : ?>
                                                <a href="<?php echo esc_url( $payment_url ); ?>" target="_blank" rel="noopener" class="button faktorak-pay-btn">پرداخت</a>
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-link-delete" onclick="return confirm('از حذف این فاکتور دستی مطمئن هستید؟');">حذف</a>
                                        </div>

                                        <div class="faktorak-links-modal__links">
                                            <div class="faktorak-modal-link-row">
                                                <label for="<?php echo esc_attr( $customer_link_input_id ); ?>">لینک مشتری</label>
                                                <input id="<?php echo esc_attr( $customer_link_input_id ); ?>" type="text" readonly class="fak-input fak-input-payment" value="<?php echo esc_attr( $customer_invoice_url ); ?>">
                                                <button type="button" class="button fak-copy-btn faktorak-pay-copy-btn" data-target="#<?php echo esc_attr( $customer_link_input_id ); ?>">کپی لینک مشتری</button>
                                            </div>

                                            <?php if ( ! $order->is_paid() && $payment_url ) : ?>
                                                <div class="faktorak-modal-link-row">
                                                    <label for="<?php echo esc_attr( $payment_link_input_id ); ?>">لینک پرداخت</label>
                                                    <input id="<?php echo esc_attr( $payment_link_input_id ); ?>" type="text" readonly class="fak-input fak-input-payment" value="<?php echo esc_attr( $payment_url ); ?>">
                                                    <button type="button" class="button fak-copy-btn faktorak-pay-copy-btn" data-target="#<?php echo esc_attr( $payment_link_input_id ); ?>">کپی لینک پرداخت</button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        </form>

        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav faktorak-list-table-nav">
                <div class="tablenav-pages">
                    <?php
                    echo wp_kses_post(
                        paginate_links(
                            array(
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'current'   => $paged,
                                'total'     => $total_pages,
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                            )
                        )
                    );
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function faktorak_handle_create_manual_invoice() {
    if ( ! faktorak_can_manage_manual_invoices() ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز.', 'faktorak' ) );
    }
    if ( ! isset( $_POST['faktorak_manual_invoice_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['faktorak_manual_invoice_nonce'] ) ), 'faktorak_create_manual_invoice' ) ) {
        wp_die( esc_html__( 'خطای امنیتی.', 'faktorak' ) );
    }

    $redirect_url = admin_url( 'admin.php?page=faktorak-manual-invoices' );
    $doc_type     = isset( $_POST['doc_type'] ) ? sanitize_key( wp_unslash( $_POST['doc_type'] ) ) : 'proforma';
    if ( ! in_array( $doc_type, array( 'proforma', 'official' ), true ) ) {
        $doc_type = 'proforma';
    }

    $product_ids = isset( $_POST['product_id'] ) ? (array) wp_unslash( $_POST['product_id'] ) : array();
    $quantities  = isset( $_POST['quantity'] ) ? (array) wp_unslash( $_POST['quantity'] ) : array();
    $items       = array();
    foreach ( $product_ids as $index => $raw_product_id ) {
        $product_id = absint( $raw_product_id );
        $quantity   = isset( $quantities[ $index ] ) ? max( 1, absint( $quantities[ $index ] ) ) : 1;
        if ( ! $product_id ) {
            continue;
        }
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            continue;
        }
        $items[] = array(
            'product'  => $product,
            'quantity' => $quantity,
        );
    }
    if ( empty( $items ) ) {
        wp_safe_redirect( add_query_arg( 'faktorak_error', 'no_items', $redirect_url ) );
        exit;
    }

    try {
        $order = wc_create_order();

        foreach ( $items as $item ) {
            $order->add_product( $item['product'], $item['quantity'] );
        }

        $billing = array(
            'first_name' => isset( $_POST['customer_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_first_name'] ) ) : '',
            'last_name'  => isset( $_POST['customer_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_last_name'] ) ) : '',
            'email'      => isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '',
            'phone'      => isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '',
            'address_1'  => isset( $_POST['customer_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['customer_address'] ) ) : '',
            'city'       => isset( $_POST['customer_city'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_city'] ) ) : '',
            'state'      => isset( $_POST['customer_state'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_state'] ) ) : '',
            'postcode'   => isset( $_POST['customer_postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_postcode'] ) ) : '',
            'country'    => ( WC()->countries ? WC()->countries->get_base_country() : 'IR' ),
        );

        $customer_user_id = isset( $_POST['customer_user_id'] ) ? absint( $_POST['customer_user_id'] ) : 0;
        $matched_user_id  = faktorak_find_existing_customer_user_id( $customer_user_id, $billing['email'], $billing['phone'], $billing['first_name'], $billing['last_name'] );
        if ( $matched_user_id ) {
            $order->set_customer_id( $matched_user_id );
            $customer = new WC_Customer( $matched_user_id );
            $saved    = $customer->get_billing();
            foreach ( array( 'first_name', 'last_name', 'email', 'phone', 'address_1', 'city', 'state', 'postcode', 'country' ) as $field ) {
                if ( empty( $billing[ $field ] ) && ! empty( $saved[ $field ] ) ) {
                    $billing[ $field ] = $saved[ $field ];
                }
            }
        }

        $order->set_address( $billing, 'billing' );
        $order->set_address( $billing, 'shipping' );

        $order->update_meta_data( '_faktorak_manual_invoice', 'yes' );
        $order->update_meta_data( '_faktorak_doc_type', $doc_type );
        $order->update_meta_data( '_faktorak_manual_created_by', get_current_user_id() );
        if ( $matched_user_id ) {
            $order->update_meta_data( '_faktorak_manual_customer_user_id', $matched_user_id );
        }

        $order->calculate_totals();

        $statuses      = wc_get_order_statuses();
        $target_status = 'pending';
        if ( 'proforma' === $doc_type && isset( $statuses['wc-proforma-invoice'] ) ) {
            $target_status = 'proforma-invoice';
        }
        $order->set_status( $target_status, __( 'سند دستی توسط فاکتورک ایجاد شد.', 'faktorak' ) );
        $order->save();

        wp_safe_redirect( add_query_arg( 'created_order', $order->get_id(), $redirect_url ) );
        exit;
    } catch ( Exception $e ) {
        wp_safe_redirect( add_query_arg( 'faktorak_error', 'create_failed', $redirect_url ) );
        exit;
    }
}

function faktorak_handle_delete_manual_invoice() {
    if ( ! faktorak_can_manage_manual_invoices() ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز.', 'faktorak' ) );
    }

    $redirect = admin_url( 'admin.php?page=faktorak-manual-invoices-list' );
    $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    if ( ! $order_id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'faktorak_delete_manual_invoice_' . $order_id ) ) {
        wp_safe_redirect( add_query_arg( 'notice', 'delete_failed', $redirect ) );
        exit;
    }

    $order = wc_get_order( $order_id );
    if ( ! $order || 'yes' !== $order->get_meta( '_faktorak_manual_invoice' ) ) {
        wp_safe_redirect( add_query_arg( 'notice', 'delete_failed', $redirect ) );
        exit;
    }

    try {
        $order->delete( false );
        wp_safe_redirect( add_query_arg( 'notice', 'deleted', $redirect ) );
        exit;
    } catch ( Exception $e ) {
        wp_safe_redirect( add_query_arg( 'notice', 'delete_failed', $redirect ) );
        exit;
    }
}

function faktorak_handle_bulk_print_manual_invoices() {
    if ( ! faktorak_can_manage_manual_invoices() ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز.', 'faktorak' ) );
    }

    $nonce = isset( $_POST['faktorak_bulk_print_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['faktorak_bulk_print_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'faktorak_bulk_print_manual_invoices' ) ) {
        wp_die( esc_html__( 'خطای امنیتی.', 'faktorak' ) );
    }

    $print_type = isset( $_POST['faktorak_bulk_print_type'] ) ? sanitize_key( wp_unslash( $_POST['faktorak_bulk_print_type'] ) ) : 'invoice';
    if ( ! in_array( $print_type, array( 'invoice', 'label' ), true ) ) {
        $print_type = 'invoice';
    }

    $raw_order_ids   = isset( $_POST['order_ids'] ) ? (array) wp_unslash( $_POST['order_ids'] ) : array();
    $order_ids       = array_values( array_unique( array_filter( array_map( 'absint', $raw_order_ids ) ) ) );

    if ( empty( $order_ids ) ) {
        wp_die( esc_html__( 'هیچ سفارشی انتخاب نشده است.', 'faktorak' ) );
    }

    faktorak_render_bulk_print_page( $order_ids, $print_type, true );
}

function faktorak_render_bulk_print_page( $order_ids, $print_type = 'invoice', $only_manual = true ) {
    $order_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $order_ids ) ) ) );
    if ( empty( $order_ids ) ) {
        wp_die( esc_html__( 'هیچ سفارشی انتخاب نشده است.', 'faktorak' ) );
    }

    $print_type = sanitize_key( (string) $print_type );
    if ( ! in_array( $print_type, array( 'invoice', 'label' ), true ) ) {
        $print_type = 'invoice';
    }

    $settings      = new Faktorak_Shipping_Invoice_Settings();
    $template_file = 'shipping-label.php';
    $asset_context = 'shipping-label';
    $style_handles = array( 'faktorak-custom-fonts', 'faktorak-shipping-label' );

    if ( 'invoice' === $print_type ) {
        $tpl = $settings->get_setting( 'invoice_template' );
        if ( 'template-1' === $tpl ) {
            $tpl = 'classic';
        }
        if ( 'modern' === $tpl ) {
            $template_file = 'invoice-template-modern.php';
            $asset_context = 'invoice-modern';
            $style_handles = array( 'faktorak-custom-fonts', 'faktorak-invoice-modern' );
        } else {
            $template_file = 'invoice-template.php';
            $asset_context = 'invoice-classic';
            $style_handles = array( 'faktorak-custom-fonts', 'faktorak-invoice-classic' );
        }
    }

    $submitted_count = count( $order_ids );
    $max_batch = 30;
    if ( count( $order_ids ) > $max_batch ) {
        $order_ids = array_slice( $order_ids, 0, $max_batch );
    }

    faktorak_enqueue_print_assets( $asset_context );

    $documents = array();
    foreach ( $order_ids as $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            continue;
        }
        if ( $only_manual && 'yes' !== $order->get_meta( '_faktorak_manual_invoice' ) ) {
            continue;
        }

        $document_markup = faktorak_render_batch_print_document_markup( $order_id, $print_type, $template_file );
        if ( '' === $document_markup ) {
            continue;
        }

        $documents[] = array(
            'order_id'     => $order_id,
            'order_number' => $order->get_order_number(),
            'markup'       => $document_markup,
        );
    }

    if ( empty( $documents ) ) {
        wp_die( esc_html__( 'سفارش معتبر برای چاپ پیدا نشد.', 'faktorak' ) );
    }

    $batch_title   = ( 'label' === $print_type ) ? 'چاپ گروهی برچسب‌ها' : 'چاپ گروهی فاکتورها';
    $batch_limited = ( $submitted_count > $max_batch );
    $batch_style_handles = $style_handles;
    nocache_headers();
    include plugin_dir_path( __FILE__ ) . '../templates/batch-print.php';
    exit;
}

function faktorak_extract_html_body_content( $html ) {
    $html = (string) $html;
    if ( '' === $html ) {
        return '';
    }
    if ( preg_match( '#<body[^>]*>(.*)</body>#is', $html, $matches ) ) {
        return (string) $matches[1];
    }
    return $html;
}

function faktorak_render_batch_print_document_markup( $order_id, $print_type, $template_file ) {
    $order_id      = absint( $order_id );
    $template_file = sanitize_file_name( $template_file );
    if ( ! $order_id || ! $template_file ) {
        return '';
    }

    $template_path = plugin_dir_path( __FILE__ ) . '../templates/' . $template_file;
    if ( ! file_exists( $template_path ) ) {
        return '';
    }

    $original_get = $_GET;
    $styles_done  = function_exists( 'wp_styles' ) && wp_styles() ? (array) wp_styles()->done : array();
    $scripts_done = function_exists( 'wp_scripts' ) && wp_scripts() ? (array) wp_scripts()->done : array();

    $_GET['order_id'] = (string) $order_id;
    $_GET['context']  = 'admin';
    if ( 'label' === $print_type ) {
        $_GET['shipping_label'] = 'true';
        unset( $_GET['invoice'] );
    } else {
        $_GET['invoice'] = 'true';
        unset( $_GET['shipping_label'] );
    }

    ob_start();
    include $template_path;
    $html = ob_get_clean();

    if ( function_exists( 'wp_styles' ) && wp_styles() ) {
        wp_styles()->done = $styles_done;
    }
    if ( function_exists( 'wp_scripts' ) && wp_scripts() ) {
        wp_scripts()->done = $scripts_done;
    }
    $_GET = $original_get;

    $body = faktorak_extract_html_body_content( $html );
    if ( '' === $body ) {
        return '';
    }

    $body = preg_replace( '#<div class="print-buttons".*?</div>#is', '', $body );
    $body = preg_replace( '#<div class="fak-actions".*?</div>#is', '', $body );
    $body = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $body );

    return trim( (string) $body );
}

function faktorak_ajax_search_products() {
    if ( ! faktorak_can_manage_manual_invoices() ) {
        wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
    }
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'faktorak_manual_invoice_search' ) ) {
        wp_send_json_error( array( 'message' => 'invalid_nonce' ), 400 );
    }

    $query = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
    if ( strlen( $query ) < 2 ) {
        wp_send_json_success( array() );
    }

    $ids = get_posts(
        array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            's'              => $query,
            'fields'         => 'ids',
        )
    );

    $sku_ids = get_posts(
        array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_sku',
                    'value'   => $query,
                    'compare' => 'LIKE',
                ),
            ),
        )
    );
    $ids = array_values( array_unique( array_map( 'absint', array_merge( (array) $ids, (array) $sku_ids ) ) ) );

    $items = array();
    foreach ( $ids as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            continue;
        }
        $items[] = array(
            'id'         => $product->get_id(),
            'name'       => $product->get_name(),
            'sku'        => $product->get_sku(),
            'price_raw'  => (float) $product->get_price(),
            'price_html' => wp_strip_all_tags( wc_price( $product->get_price() ) ),
        );
    }

    wp_send_json_success( array_slice( $items, 0, 20 ) );
}

function faktorak_ajax_search_customers() {
    if ( ! faktorak_can_manage_manual_invoices() ) {
        wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
    }

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'faktorak_manual_customer_search' ) ) {
        wp_send_json_error( array( 'message' => 'invalid_nonce' ), 400 );
    }

    $query = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
    if ( strlen( $query ) < 2 ) {
        wp_send_json_success( array() );
    }

    $users = get_users(
        array(
            'number'         => 20,
            'search'         => '*' . $query . '*',
            'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
        )
    );
    $ids = array();
    foreach ( $users as $user ) {
        $ids[] = (int) $user->ID;
    }

    $phone_query = new WP_User_Query(
        array(
            'number'     => 20,
            'fields'     => 'all',
            'meta_query' => array(
                array(
                    'key'     => 'billing_phone',
                    'value'   => $query,
                    'compare' => 'LIKE',
                ),
            ),
        )
    );
    foreach ( $phone_query->get_results() as $user ) {
        $ids[] = (int) $user->ID;
    }
    $ids = array_values( array_unique( $ids ) );

    $items = array();
    foreach ( $ids as $user_id ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            continue;
        }
        $customer  = new WC_Customer( $user_id );
        $billing   = $customer->get_billing();
        $full_name = trim( (string) $billing['first_name'] . ' ' . (string) $billing['last_name'] );
        if ( '' === $full_name ) {
            $full_name = $user->display_name;
        }

        $items[] = array(
            'id'         => $user_id,
            'name'       => $full_name,
            'email'      => $billing['email'] ? $billing['email'] : $user->user_email,
            'phone'      => $billing['phone'],
            'first_name' => $billing['first_name'],
            'last_name'  => $billing['last_name'],
            'state'      => $billing['state'],
            'city'       => $billing['city'],
            'postcode'   => $billing['postcode'],
            'address_1'  => $billing['address_1'],
        );
    }

    wp_send_json_success( array_slice( $items, 0, 20 ) );
}

function faktorak_shipping_invoice_add_order_metabox() {
    $screen = get_current_screen();
    if ($screen && ($screen->id === 'shop_order' || $screen->id === 'woocommerce_page_wc-orders')) {
        add_meta_box(
            'shipping_invoice_metabox', 'فـاکتـورک', 'faktorak_shipping_invoice_metabox_callback',
            null, 'side', 'default'
        );
    }
}

function faktorak_shipping_invoice_metabox_callback($post_or_order) {
    $order_id = is_a($post_or_order, 'WC_Order') ? $post_or_order->get_id() : $post_or_order->ID;
    $urls = faktorak_get_admin_document_urls( $order_id );
    ?>
    <div class="faktorak-scope faktorak-metabox-buttons">
        <a href="<?php echo esc_url( $urls['shipping_label'] ); ?>" target="_blank" rel="noopener" class="button button-primary faktorak-btn">برچسب پستی</a>
        <a href="<?php echo esc_url( $urls['invoice'] ); ?>" target="_blank" rel="noopener" class="button button-primary faktorak-btn">مشاهده فاکتور</a>
    </div>
    <?php
}

function faktorak_get_admin_document_urls( $order_id ) {
    return array(
        'invoice'        => faktorak_get_order_invoice_url( $order_id, 'admin' ),
        'shipping_label' => faktorak_get_order_shipping_label_url( $order_id, 'admin' ),
    );
}

function faktorak_add_orders_list_column( $columns ) {
    if ( ! is_array( $columns ) || isset( $columns['faktorak_actions'] ) ) {
        return $columns;
    }

    $new_columns = array();
    $inserted    = false;
    foreach ( $columns as $key => $label ) {
        $new_columns[ $key ] = $label;
        if ( ! $inserted && in_array( $key, array( 'order_status', 'order_total', 'wc_actions' ), true ) ) {
            $new_columns['faktorak_actions'] = __( 'فاکتورک', 'faktorak' );
            $inserted = true;
        }
    }

    if ( ! $inserted ) {
        $new_columns['faktorak_actions'] = __( 'فاکتورک', 'faktorak' );
    }

    return $new_columns;
}

function faktorak_render_order_list_actions_html( $order_id ) {
    if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) ) {
        echo '&mdash;';
        return;
    }

    $urls = faktorak_get_admin_document_urls( $order_id );
    if ( empty( $urls['invoice'] ) || empty( $urls['shipping_label'] ) ) {
        echo '&mdash;';
        return;
    }
    ?>
    <div class="faktorak-order-actions">
        <a href="<?php echo esc_url( $urls['invoice'] ); ?>" target="_blank" rel="noopener" class="button button-small">فاکتور</a>
        <a href="<?php echo esc_url( $urls['shipping_label'] ); ?>" target="_blank" rel="noopener" class="button button-small">برچسب</a>
    </div>
    <?php
}

function faktorak_render_orders_list_column_legacy( $column, $post_id = 0 ) {
    if ( 'faktorak_actions' !== $column ) {
        return;
    }
    faktorak_render_order_list_actions_html( $post_id );
}

function faktorak_render_orders_list_column_hpos( $column, $order_or_id = null ) {
    if ( 'faktorak_actions' !== $column ) {
        return;
    }

    $order_id = 0;
    if ( is_object( $order_or_id ) && method_exists( $order_or_id, 'get_id' ) ) {
        $order_id = (int) $order_or_id->get_id();
    } elseif ( is_numeric( $order_or_id ) ) {
        $order_id = absint( $order_or_id );
    }

    faktorak_render_order_list_actions_html( $order_id );
}

function faktorak_register_orders_bulk_print_actions( $actions ) {
    if ( ! is_array( $actions ) ) {
        $actions = array();
    }
    $actions['faktorak_bulk_print_invoices'] = 'چاپ گروهی فاکتور';
    $actions['faktorak_bulk_print_labels']   = 'چاپ گروهی برچسب';
    return $actions;
}

function faktorak_handle_orders_bulk_print_action( $redirect_to, $doaction, $order_ids ) {
    if ( ! in_array( $doaction, array( 'faktorak_bulk_print_invoices', 'faktorak_bulk_print_labels' ), true ) ) {
        return $redirect_to;
    }

    if ( ! faktorak_can_manage_manual_invoices() ) {
        return $redirect_to;
    }

    $order_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $order_ids ) ) ) );
    if ( empty( $order_ids ) ) {
        return $redirect_to;
    }

    $print_type = ( 'faktorak_bulk_print_labels' === $doaction ) ? 'label' : 'invoice';
    $token      = wp_generate_password( 20, false, false );
    $key        = 'faktorak_bulk_print_orders_' . $token;
    $payload    = array(
        'user_id'    => get_current_user_id(),
        'order_ids'  => $order_ids,
        'print_type' => $print_type,
    );

    set_transient( $key, $payload, 10 * MINUTE_IN_SECONDS );

    return add_query_arg(
        array(
            'action' => 'faktorak_bulk_print_orders',
            'token'  => $token,
        ),
        admin_url( 'admin-post.php' )
    );
}

function faktorak_handle_bulk_print_orders() {
    if ( ! faktorak_can_manage_manual_invoices() ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز.', 'faktorak' ) );
    }

    $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
    if ( '' === $token ) {
        wp_die( esc_html__( 'درخواست چاپ نامعتبر است.', 'faktorak' ) );
    }

    $key     = 'faktorak_bulk_print_orders_' . $token;
    $payload = get_transient( $key );
    delete_transient( $key );

    if ( ! is_array( $payload ) || empty( $payload['order_ids'] ) ) {
        wp_die( esc_html__( 'اطلاعات چاپ منقضی شده یا معتبر نیست.', 'faktorak' ) );
    }

    $user_id = isset( $payload['user_id'] ) ? absint( $payload['user_id'] ) : 0;
    if ( $user_id !== get_current_user_id() ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز.', 'faktorak' ) );
    }

    $order_ids  = array_values( array_unique( array_filter( array_map( 'absint', (array) $payload['order_ids'] ) ) ) );
    $print_type = isset( $payload['print_type'] ) ? sanitize_key( $payload['print_type'] ) : 'invoice';
    if ( ! in_array( $print_type, array( 'invoice', 'label' ), true ) ) {
        $print_type = 'invoice';
    }

    faktorak_render_bulk_print_page( $order_ids, $print_type, false );
}

function faktorak_enqueue_admin_styles($hook) {
    $is_faktorak_screen = ( $hook === 'toplevel_page_shipping-invoice-settings' )
        || ( isset( $_GET['page'] ) && sanitize_key( wp_unslash( $_GET['page'] ) ) === 'shipping-invoice-settings' );

    if ( $is_faktorak_screen ) {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
    }
    $is_settings_page = ( 'toplevel_page_shipping-invoice-settings' === $hook );
    $is_manual_page   = ( false !== strpos( (string) $hook, 'faktorak-manual-invoices' ) );
    $screen           = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    $is_order_screen  = ( $screen && in_array( $screen->id, array( 'shop_order', 'woocommerce_page_wc-orders' ), true ) );
    $manual_css_file  = plugin_dir_path( __FILE__ ) . '../assets/css/manual-invoice-admin.css';
    $admin_css_file   = plugin_dir_path( __FILE__ ) . '../assets/css/admin-ui.css';
    $manual_js_file   = plugin_dir_path( __FILE__ ) . '../assets/js/manual-invoice-admin.js';
    $settings_js_file = plugin_dir_path( __FILE__ ) . '../assets/js/admin-settings.js';
    $manual_css_ver   = file_exists( $manual_css_file ) ? (string) filemtime( $manual_css_file ) : ( defined( 'FAKTORAK_VERSION' ) ? FAKTORAK_VERSION : '1.0.0' );
    $admin_css_ver    = file_exists( $admin_css_file ) ? (string) filemtime( $admin_css_file ) : ( defined( 'FAKTORAK_VERSION' ) ? FAKTORAK_VERSION : '1.0.0' );
    $manual_js_ver    = file_exists( $manual_js_file ) ? (string) filemtime( $manual_js_file ) : ( defined( 'FAKTORAK_VERSION' ) ? FAKTORAK_VERSION : '1.0.0' );
    $settings_js_ver  = file_exists( $settings_js_file ) ? (string) filemtime( $settings_js_file ) : ( defined( 'FAKTORAK_VERSION' ) ? FAKTORAK_VERSION : '1.0.0' );

    if ( $is_settings_page || $is_manual_page ) {
        faktorak_enqueue_custom_fonts_style();
        wp_enqueue_media();
        wp_enqueue_script(
            'faktorak-admin-settings',
            plugin_dir_url( __FILE__ ) . '../assets/js/admin-settings.js',
            array( 'jquery' ),
            $settings_js_ver,
            true
        );
    }

    if ( $is_manual_page ) {
        faktorak_enqueue_custom_fonts_style();
        wp_enqueue_style(
            'faktorak-manual-invoice-admin',
            plugin_dir_url( __FILE__ ) . '../assets/css/manual-invoice-admin.css',
            array( 'faktorak-custom-fonts' ),
            $manual_css_ver
        );
        wp_enqueue_script(
            'faktorak-manual-invoice-admin',
            plugin_dir_url( __FILE__ ) . '../assets/js/manual-invoice-admin.js',
            array( 'jquery' ),
            $manual_js_ver,
            true
        );
        wp_localize_script(
            'faktorak-manual-invoice-admin',
            'faktorakManualInvoice',
            array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'productNonce'   => wp_create_nonce( 'faktorak_manual_invoice_search' ),
                'customerNonce'  => wp_create_nonce( 'faktorak_manual_customer_search' ),
                'searchMinChars' => 2,
            )
        );
    }

    if ( $is_settings_page || $is_manual_page || $is_order_screen ) {
        faktorak_enqueue_custom_fonts_style();
        wp_enqueue_style(
            'faktorak-admin-ui',
            plugin_dir_url( __FILE__ ) . '../assets/css/admin-ui.css',
            array( 'faktorak-custom-fonts' ),
            $admin_css_ver
        );
    }

    if ( $is_order_screen ) {
        wp_enqueue_script(
            'faktorak-qrcodejs',
            plugin_dir_url( __FILE__ ) . '../assets/vendor/qrcodejs/qrcode.min.js',
            array(),
            '1.0.0',
            true
        );
        wp_enqueue_script(
            'faktorak-qr-render',
            plugin_dir_url( __FILE__ ) . '../assets/js/qr-render.js',
            array( 'faktorak-qrcodejs' ),
            '1.0.0',
            true
        );
    }
}


/*----------------------------
  PUBLIC FUNCTIONS
----------------------------*/

function faktorak_enqueue_print_assets( $context ) {
    $assets_url = faktorak_get_assets_url();
    faktorak_enqueue_custom_fonts_style();

    if ( 'shipping-label' === $context ) {
        wp_enqueue_style(
            'faktorak-shipping-label',
            $assets_url . 'css/shipping-label.css',
            array( 'faktorak-custom-fonts' ),
            '1.0.0'
        );
        wp_enqueue_script(
            'faktorak-qrcodejs',
            $assets_url . 'vendor/qrcodejs/qrcode.min.js',
            array(),
            '1.0.0',
            true
        );
        wp_enqueue_script(
            'faktorak-qr-render',
            $assets_url . 'js/qr-render.js',
            array( 'faktorak-qrcodejs' ),
            '1.0.0',
            true
        );
        return;
    }

    if ( 'invoice-modern' === $context ) {
        wp_enqueue_style(
            'faktorak-invoice-modern',
            $assets_url . 'css/invoice-template-modern.css',
            array( 'faktorak-custom-fonts' ),
            '1.0.0'
        );
        wp_enqueue_script(
            'faktorak-qrcodejs',
            $assets_url . 'vendor/qrcodejs/qrcode.min.js',
            array(),
            '1.0.0',
            true
        );
        wp_enqueue_script(
            'faktorak-qr-render',
            $assets_url . 'js/qr-render.js',
            array( 'faktorak-qrcodejs' ),
            '1.0.0',
            true
        );
        return;
    }

    wp_enqueue_style(
        'faktorak-invoice-classic',
        $assets_url . 'css/invoice-template.css',
        array( 'faktorak-custom-fonts' ),
        '1.0.0'
    );
}

function faktorak_shipping_invoice_add_user_buttons_frontend($order) {
    if (!$order || !is_a($order, 'WC_Order')) return;
    $order_id = $order->get_id();

    // ✅ توکن امنیتی لینک فاکتور
    $token = wp_create_nonce('faktorak_inv_' . $order_id);

    $invoice_url = add_query_arg(
        array(
            'invoice'         => 'true',
            'order_id'        => $order_id,
            'context'         => 'user',
            'referrer'        => urlencode(wc_get_account_endpoint_url('orders')),
            'faktorak_token'  => $token,
        ),
        home_url()
    );
    ?>
    <div class="shipping-invoice-buttons faktorak-scope faktorak-user-invoice-buttons">
        <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="button faktorak-btn">مشاهده فاکتور</a>
    </div>
    <?php
}

function faktorak_shipping_invoice_display_content() {
    if (isset($_GET['shipping_label']) && $_GET['shipping_label'] === 'true' && isset($_GET['order_id'])) {
        faktorak_enqueue_print_assets( 'shipping-label' );
        include_once plugin_dir_path(__FILE__) . '../templates/shipping-label.php';
        exit;
    }
    if (isset($_GET['invoice']) && $_GET['invoice'] === 'true' && isset($_GET['order_id'])) {

        // ✅ اعتبارسنجی توکن برای فرانت (ادمین معاف است)
        $is_admin_ctx = isset($_GET['context']) && $_GET['context'] === 'admin';
        if ( ! $is_admin_ctx ) {
            $order_id = absint($_GET['order_id']);
            $token    = isset($_GET['faktorak_token']) ? sanitize_text_field(wp_unslash($_GET['faktorak_token'])) : '';

            if ( empty($order_id) || empty($token) || ! wp_verify_nonce($token, 'faktorak_inv_' . $order_id) ) {
                wp_die( esc_html__( 'دسترسی غیرمجاز به فاکتور.', 'faktorak' ) );
            }
        }

        // ✅ انتخاب قالب فاکتور (مدرن/کلاسیک) بر اساس تنظیمات
        $settings = new Faktorak_Shipping_Invoice_Settings();
        $tpl = $settings->get_setting('invoice_template');
        if ($tpl === 'template-1') { $tpl = 'classic'; } // سازگاری با مقدار قدیمی
        $template_file = ($tpl === 'modern') ? 'invoice-template-modern.php' : 'invoice-template.php';
        faktorak_enqueue_print_assets( ($tpl === 'modern') ? 'invoice-modern' : 'invoice-classic' );

        include_once plugin_dir_path(__FILE__) . '../templates/' . $template_file;
        exit;
    }
}

function faktorak_shipping_invoice_rewrite_rules() {
    add_rewrite_rule('^shipping-label/?$', 'index.php?shipping_label=true', 'top');
    add_rewrite_rule('^invoice/?$', 'index.php?invoice=true', 'top');
}

function faktorak_shipping_invoice_query_vars($vars) {
    $vars[] = 'shipping_label';
    $vars[] = 'invoice';
    $vars[] = 'order_id';
    return $vars;
}

function faktorak_enqueue_frontend_styles() {
    faktorak_enqueue_custom_fonts_style();
    wp_enqueue_style(
        'faktorak-frontend-ui',
        plugin_dir_url( __FILE__ ) . '../assets/css/frontend-ui.css',
        array( 'faktorak-custom-fonts' ),
        defined( 'FAKTORAK_VERSION' ) ? FAKTORAK_VERSION : '1.0.0'
    );
}


/*----------------------------
  SHORTCODE FUNCTIONS
----------------------------*/

function faktorak_invoice_button_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'order_id' => 0,
            'text'     => __('مشاهده فاکتور', 'faktorak'),
            'class'    => 'button faktorak-invoice-btn faktorak-btn',
            'icon'     => ''
        ),
        $atts,
        'faktorak_invoice_button'
    );

    $order_id = absint($atts['order_id']);
    if (!$order_id) {
        if (is_wc_endpoint_url('view-order')) {
            $order_id = absint(get_query_var('view-order'));
        } elseif (is_wc_endpoint_url('order-received')) {
            $order_id = absint(get_query_var('order-received'));
        }
    }
    if (!$order_id) return '';
    $order = wc_get_order($order_id);
    if (!$order) return '';

    if (!is_user_logged_in() && is_wc_endpoint_url('order-received')) {
        $order_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
        if ($order->get_order_key() !== $order_key) return '';
    }

    // ✅ توکن امنیتی لینک فاکتور
    $token = wp_create_nonce('faktorak_inv_' . $order_id);

    $url = add_query_arg(array(
        'invoice'        => 'true',
        'order_id'       => $order_id,
        'context'        => 'frontend',
        'faktorak_token' => $token,
    ), home_url());

    $icon_html = '';
    if (!empty($atts['icon'])) {
        $icon_html = '<img src="' . esc_url($atts['icon']) . '" alt="' . esc_attr($atts['text']) . '" class="pymdashboardviword-invoice-icon">';
    }

    $link = sprintf(
        '<a href="%s" target="_blank" rel="noopener" class="%s">%s%s</a>',
        esc_url($url),
        esc_attr($atts['class']),
        $icon_html,
        esc_html($atts['text'])
    );

    return '<div class="faktorak-scope">' . $link . '</div>';
}

function faktorak_proforma_invoice_button_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'order_id' => 0,
        'text'     => __( 'صدور پیش‌فاکتور', 'faktorak' ),
        'class'    => 'button faktorak-proforma-btn faktorak-btn',
    ), $atts, 'faktorak_proforma_button' );

    $order_id = absint( $atts['order_id'] );
    if ( $order_id ) {
        // ✅ توکن امنیتی برای لینک پیش‌فاکتورِ سفارش موجود
        $token = wp_create_nonce('faktorak_inv_' . $order_id);
        $url = add_query_arg( array(
            'invoice'        => 'true',
            'order_id'       => $order_id,
            'is_proforma'    => 'true',
            'context'        => 'frontend',
            'faktorak_token' => $token,
        ), home_url() );
    } else {
        $is_checkout_context = function_exists( 'is_checkout' ) && is_checkout();
        if ( ! is_user_logged_in() && ! $is_checkout_context ) {
            $url = faktorak_get_proforma_prepare_checkout_url();
        } else {
            $base_url = $is_checkout_context ? wc_get_checkout_url() : wc_get_cart_url();
            $url      = faktorak_get_proforma_create_url( $base_url );
        }
    }

    $link = sprintf(
        '<a href="%s" target="_blank" rel="noopener" class="%s">%s</a>',
        esc_url( $url ),
        esc_attr( $atts['class'] ),
        esc_html( $atts['text'] )
    );

    return '<div class="faktorak-scope">' . $link . '</div>';
}


/*----------------------------
  PROFORMA INVOICE FUNCTIONS
----------------------------*/

function faktorak_register_proforma_invoice_order_status() {
    register_post_status('wc-proforma-invoice', array(
        'label' => 'پیش فاکتور',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('پیش فاکتور <span class="count">(%s)</span>', 'پیش فاکتورها <span class="count">(%s)</span>', 'faktorak')
    ));
}

function faktorak_add_proforma_invoice_to_order_statuses($order_statuses) {
    $new_order_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-proforma-invoice'] = 'پیش فاکتور';
        }
    }
    return $new_order_statuses;
}

function faktorak_get_proforma_create_url( $base_url = '' ) {
    if ( '' === $base_url ) {
        $base_url = wc_get_cart_url();
    }

    return wp_nonce_url(
        add_query_arg( 'create_proforma_invoice', 'true', $base_url ),
        'faktorak_create_proforma'
    );
}

function faktorak_get_proforma_prepare_checkout_url() {
    return add_query_arg( 'faktorak_prepare_proforma', 'true', wc_get_checkout_url() );
}

function faktorak_get_proforma_customer_addresses_from_session() {
    $billing  = array();
    $shipping = array();

    if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
        return array( $billing, $shipping );
    }

    $customer = WC()->customer;

    $billing = array(
        'first_name' => $customer->get_billing_first_name(),
        'last_name'  => $customer->get_billing_last_name(),
        'company'    => $customer->get_billing_company(),
        'country'    => $customer->get_billing_country(),
        'state'      => $customer->get_billing_state(),
        'city'       => $customer->get_billing_city(),
        'postcode'   => $customer->get_billing_postcode(),
        'address_1'  => $customer->get_billing_address_1(),
        'address_2'  => $customer->get_billing_address_2(),
        'phone'      => $customer->get_billing_phone(),
        'email'      => $customer->get_billing_email(),
    );

    $shipping = array(
        'first_name' => $customer->get_shipping_first_name(),
        'last_name'  => $customer->get_shipping_last_name(),
        'company'    => $customer->get_shipping_company(),
        'country'    => $customer->get_shipping_country(),
        'state'      => $customer->get_shipping_state(),
        'city'       => $customer->get_shipping_city(),
        'postcode'   => $customer->get_shipping_postcode(),
        'address_1'  => $customer->get_shipping_address_1(),
        'address_2'  => $customer->get_shipping_address_2(),
    );

    if ( '' === trim( (string) $shipping['address_1'] ) ) {
        $shipping = array(
            'first_name' => $billing['first_name'],
            'last_name'  => $billing['last_name'],
            'company'    => $billing['company'],
            'country'    => $billing['country'],
            'state'      => $billing['state'],
            'city'       => $billing['city'],
            'postcode'   => $billing['postcode'],
            'address_1'  => $billing['address_1'],
            'address_2'  => $billing['address_2'],
        );
    }

    return array( $billing, $shipping );
}

function faktorak_get_proforma_customer_addresses_from_checkout_post() {
    $billing = array(
        'first_name' => isset( $_POST['billing_first_name'] ) ? wc_clean( wp_unslash( $_POST['billing_first_name'] ) ) : '',
        'last_name'  => isset( $_POST['billing_last_name'] ) ? wc_clean( wp_unslash( $_POST['billing_last_name'] ) ) : '',
        'company'    => isset( $_POST['billing_company'] ) ? wc_clean( wp_unslash( $_POST['billing_company'] ) ) : '',
        'country'    => isset( $_POST['billing_country'] ) ? wc_clean( wp_unslash( $_POST['billing_country'] ) ) : '',
        'state'      => isset( $_POST['billing_state'] ) ? wc_clean( wp_unslash( $_POST['billing_state'] ) ) : '',
        'city'       => isset( $_POST['billing_city'] ) ? wc_clean( wp_unslash( $_POST['billing_city'] ) ) : '',
        'postcode'   => isset( $_POST['billing_postcode'] ) ? wc_clean( wp_unslash( $_POST['billing_postcode'] ) ) : '',
        'address_1'  => isset( $_POST['billing_address_1'] ) ? wc_clean( wp_unslash( $_POST['billing_address_1'] ) ) : '',
        'address_2'  => isset( $_POST['billing_address_2'] ) ? wc_clean( wp_unslash( $_POST['billing_address_2'] ) ) : '',
        'phone'      => isset( $_POST['billing_phone'] ) ? wc_clean( wp_unslash( $_POST['billing_phone'] ) ) : '',
        'email'      => isset( $_POST['billing_email'] ) ? sanitize_email( wp_unslash( $_POST['billing_email'] ) ) : '',
    );

    $ship_to_different = isset( $_POST['ship_to_different_address'] ) && '1' === wc_clean( wp_unslash( $_POST['ship_to_different_address'] ) );
    if ( $ship_to_different ) {
        $shipping = array(
            'first_name' => isset( $_POST['shipping_first_name'] ) ? wc_clean( wp_unslash( $_POST['shipping_first_name'] ) ) : '',
            'last_name'  => isset( $_POST['shipping_last_name'] ) ? wc_clean( wp_unslash( $_POST['shipping_last_name'] ) ) : '',
            'company'    => isset( $_POST['shipping_company'] ) ? wc_clean( wp_unslash( $_POST['shipping_company'] ) ) : '',
            'country'    => isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : '',
            'state'      => isset( $_POST['shipping_state'] ) ? wc_clean( wp_unslash( $_POST['shipping_state'] ) ) : '',
            'city'       => isset( $_POST['shipping_city'] ) ? wc_clean( wp_unslash( $_POST['shipping_city'] ) ) : '',
            'postcode'   => isset( $_POST['shipping_postcode'] ) ? wc_clean( wp_unslash( $_POST['shipping_postcode'] ) ) : '',
            'address_1'  => isset( $_POST['shipping_address_1'] ) ? wc_clean( wp_unslash( $_POST['shipping_address_1'] ) ) : '',
            'address_2'  => isset( $_POST['shipping_address_2'] ) ? wc_clean( wp_unslash( $_POST['shipping_address_2'] ) ) : '',
        );
    } else {
        $shipping = array(
            'first_name' => $billing['first_name'],
            'last_name'  => $billing['last_name'],
            'company'    => $billing['company'],
            'country'    => $billing['country'],
            'state'      => $billing['state'],
            'city'       => $billing['city'],
            'postcode'   => $billing['postcode'],
            'address_1'  => $billing['address_1'],
            'address_2'  => $billing['address_2'],
        );
    }

    return array( $billing, $shipping );
}

function faktorak_store_checkout_addresses_to_customer_session( $billing, $shipping ) {
    if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
        return;
    }

    $customer = WC()->customer;

    $customer->set_billing_first_name( isset( $billing['first_name'] ) ? $billing['first_name'] : '' );
    $customer->set_billing_last_name( isset( $billing['last_name'] ) ? $billing['last_name'] : '' );
    $customer->set_billing_company( isset( $billing['company'] ) ? $billing['company'] : '' );
    $customer->set_billing_country( isset( $billing['country'] ) ? $billing['country'] : '' );
    $customer->set_billing_state( isset( $billing['state'] ) ? $billing['state'] : '' );
    $customer->set_billing_city( isset( $billing['city'] ) ? $billing['city'] : '' );
    $customer->set_billing_postcode( isset( $billing['postcode'] ) ? $billing['postcode'] : '' );
    $customer->set_billing_address_1( isset( $billing['address_1'] ) ? $billing['address_1'] : '' );
    $customer->set_billing_address_2( isset( $billing['address_2'] ) ? $billing['address_2'] : '' );
    $customer->set_billing_phone( isset( $billing['phone'] ) ? $billing['phone'] : '' );
    $customer->set_billing_email( isset( $billing['email'] ) ? $billing['email'] : '' );

    $customer->set_shipping_first_name( isset( $shipping['first_name'] ) ? $shipping['first_name'] : '' );
    $customer->set_shipping_last_name( isset( $shipping['last_name'] ) ? $shipping['last_name'] : '' );
    $customer->set_shipping_company( isset( $shipping['company'] ) ? $shipping['company'] : '' );
    $customer->set_shipping_country( isset( $shipping['country'] ) ? $shipping['country'] : '' );
    $customer->set_shipping_state( isset( $shipping['state'] ) ? $shipping['state'] : '' );
    $customer->set_shipping_city( isset( $shipping['city'] ) ? $shipping['city'] : '' );
    $customer->set_shipping_postcode( isset( $shipping['postcode'] ) ? $shipping['postcode'] : '' );
    $customer->set_shipping_address_1( isset( $shipping['address_1'] ) ? $shipping['address_1'] : '' );
    $customer->set_shipping_address_2( isset( $shipping['address_2'] ) ? $shipping['address_2'] : '' );
    $customer->save();
}

function faktorak_validate_guest_proforma_customer_data( $billing ) {
    $required_keys = array( 'first_name', 'last_name', 'phone', 'address_1', 'city' );
    $field_labels  = array();

    if ( function_exists( 'WC' ) && WC()->checkout() ) {
        $billing_fields = WC()->checkout()->get_checkout_fields( 'billing' );
        if ( is_array( $billing_fields ) && ! empty( $billing_fields ) ) {
            $required_keys = array();
            foreach ( $billing_fields as $field_key => $field_config ) {
                if ( empty( $field_config['required'] ) || 0 !== strpos( (string) $field_key, 'billing_' ) ) {
                    continue;
                }

                $key = substr( (string) $field_key, 8 );
                if ( '' === $key ) {
                    continue;
                }

                $required_keys[]      = $key;
                $field_labels[ $key ] = ! empty( $field_config['label'] ) ? (string) $field_config['label'] : $key;
            }
            $required_keys = array_values( array_unique( $required_keys ) );
        }
    }

    $missing = array();
    foreach ( $required_keys as $key ) {
        $value = isset( $billing[ $key ] ) ? trim( (string) $billing[ $key ] ) : '';
        if ( '' === $value ) {
            $missing[] = isset( $field_labels[ $key ] ) ? $field_labels[ $key ] : $key;
        }
    }

    return $missing;
}

function faktorak_add_proforma_invoice_button_on_cart() {
    if ( WC()->cart->is_empty() ) {
        return;
    }

    if ( is_user_logged_in() ) {
        $url  = faktorak_get_proforma_create_url( wc_get_cart_url() );
        $text = 'دریافت پیش فاکتور';
    } else {
        $url  = faktorak_get_proforma_prepare_checkout_url();
        $text = 'تکمیل اطلاعات و دریافت پیش فاکتور';
    }

    echo '<div class="faktorak-scope faktorak-proforma-wrap"><a href="' . esc_url( $url ) . '" class="button faktorak-btn">' . esc_html( $text ) . '</a></div>';
}

function faktorak_maybe_render_checkout_proforma_cta() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_wc_endpoint_url( 'order-pay' ) ) {
        return;
    }

    if ( ! isset( $_GET['faktorak_prepare_proforma'] ) || 'true' !== sanitize_text_field( wp_unslash( $_GET['faktorak_prepare_proforma'] ) ) ) {
        return;
    }

    if ( WC()->cart->is_empty() ) {
        return;
    }
    $proforma_nonce = wp_create_nonce( 'faktorak_create_proforma_checkout' );
    ?>
    <div class="woocommerce-info faktorak-proforma-checkout-cta" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
        <span>برای صدور پیش‌فاکتور، ابتدا اطلاعات گیرنده را کامل کنید و سپس دکمه «صدور پیش‌فاکتور» را بزنید.</span>
        <button type="button" class="button alt" onclick="faktorakSubmitProformaCheckout(this);" data-faktorak-proforma-nonce="<?php echo esc_attr( $proforma_nonce ); ?>">صدور پیش‌فاکتور</button>
    </div>
    <script>
    if (typeof window.faktorakSubmitProformaCheckout !== 'function') {
        window.faktorakSubmitProformaCheckout = function(button){
            var form = document.querySelector('form.checkout');
            if (!form) { return; }

            var marker = form.querySelector('input[name="faktorak_create_proforma_checkout"]');
            if (!marker) {
                marker = document.createElement('input');
                marker.type = 'hidden';
                marker.name = 'faktorak_create_proforma_checkout';
                form.appendChild(marker);
            }
            marker.value = '1';

            var nonceField = form.querySelector('input[name="faktorak_create_proforma_checkout_nonce"]');
            if (!nonceField) {
                nonceField = document.createElement('input');
                nonceField.type = 'hidden';
                nonceField.name = 'faktorak_create_proforma_checkout_nonce';
                form.appendChild(nonceField);
            }
            nonceField.value = (button && button.getAttribute('data-faktorak-proforma-nonce')) ? button.getAttribute('data-faktorak-proforma-nonce') : '';

            HTMLFormElement.prototype.submit.call(form);
        };
    }
    </script>
    <?php
}

function faktorak_handle_create_proforma_invoice() {
    $request_method     = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
    $is_checkout_submit = ( 'POST' === $request_method ) && isset( $_POST['faktorak_create_proforma_checkout'] );
    $is_direct_get      = isset( $_GET['create_proforma_invoice'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['create_proforma_invoice'] ) );

    if ( ! $is_checkout_submit && ! $is_direct_get ) {
        return;
    }

    if ( $is_checkout_submit ) {
        if ( ! isset( $_POST['faktorak_create_proforma_checkout_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['faktorak_create_proforma_checkout_nonce'] ) ), 'faktorak_create_proforma_checkout' ) ) {
            wc_add_notice( 'خطای امنیتی در صدور پیش‌فاکتور. لطفاً دوباره تلاش کنید.', 'error' );
            wp_safe_redirect( faktorak_get_proforma_prepare_checkout_url() );
            exit;
        }
    } elseif ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'faktorak_create_proforma' ) ) {
        wp_die( esc_html__( 'خطای امنیتی. لطفاً دوباره تلاش کنید.', 'faktorak' ) );
    }

    if ( WC()->cart->is_empty() ) {
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }

    $is_guest = ! is_user_logged_in();
    if ( $is_checkout_submit ) {
        list( $billing_data, $shipping_data ) = faktorak_get_proforma_customer_addresses_from_checkout_post();
        faktorak_store_checkout_addresses_to_customer_session( $billing_data, $shipping_data );
    } else {
        list( $billing_data, $shipping_data ) = faktorak_get_proforma_customer_addresses_from_session();
    }

    if ( $is_guest ) {
        $missing_fields = faktorak_validate_guest_proforma_customer_data( $billing_data );
        if ( ! empty( $missing_fields ) ) {
            wc_add_notice(
                'برای صدور پیش‌فاکتور، لطفاً اطلاعات ضروری را کامل کنید: ' . implode( '، ', array_map( 'esc_html', $missing_fields ) ),
                'error'
            );
            wp_safe_redirect( faktorak_get_proforma_prepare_checkout_url() );
            exit;
        }
    }

    try {
        $order = wc_create_order();
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
            $order->add_product( $product, $cart_item['quantity'], array( 'variation' => $cart_item['variation'] ) );
        }

        if ( is_user_logged_in() ) {
            $order->set_customer_id( get_current_user_id() );
        }

        if ( ! empty( array_filter( $billing_data ) ) ) {
            $order->set_address( $billing_data, 'billing' );
        }
        if ( ! empty( array_filter( $shipping_data ) ) ) {
            $order->set_address( $shipping_data, 'shipping' );
        }

        $order->update_meta_data( '_faktorak_doc_type', 'proforma' );
        $order->calculate_totals();
        $order->update_status( 'proforma-invoice', 'پیش فاکتور توسط کاربر ایجاد شد.', true );
        $order_id = $order->get_id();
        if ( $order_id ) {
            // ✅ ریدایرکت به فاکتورِ پیش‌فاکتور با توکن امنیتی
            $token       = wp_create_nonce( 'faktorak_inv_' . $order_id );
            $invoice_url = add_query_arg( array(
                'invoice'        => 'true',
                'order_id'       => $order_id,
                'context'        => 'proforma',
                'is_proforma'    => 'true',
                'faktorak_token' => $token,
            ), home_url() );
            wp_safe_redirect( $invoice_url );
            exit;
        }
    } catch ( Exception $e ) {
        wc_add_notice(
            sprintf(
                esc_html__( 'خطا در ایجاد پیش فاکتور: %s', 'faktorak' ),
                esc_html( $e->getMessage() )
            ),
            'error'
        );
        if ( $is_checkout_submit || $is_guest ) {
            wp_safe_redirect( faktorak_get_proforma_prepare_checkout_url() );
        } else {
            wp_safe_redirect( wc_get_cart_url() );
        }
        exit;
    }
}

function faktorak_convert_proforma_doc_type_to_official( $order ) {
    if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
        return false;
    }

    $is_proforma = ( 'proforma' === $order->get_meta( '_faktorak_doc_type' ) ) || $order->has_status( 'proforma-invoice' );
    if ( ! $is_proforma ) {
        return false;
    }

    if ( ! $order->is_paid() ) {
        return false;
    }

    if ( 'official' === $order->get_meta( '_faktorak_doc_type' ) ) {
        return false;
    }

    $order->update_meta_data( '_faktorak_doc_type', 'official' );
    $order->add_order_note( __( 'نوع سند پس از پرداخت موفق به فاکتور رسمی تغییر کرد.', 'faktorak' ) );
    $order->save();
    return true;
}

function faktorak_convert_proforma_to_official_after_payment( $order_id ) {
    $order_id = absint( $order_id );
    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );
    faktorak_convert_proforma_doc_type_to_official( $order );
}

function faktorak_convert_proforma_to_official_on_status_change( $order_id, $from, $to, $order ) {
    $paid_statuses = function_exists( 'wc_get_is_paid_statuses' ) ? (array) wc_get_is_paid_statuses() : array( 'processing', 'completed' );
    if ( ! in_array( $to, $paid_statuses, true ) ) {
        return;
    }

    if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
        $order = wc_get_order( $order_id );
    }

    faktorak_convert_proforma_doc_type_to_official( $order );
}


/*----------------------------
  CHECKOUT MAP FUNCTIONS
----------------------------*/

function faktorak_enqueue_map_assets() {
    if (is_checkout()) {
        $vendor_url = plugin_dir_url(__FILE__) . '../assets/vendor/';

        wp_enqueue_style('leaflet-css', $vendor_url . 'leaflet/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('leaflet-js', $vendor_url . 'leaflet/leaflet.js', array(), '1.9.4', true);

        wp_enqueue_style('leaflet-geocoder-css', $vendor_url . 'leaflet-control-geocoder/Control.Geocoder.css', array('leaflet-css'), '3.3.1');
        wp_enqueue_script('leaflet-geocoder-js', $vendor_url . 'leaflet-control-geocoder/Control.Geocoder.js', array('leaflet-js'), '3.3.1', true);

        wp_enqueue_style('leaflet-locate-css', $vendor_url . 'leaflet-locatecontrol/L.Control.Locate.min.css', array('leaflet-css'), '0.85.1');
        wp_enqueue_script('leaflet-locate-js', $vendor_url . 'leaflet-locatecontrol/L.Control.Locate.min.js', array('leaflet-js'), '0.85.1', true);

        /* استایل‌های UI نقشه (طبق خواسته تو) */
        $map_css = "
        .leaflet-control-geocoder.leaflet-bar.leaflet-control-geocoder-expanded.leaflet-control{
            margin:0;border:none;padding:5px 0 5px 5px;border-radius:12px;
        }
        .leaflet-control-geocoder-form input[type='search']{ font-size:13px; }
        .fb-map-message{ font-size:14px; font-family:inherit !important; }
        button.leaflet-control-geocoder-icon{ padding:0; border:none; }
        ";
        wp_add_inline_style('leaflet-geocoder-css', $map_css);

        $inline_script = "
        document.addEventListener('DOMContentLoaded', function(){
            if (document.getElementById('fb-map')) {
                const map = L.map('fb-map').setView([35.6892, 51.3890], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                let marker;
                map.on('click', function(e){
                    if(marker) map.removeLayer(marker);
                    marker = L.marker(e.latlng).addTo(map);
                    document.querySelector('#checkout_latlng').value = e.latlng.lat + ',' + e.latlng.lng;
                });

                const geocoder = L.Control.geocoder({
                    collapsed:false, defaultMarkGeocode:false,
                    placeholder:'جستجو شهر یا آدرس...', errorMessage:'هیچ نتیجه‌ای پیدا نشد',
                    geocoder:L.Control.Geocoder.nominatim({ geocodingQueryParams:{ 'accept-language':'fa', 'countrycodes':'ir' } })
                }).on('markgeocode', function(e){
                    const latlng = e.geocode.center;
                    map.setView(latlng, 14);
                    if(marker) map.removeLayer(marker);
                    marker = L.marker(latlng).addTo(map);
                    document.querySelector('#checkout_latlng').value = latlng.lat + ',' + latlng.lng;
                }).addTo(map);

                L.control.locate({
                    position:'topleft', flyTo:true, keepCurrentZoomLevel:false,
                    strings:{ title:'مکان فعلی من' }
                }).addTo(map);
            }
        });";
        wp_add_inline_script('leaflet-geocoder-js', $inline_script);
    }
}

function faktorak_display_checkout_map($checkout){
    echo '<div class="fb-map-message">می‌توانید برای ارسال مطمئن‌تر، لوکیشن خود را روی نقشه ثبت کنید.</div>';
    echo '<div id="fb-map" class="fb-map-canvas"></div>';
    echo '<input type="hidden" id="checkout_latlng" name="checkout_latlng">';
}

function faktorak_save_map_location($order, $data){
    if(!empty($_POST['checkout_latlng'])){
        $order->update_meta_data('_delivery_location', sanitize_text_field($_POST['checkout_latlng']));
    }
}

function faktorak_display_location_in_admin($order){
    $location = $order->get_meta('_delivery_location');
    if($location){
        $maps_url = 'https://www.google.com/maps?q=' . rawurlencode( $location );
        echo '<p><strong>لوکیشن ارسال: </strong><br><a href="'.esc_url($maps_url).'" target="_blank" rel="noopener noreferrer">برای دیدن لوکیشن ارسال کلیک کنید</a></p>';
        echo '<div class="faktorak-qr" data-qr="' . esc_attr( esc_url( $maps_url ) ) . '" data-qr-size="150"></div>';
    }
}


/*--------------------------------------------
  PROFORMA TO ORDER CONVERSION FUNCTIONS
----------------------------------------------*/

function faktorak_add_convert_order_action( $actions ) {
    global $theorder;
    if ( $theorder && $theorder->has_status( 'proforma-invoice' ) ) {
        $actions['faktorak_convert_to_payable'] = __( 'تبدیل به سفارش قابل پرداخت', 'faktorak' );
    }
    return $actions;
}
add_filter( 'woocommerce_order_actions', 'faktorak_add_convert_order_action' );

function faktorak_process_convert_order_action( $order ) {
    $order->update_status( 'pending', __( 'پیش فاکتور توسط مدیر به سفارش قابل پرداخت تبدیل شد.', 'faktorak' ) );
    WC()->payment_gateways();
    WC()->shipping();
    WC()->mailer()->customer_invoice( $order );
    $order->add_order_note( __( 'ایمیل فاکتور به همراه لینک پرداخت برای مشتری ارسال شد.', 'faktorak' ) );
}
add_action( 'woocommerce_order_action_faktorak_convert_to_payable', 'faktorak_process_convert_order_action' );

/*----------------------------
  PROMOTION FUNCTIONS
----------------------------*/

function faktorak_is_plugin_admin_page() {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    if ( in_array(
        $page,
        array(
            'shipping-invoice-settings',
            'faktorak-manual-invoices',
            'faktorak-manual-invoices-list',
        ),
        true
    ) ) {
        return true;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    $screen_id = $screen && isset( $screen->id ) ? (string) $screen->id : '';

    return 'toplevel_page_shipping-invoice-settings' === $screen_id
        || false !== strpos( $screen_id, 'faktorak-manual-invoices' );
}

function faktorak_is_platforms_promo_dismissed() {
    $dismissed_at = (int) get_user_meta( get_current_user_id(), 'faktorak_platforms_promo_dismissed', true );

    if ( ! $dismissed_at ) {
        return false;
    }

    return ( time() - $dismissed_at ) < ( 15 * DAY_IN_SECONDS );
}

function faktorak_platforms_promo_notice() {
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    if ( ! faktorak_is_plugin_admin_page() ) {
        return;
    }

    if ( faktorak_is_platforms_promo_dismissed() ) {
        return;
    }

    $product_url = 'https://sajjadshadloo.ir/product/woocommerce-to-eitaa-bale/';
    $nonce       = wp_create_nonce( 'faktorak_dismiss_platforms_promo' );
    ?>
    <style>
        .faktorak-platforms-notice {
            border-right-color: #111827;
            border-radius: 6px;
            padding: 8px 12px !important;
            font-family: 'iranyekan', sans-serif !important;
        }
        .faktorak-platforms-notice .faktorak-platforms-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            direction: ltr;
        }
        .faktorak-platforms-notice .faktorak-platforms-text {
            margin: 0;
            color: #1d2327;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.7;
            text-align: right;
            direction: rtl;
            font-family: 'iranyekan', sans-serif !important;
        }
        .faktorak-platforms-notice .faktorak-platforms-text a {
            color: #111827;
            text-decoration: none;
            font-family: 'iranyekan', sans-serif !important;
            font-weight: 500;
            display: inline-block;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 3px 9px;
            border-radius: 6px;
            white-space: nowrap;
            margin-right: 6px;
        }
        .faktorak-platforms-notice .faktorak-platforms-text a:hover {
            background: #111827;
            border-color: #111827;
            color: #ffffff;
        }
        .faktorak-platforms-notice .faktorak-platforms-text a:focus-visible {
            outline: 2px solid #111827;
            outline-offset: 2px;
        }
        .faktorak-platforms-notice .faktorak-platforms-close {
            width: 24px;
            height: 24px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #646970;
            font-size: 18px;
            line-height: 24px;
            text-align: center;
            cursor: pointer;
            padding: 0;
            font-family: 'iranyekan', sans-serif !important;
        }
        .faktorak-platforms-notice .faktorak-platforms-close:hover {
            background: #f3f4f6;
            color: #111827;
        }
        @media (max-width: 782px) {
            .faktorak-platforms-notice .faktorak-platforms-wrap {
                align-items: flex-start;
            }
            .faktorak-platforms-notice .faktorak-platforms-text {
                font-size: 13px;
            }
        }
    </style>
    <div class="notice notice-info faktorak-platforms-notice" data-nonce="<?php echo esc_attr( $nonce ); ?>">
        <div class="faktorak-platforms-wrap">
            <button type="button" class="faktorak-platforms-close" aria-label="بستن اعلان">&times;</button>
            <p class="faktorak-platforms-text">
                می‌خوای سفارشات ووکامرس رو خودکار به پیام‌رسان‌های ایرانی مثل ایتا و بله بفرستی؟
                <a href="<?php echo esc_url( $product_url ); ?>" target="_blank" rel="noopener noreferrer">مشاهده افزونه ارسال سفارش به ایتا و بله</a>
            </p>
        </div>
    </div>
    <script>
    (function() {
        var notice = document.querySelector('.faktorak-platforms-notice');
        if (!notice) {
            return;
        }
        var close = notice.querySelector('.faktorak-platforms-close');
        if (!close) {
            return;
        }
        close.addEventListener('click', function() {
            var body = new URLSearchParams();
            body.append('action', 'faktorak_dismiss_platforms_promo');
            body.append('nonce', notice.getAttribute('data-nonce') || '');

            fetch(ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            }).finally(function() {
                notice.remove();
            });
        });
    })();
    </script>
    <?php
}

function faktorak_dismiss_platforms_promo() {
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
    }

    check_ajax_referer( 'faktorak_dismiss_platforms_promo', 'nonce' );

    update_user_meta( get_current_user_id(), 'faktorak_platforms_promo_dismissed', time() );

    wp_send_json_success();
}


function faktorak_order_export_admin_inline_assets() {
    if ( ! isset( $_GET['page'] ) || sanitize_key( wp_unslash( $_GET['page'] ) ) !== 'shipping-invoice-settings' ) {
        return;
    }
    ?>
    <style>
    .faktorak-admin-page .fak-datepicker{direction:ltr!important;text-align:center!important;background:#fff!important}.ui-datepicker{z-index:100001!important;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 22px 55px rgba(17,24,39,.16);padding:12px;direction:rtl;font-family:tahoma,arial,sans-serif}.ui-datepicker-header{display:flex;align-items:center;justify-content:space-between;gap:8px;background:#111827;color:#fff;border-radius:6px;padding:8px;margin-bottom:10px}.ui-datepicker-title{display:flex;gap:6px;align-items:center;justify-content:center;order:2;flex:1}.ui-datepicker-title select{border-radius:6px!important;border:0!important;padding:2px 22px 2px 6px!important;min-height:28px!important;font-size:12px!important}.ui-datepicker-prev,.ui-datepicker-next{cursor:pointer;color:#fff;text-decoration:none;font-size:0;width:28px;height:28px;border-radius:6px;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center}.ui-datepicker-prev:before{content:'›';font-size:20px;line-height:1}.ui-datepicker-next:before{content:'‹';font-size:20px;line-height:1}.ui-datepicker-calendar{width:100%;border-collapse:separate;border-spacing:4px}.ui-datepicker-calendar th{font-size:11px;color:#6b7280;font-weight:700;text-align:center}.ui-datepicker-calendar td{text-align:center}.ui-datepicker-calendar a,.ui-datepicker-calendar span{display:block;min-width:28px;padding:6px 7px;border-radius:6px;text-decoration:none;color:#111827;background:#f9fafb;border:1px solid #eef0f3}.ui-datepicker-calendar a:hover{background:#111827;color:#fff;border-color:#111827}.ui-datepicker-today a{border-color:#111827;font-weight:700}.ui-datepicker-current-day a{background:#111827!important;color:#fff!important}
    </style>
    <?php
}
add_action( 'admin_head', 'faktorak_order_export_admin_inline_assets' );

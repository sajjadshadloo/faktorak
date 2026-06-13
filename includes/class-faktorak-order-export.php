<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Faktorak_Order_Export {

    const NONCE_ACTION = 'faktorak_order_export_action';
    const PAGE_SLUG    = 'shipping-invoice-settings';

    public static function init() {
        add_action( 'admin_post_faktorak_export_orders', array( __CLASS__, 'handle_export' ) );
        add_action( 'admin_post_faktorak_bulk_invoice_pdf', array( __CLASS__, 'handle_bulk_invoice_pdf' ) );
        add_action( 'admin_post_faktorak_bulk_invoice_zip', array( __CLASS__, 'handle_bulk_invoice_zip' ) );
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'شما دسترسی لازم را ندارید.', 'faktorak' ) );
        }

        $limit  = isset( $_GET['faktorak_limit'] ) ? max( 1, min( 500, absint( $_GET['faktorak_limit'] ) ) ) : 100;
        $status = isset( $_GET['faktorak_status'] ) ? sanitize_text_field( wp_unslash( $_GET['faktorak_status'] ) ) : '';
        $date_from = isset( $_GET['faktorak_date_from'] ) ? self::sanitize_date_param( wp_unslash( $_GET['faktorak_date_from'] ) ) : '';
        $date_to   = isset( $_GET['faktorak_date_to'] ) ? self::sanitize_date_param( wp_unslash( $_GET['faktorak_date_to'] ) ) : '';
        $orders = self::get_orders_for_preview( $limit, $status, $date_from, $date_to );
        $statuses = wc_get_order_statuses();
        $columns = self::columns();
        ?>
        <div class="fak-export-page" dir="rtl">
            <div class="fak-export-head">
                <div>
                    <h2>خروجی سفارشات ووکامرس</h2>
                    <p>خروجی CSV، Excel و فاکتور چاپی/PDF را به صورت انتخابی یا دسته‌جمعی بگیرید، بازه تاریخ سفارش را مشخص کنید و دقیقاً تعیین کنید چه اطلاعاتی داخل خروجی باشد.</p>
                </div>
                <div class="fak-export-badge">سازگار با HPOS</div>
            </div>

            <form method="get" class="fak-filter-card">
                <input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
                <input type="hidden" name="tab" value="orders_export">
                <label>
                    تعداد سفارش آخر
                    <input type="number" name="faktorak_limit" min="1" max="500" value="<?php echo esc_attr( $limit ); ?>">
                </label>
                <label>
                    وضعیت سفارش
                    <select name="faktorak_status">
                        <option value="">همه وضعیت‌ها</option>
                        <?php foreach ( $statuses as $key => $label ) :
                            $clean_key = str_replace( 'wc-', '', $key ); ?>
                            <option value="<?php echo esc_attr( $clean_key ); ?>" <?php selected( $status, $clean_key ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    از تاریخ سفارش
                    <input type="text" name="faktorak_date_from" class="fak-datepicker" inputmode="numeric" autocomplete="off" placeholder="مثلاً 2026-01-01" value="<?php echo esc_attr( $date_from ); ?>">
                </label>
                <label>
                    تا تاریخ سفارش
                    <input type="text" name="faktorak_date_to" class="fak-datepicker" inputmode="numeric" autocomplete="off" placeholder="مثلاً 2026-01-31" value="<?php echo esc_attr( $date_to ); ?>">
                </label>
                <div class="fak-filter-actions">
                    <button type="submit" class="button fak-black-btn">نمایش سفارش‌ها</button>
                    <a class="button fak-outline-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=orders_export' ) ); ?>">پاک‌کردن فیلتر</a>
                </div>
            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="faktorak-export-form">
                <?php wp_nonce_field( self::NONCE_ACTION, 'faktorak_export_nonce' ); ?>
                <input type="hidden" name="action" value="faktorak_export_orders">
                <input type="hidden" name="export_format" id="faktorak_export_format" value="csv">
                <input type="hidden" name="pdf_delivery" id="faktorak_pdf_delivery" value="continuous">
                <input type="hidden" name="limit" value="<?php echo esc_attr( $limit ); ?>">
                <input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
                <input type="hidden" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
                <input type="hidden" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">

                <div class="fak-fields-card">
                    <div class="fak-fields-head">
                        <div>
                            <strong>موارد داخل خروجی</strong>
                            <span>روی «همه موارد» همه ستون‌ها/اطلاعات ثبت می‌شود؛ روی «انتخابی» فقط گزینه‌های تیک‌خورده خروجی گرفته می‌شوند.</span>
                        </div>
                        <div class="fak-choice-group" role="radiogroup" aria-label="نوع ستون‌های خروجی">
                            <label><input type="radio" name="export_field_mode" value="all" checked> همه موارد</label>
                            <label><input type="radio" name="export_field_mode" value="custom"> انتخابی</label>
                        </div>
                    </div>
                    <div class="fak-field-grid" id="faktorak-field-grid" aria-disabled="true">
                        <?php foreach ( $columns as $key => $label ) : ?>
                            <label class="fak-field-chip">
                                <input type="checkbox" name="export_fields[]" value="<?php echo esc_attr( $key ); ?>" checked>
                                <span><?php echo esc_html( $label ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="fak-actions-card">
                    <div>
                        <strong>خروجی انتخابی/دسته‌جمعی</strong>
                        <span>اگر سفارشی انتخاب نشود، خروجی از آخرین سفارش‌های همین لیست گرفته می‌شود.</span>
                    </div>
                    <div class="fak-export-actions">
                        <button type="submit" class="button fak-black-btn" data-format="csv">دریافت CSV</button>
                        <button type="submit" class="button fak-black-btn" data-format="excel">دریافت Excel</button>
                        <button type="button" class="button fak-outline-btn" id="faktorak-open-pdf-modal" data-format="pdf">فاکتور PDF دسته‌جمعی</button>
                    </div>
                </div>

                <div class="fak-table-wrap">
                    <table class="widefat fixed striped fak-export-table">
                        <thead>
                            <tr>
                                <td class="check-column"><input type="checkbox" id="faktorak-select-all"></td>
                                <th>شماره سفارش</th>
                                <th>تاریخ</th>
                                <th>وضعیت</th>
                                <th>نام مشتری</th>
                                <th>شماره تماس</th>
                                <th>اقلام سفارش</th>
                                <th>مبلغ کل</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ( empty( $orders ) ) : ?>
                            <tr><td colspan="8">سفارشی برای نمایش پیدا نشد.</td></tr>
                        <?php else : ?>
                            <?php foreach ( $orders as $order ) :
                                $data = self::get_order_export_data( $order ); ?>
                                <tr>
                                    <th class="check-column"><input type="checkbox" name="order_ids[]" value="<?php echo esc_attr( $order->get_id() ); ?>"></th>
                                    <td><strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong></td>
                                    <td><?php echo esc_html( $data['date_created'] ); ?></td>
                                    <td><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
                                    <td><?php echo esc_html( $data['billing_full_name'] ); ?></td>
                                    <td><?php echo esc_html( $data['billing_phone'] ); ?></td>
                                    <td><?php echo esc_html( wp_trim_words( $data['items'], 16, '…' ) ); ?></td>
                                    <td><?php echo wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="fak-modal-backdrop" id="faktorak-pdf-modal" hidden>
                    <div class="fak-modal" role="dialog" aria-modal="true" aria-labelledby="faktorak-pdf-modal-title">
                        <button type="button" class="fak-modal-close" id="faktorak-pdf-modal-close" aria-label="بستن">×</button>
                        <h3 id="faktorak-pdf-modal-title">نوع خروجی فاکتور PDF</h3>
                        <p>خروجی PDF را به چه شکل می‌خواهید؟</p>
                        <div class="fak-modal-options">
                            <button type="button" class="fak-modal-option" data-pdf-delivery="continuous">
                                <strong>پشت سر هم</strong>
                                <span>همه فاکتورها داخل یک صفحه چاپی حرفه‌ای، مثل نسخه فعلی.</span>
                            </button>
                            <button type="button" class="fak-modal-option" data-pdf-delivery="zip">
                                <strong>زیپ فاکتورهای جداگانه</strong>
                                <span>برای هر سفارش یک فایل فاکتور جداگانه و آماده چاپ/PDF داخل ZIP ساخته می‌شود.</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <script>
            (function(){
                const form = document.getElementById('faktorak-export-form');
                const formatInput = document.getElementById('faktorak_export_format');
                const pdfDeliveryInput = document.getElementById('faktorak_pdf_delivery');
                const selectAll = document.getElementById('faktorak-select-all');
                const modal = document.getElementById('faktorak-pdf-modal');
                const openModal = document.getElementById('faktorak-open-pdf-modal');
                const closeModal = document.getElementById('faktorak-pdf-modal-close');
                const modeRadios = document.querySelectorAll('input[name="export_field_mode"]');
                const fieldGrid = document.getElementById('faktorak-field-grid');
                const fieldChecks = document.querySelectorAll('input[name="export_fields[]"]');

                function initFaktorakDatepicker(attempt){
                    attempt = attempt || 1;
                    if (!window.jQuery) {
                        if (attempt < 12) setTimeout(function(){ initFaktorakDatepicker(attempt + 1); }, 150);
                        return;
                    }
                    const $ = window.jQuery;
                    const $fields = $('.fak-datepicker');
                    if (!$fields.length) return;

                    if ($.fn && $.fn.datepicker) {
                        $fields.each(function(){
                            const $field = $(this);
                            if ($field.data('faktorak-datepicker-ready')) return;
                            $field.data('faktorak-datepicker-ready', true);
                            $field.datepicker({
                                dateFormat: 'yy-mm-dd',
                                changeMonth: true,
                                changeYear: true,
                                showButtonPanel: true,
                                closeText: 'بستن',
                                currentText: 'امروز',
                                monthNames: ['ژانویه','فوریه','مارس','آوریل','مه','ژوئن','ژوئیه','اوت','سپتامبر','اکتبر','نوامبر','دسامبر'],
                                monthNamesShort: ['ژان','فور','مار','آور','مه','ژوئن','ژوئیه','اوت','سپ','اکت','نوا','دسا'],
                                dayNames: ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه'],
                                dayNamesShort: ['یک','دو','سه','چهار','پنج','جمعه','شنبه'],
                                dayNamesMin: ['ی','د','س','چ','پ','ج','ش'],
                                isRTL: true,
                                beforeShow: function(input, inst){
                                    setTimeout(function(){
                                        if (inst && inst.dpDiv) inst.dpDiv.attr('dir', 'rtl');
                                    }, 0);
                                }
                            });
                        });
                    } else if (attempt < 12) {
                        setTimeout(function(){ initFaktorakDatepicker(attempt + 1); }, 150);
                    } else {
                        // fallback امن: اگر به هر دلیل jQuery UI در محیط ادمین لود نشد، تقویم Native مرورگر فعال می‌شود.
                        $fields.attr('type', 'date');
                    }
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function(){ initFaktorakDatepicker(1); });
                } else {
                    initFaktorakDatepicker(1);
                }

                function syncFieldMode(){
                    const custom = document.querySelector('input[name="export_field_mode"][value="custom"]')?.checked;
                    if (fieldGrid) fieldGrid.setAttribute('aria-disabled', custom ? 'false' : 'true');
                    fieldChecks.forEach(function(cb){ cb.disabled = !custom; });
                }
                modeRadios.forEach(function(r){ r.addEventListener('change', syncFieldMode); });
                syncFieldMode();

                if (selectAll) {
                    selectAll.addEventListener('change', function(){
                        document.querySelectorAll('input[name="order_ids[]"]').forEach(function(cb){ cb.checked = selectAll.checked; });
                    });
                }
                if (form) {
                    form.addEventListener('click', function(e){
                        const btn = e.target.closest('button[data-format]');
                        if (!btn) return;
                        formatInput.value = btn.getAttribute('data-format');
                    });
                }
                if (openModal && modal) {
                    openModal.addEventListener('click', function(){
                        formatInput.value = 'pdf';
                        modal.hidden = false;
                    });
                }
                if (closeModal && modal) {
                    closeModal.addEventListener('click', function(){ modal.hidden = true; });
                }
                if (modal) {
                    modal.addEventListener('click', function(e){
                        if (e.target === modal) { modal.hidden = true; return; }
                        const opt = e.target.closest('[data-pdf-delivery]');
                        if (!opt) return;
                        pdfDeliveryInput.value = opt.getAttribute('data-pdf-delivery');
                        modal.hidden = true;
                        form.submit();
                    });
                }
            })();
            </script>
        </div>
        <?php
    }

    public static function handle_export() {
        self::verify_request();
        $format = isset( $_POST['export_format'] ) ? sanitize_key( wp_unslash( $_POST['export_format'] ) ) : 'csv';
        $orders = self::get_orders_from_request();
        $fields = self::get_selected_fields_from_request();

        if ( 'pdf' === $format ) {
            $delivery = isset( $_POST['pdf_delivery'] ) ? sanitize_key( wp_unslash( $_POST['pdf_delivery'] ) ) : 'continuous';
            if ( 'zip' === $delivery ) {
                self::output_invoice_zip( $orders, $fields );
            }
            self::render_bulk_invoice_document( $orders, $fields );
            exit;
        }

        $rows = array_map( array( __CLASS__, 'get_order_export_data' ), $orders );
        if ( 'excel' === $format ) {
            self::output_excel( $rows, $fields );
        }
        self::output_csv( $rows, $fields );
    }

    public static function handle_bulk_invoice_pdf() {
        self::verify_request( 'get' );
        $ids = isset( $_GET['order_ids'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['order_ids'] ) ) ) ) ) : array();
        $limit  = isset( $_GET['limit'] ) ? max( 1, min( 500, absint( $_GET['limit'] ) ) ) : 100;
        $status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $date_from = isset( $_GET['date_from'] ) ? self::sanitize_date_param( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to   = isset( $_GET['date_to'] ) ? self::sanitize_date_param( wp_unslash( $_GET['date_to'] ) ) : '';
        $orders = self::query_orders( $ids, $limit, $status, $date_from, $date_to );
        self::render_bulk_invoice_document( $orders, self::get_default_fields() );
        exit;
    }

    public static function handle_bulk_invoice_zip() {
        self::verify_request( 'get' );
        $ids = isset( $_GET['order_ids'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['order_ids'] ) ) ) ) ) : array();
        $limit  = isset( $_GET['limit'] ) ? max( 1, min( 500, absint( $_GET['limit'] ) ) ) : 100;
        $status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $date_from = isset( $_GET['date_from'] ) ? self::sanitize_date_param( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to   = isset( $_GET['date_to'] ) ? self::sanitize_date_param( wp_unslash( $_GET['date_to'] ) ) : '';
        $orders = self::query_orders( $ids, $limit, $status, $date_from, $date_to );
        self::output_invoice_zip( $orders, self::get_default_fields() );
        exit;
    }

    private static function verify_request( $method = 'post' ) {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'شما دسترسی لازم را ندارید.', 'faktorak' ) );
        }
        $source = ( 'get' === $method ) ? $_GET : $_POST;
        $nonce  = isset( $source['faktorak_export_nonce'] ) ? sanitize_text_field( wp_unslash( $source['faktorak_export_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            wp_die( esc_html__( 'درخواست نامعتبر است. لطفاً صفحه را تازه‌سازی و دوباره تلاش کنید.', 'faktorak' ) );
        }
    }

    private static function get_orders_from_request() {
        $ids = isset( $_POST['order_ids'] ) ? array_map( 'absint', (array) $_POST['order_ids'] ) : array();
        $ids = array_filter( $ids );
        $limit  = isset( $_POST['limit'] ) ? max( 1, min( 500, absint( $_POST['limit'] ) ) ) : 100;
        $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
        $date_from = isset( $_POST['date_from'] ) ? self::sanitize_date_param( wp_unslash( $_POST['date_from'] ) ) : '';
        $date_to   = isset( $_POST['date_to'] ) ? self::sanitize_date_param( wp_unslash( $_POST['date_to'] ) ) : '';
        return self::query_orders( $ids, $limit, $status, $date_from, $date_to );
    }

    private static function get_orders_for_preview( $limit = 100, $status = '', $date_from = '', $date_to = '' ) {
        return self::query_orders( array(), $limit, $status, $date_from, $date_to );
    }

    private static function query_orders( $ids = array(), $limit = 100, $status = '', $date_from = '', $date_to = '' ) {
        if ( ! function_exists( 'wc_get_orders' ) || ! function_exists( 'wc_get_order' ) ) {
            return array();
        }

        // وقتی سفارش‌ها دستی انتخاب شده‌اند، باید دقیقاً همان سفارش‌ها خروجی شوند.
        // استفاده از wc_get_orders با include در بعضی حالت‌های HPOS/فیلترها می‌تواند ترتیب یا نتیجه را تغییر دهد.
        $ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
        if ( ! empty( $ids ) ) {
            $orders = array();
            foreach ( $ids as $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order && is_a( $order, 'WC_Order' ) && 'shop_order' === $order->get_type() ) {
                    $orders[] = $order;
                }
            }
            return $orders;
        }

        $args = array(
            'type'    => 'shop_order',
            'limit'   => $limit,
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'objects',
        );
        if ( ! empty( $status ) ) {
            $args['status'] = $status;
        }
        $date_range = self::build_wc_date_range( $date_from, $date_to );
        if ( $date_range ) {
            $args['date_created'] = $date_range;
        }
        return wc_get_orders( $args );
    }

    private static function sanitize_date_param( $date ) {
        $date = sanitize_text_field( $date );
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return '';
        }
        list( $year, $month, $day ) = array_map( 'absint', explode( '-', $date ) );
        return checkdate( $month, $day, $year ) ? sprintf( '%04d-%02d-%02d', $year, $month, $day ) : '';
    }

    private static function build_wc_date_range( $date_from = '', $date_to = '' ) {
        $date_from = self::sanitize_date_param( $date_from );
        $date_to   = self::sanitize_date_param( $date_to );
        if ( empty( $date_from ) && empty( $date_to ) ) {
            return '';
        }
        if ( $date_from && $date_to && strtotime( $date_from ) > strtotime( $date_to ) ) {
            $tmp = $date_from;
            $date_from = $date_to;
            $date_to = $tmp;
        }
        $from = $date_from ? $date_from . ' 00:00:00' : '1970-01-01 00:00:00';
        $to   = $date_to ? $date_to . ' 23:59:59' : gmdate( 'Y-m-d 23:59:59' );
        return $from . '...' . $to;
    }

    private static function get_clean_order_address( $order, $type = 'billing' ) {
        if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
            return '';
        }

        $getter_prefix = ( 'shipping' === $type ) ? 'get_shipping_' : 'get_billing_';
        $parts = array();
        foreach ( array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ) as $field ) {
            $method = $getter_prefix . $field;
            if ( is_callable( array( $order, $method ) ) ) {
                $value = trim( wp_strip_all_tags( (string) $order->{$method}() ) );
                if ( '' !== $value ) {
                    $parts[] = $value;
                }
            }
        }

        // اگر فیلدهای جزئی خالی بودند، از آدرس فرمت‌شده ووکامرس استفاده می‌کنیم، اما قبل از حذف HTML، جداکننده‌ها را حفظ می‌کنیم.
        if ( empty( $parts ) ) {
            $formatted = ( 'shipping' === $type ) ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address();
            $formatted = preg_replace( '/<br\s*\/?>(\s*)/i', '، ', (string) $formatted );
            $formatted = preg_replace( '/<\/p>|<\/div>|<\/li>/i', '، ', $formatted );
            return trim( preg_replace( '/\s*،\s*/u', '، ', wp_strip_all_tags( $formatted ) ), " \t\n\r\x0B،" );
        }

        return implode( '، ', array_unique( $parts ) );
    }

    private static function format_address_for_print( $address ) {
        $address = trim( (string) $address );
        if ( '' === $address ) {
            return '—';
        }
        $parts = preg_split( '/\s*،\s*/u', $address );
        $parts = array_filter( array_map( 'trim', (array) $parts ) );
        return esc_html( implode( "
", $parts ) );
    }

    public static function get_order_export_data( $order ) {
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( absint( $order ) );
        }
        if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
            return array();
        }
        $items = array();
        $qty_total = 0;
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            $sku = $product ? $product->get_sku() : '';
            $qty = (int) $item->get_quantity();
            $qty_total += $qty;
            $name = $item->get_name();
            $items[] = $name . ( $sku ? ' | SKU: ' . $sku : '' ) . ' × ' . $qty . ' = ' . wc_format_decimal( $item->get_total(), 0 );
        }
        $billing_address  = self::get_clean_order_address( $order, 'billing' );
        $shipping_address = self::get_clean_order_address( $order, 'shipping' );
        if ( empty( $shipping_address ) ) {
            $shipping_address = $billing_address;
        }
        return array(
            'order_id'           => $order->get_id(),
            'order_number'       => $order->get_order_number(),
            'date_created'       => $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'Y/m/d H:i' ) : '',
            'status'             => wc_get_order_status_name( $order->get_status() ),
            'billing_full_name'  => trim( $order->get_formatted_billing_full_name() ),
            'billing_phone'      => $order->get_billing_phone(),
            'billing_email'      => $order->get_billing_email(),
            'billing_address'    => $billing_address,
            'shipping_full_name' => trim( $order->get_formatted_shipping_full_name() ) ?: trim( $order->get_formatted_billing_full_name() ),
            'shipping_phone'     => $order->get_shipping_phone() ?: $order->get_billing_phone(),
            'shipping_address'   => $shipping_address,
            'payment_method'     => $order->get_payment_method_title(),
            'shipping_method'    => $order->get_shipping_method(),
            'items'              => implode( ' | ', $items ),
            'items_count'        => $qty_total,
            'subtotal'           => wc_format_decimal( $order->get_subtotal(), 0 ),
            'discount_total'     => wc_format_decimal( $order->get_discount_total(), 0 ),
            'shipping_total'     => wc_format_decimal( $order->get_shipping_total(), 0 ),
            'tax_total'          => wc_format_decimal( $order->get_total_tax(), 0 ),
            'total'              => wc_format_decimal( $order->get_total(), 0 ),
            'currency'           => $order->get_currency(),
            'customer_note'      => $order->get_customer_note(),
        );
    }

    private static function columns() {
        return array(
            'order_id'           => 'شناسه سفارش',
            'order_number'       => 'شماره سفارش',
            'date_created'       => 'تاریخ ثبت',
            'status'             => 'وضعیت سفارش',
            'billing_full_name'  => 'نام خریدار',
            'billing_phone'      => 'شماره خریدار',
            'billing_email'      => 'ایمیل خریدار',
            'billing_address'    => 'آدرس صورتحساب',
            'shipping_full_name' => 'نام گیرنده',
            'shipping_phone'     => 'شماره گیرنده',
            'shipping_address'   => 'آدرس ارسال',
            'payment_method'     => 'روش پرداخت',
            'shipping_method'    => 'روش ارسال',
            'items'              => 'اقلام سفارش / نوع سفارش',
            'items_count'        => 'تعداد کل اقلام',
            'subtotal'           => 'جمع جزء',
            'discount_total'     => 'تخفیف',
            'shipping_total'     => 'هزینه ارسال',
            'tax_total'          => 'مالیات',
            'total'              => 'مبلغ کل',
            'currency'           => 'واحد پول',
            'customer_note'      => 'یادداشت مشتری',
        );
    }

    private static function get_default_fields() {
        return array_keys( self::columns() );
    }

    private static function get_selected_fields_from_request() {
        $mode = isset( $_POST['export_field_mode'] ) ? sanitize_key( wp_unslash( $_POST['export_field_mode'] ) ) : 'all';
        if ( 'custom' !== $mode ) {
            return self::get_default_fields();
        }
        $allowed = self::get_default_fields();
        $fields = isset( $_POST['export_fields'] ) ? (array) $_POST['export_fields'] : array();
        $fields = array_values( array_intersect( $allowed, array_map( 'sanitize_key', wp_unslash( $fields ) ) ) );
        return ! empty( $fields ) ? $fields : self::get_default_fields();
    }

    private static function selected_columns( $fields ) {
        return array_intersect_key( self::columns(), array_flip( $fields ) );
    }

    private static function output_csv( $rows, $fields ) {
        nocache_headers();
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename=faktorak-orders-' . gmdate( 'Y-m-d-His' ) . '.csv' );
        echo "\xEF\xBB\xBF";
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, array_values( self::selected_columns( $fields ) ) );
        foreach ( $rows as $row ) {
            fputcsv( $out, self::row_values( $row, $fields ) );
        }
        fclose( $out );
        exit;
    }

    private static function output_excel( $rows, $fields ) {
        nocache_headers();
        header( 'Content-Type: application/vnd.ms-excel; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename=faktorak-orders-' . gmdate( 'Y-m-d-His' ) . '.xls' );
        echo "\xEF\xBB\xBF";
        echo '<html><head><meta charset="UTF-8"></head><body dir="rtl"><table border="1" style="border-collapse:collapse;font-family:tahoma;font-size:12px;direction:rtl;text-align:right">';
        echo '<thead><tr>';
        foreach ( self::selected_columns( $fields ) as $label ) {
            echo '<th style="background:#111;color:#fff;padding:8px">' . esc_html( $label ) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ( $rows as $row ) {
            echo '<tr>';
            foreach ( self::row_values( $row, $fields ) as $value ) {
                echo '<td style="padding:7px;mso-number-format:\@">' . esc_html( $value ) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></body></html>';
        exit;
    }

    private static function row_values( $row, $fields ) {
        $values = array();
        foreach ( $fields as $key ) {
            $values[] = isset( $row[ $key ] ) ? $row[ $key ] : '';
        }
        return $values;
    }

    private static function output_invoice_zip( $orders, $fields ) {
        if ( ! class_exists( 'ZipArchive' ) ) {
            wp_die( esc_html__( 'افزونه ZipArchive روی هاست فعال نیست. برای خروجی ZIP باید اکستنشن zip در PHP فعال باشد.', 'faktorak' ) );
        }
        $upload_dir = wp_upload_dir();
        $tmp_dir = trailingslashit( $upload_dir['basedir'] ) . 'faktorak-temp';
        if ( ! wp_mkdir_p( $tmp_dir ) ) {
            wp_die( esc_html__( 'امکان ساخت پوشه موقت برای خروجی وجود ندارد.', 'faktorak' ) );
        }
        $zip_path = trailingslashit( $tmp_dir ) . 'faktorak-invoices-' . gmdate( 'Y-m-d-His' ) . '.zip';
        $zip = new ZipArchive();
        if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
            wp_die( esc_html__( 'امکان ساخت فایل ZIP وجود ندارد.', 'faktorak' ) );
        }
        $zip->addFromString( 'README.txt', "Faktorak bulk invoice export\nEach invoice is saved as a separate printable HTML document. Open each file in a browser and choose Print / Save as PDF.\n" );
        foreach ( $orders as $order ) {
            if ( ! $order || ! is_a( $order, 'WC_Order' ) ) { continue; }
            $filename = 'invoice-' . sanitize_file_name( $order->get_order_number() ) . '.html';
            $zip->addFromString( $filename, self::get_single_invoice_html( $order, $fields, true ) );
        }
        $zip->close();

        nocache_headers();
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename=faktorak-invoices-' . gmdate( 'Y-m-d-His' ) . '.zip' );
        header( 'Content-Length: ' . filesize( $zip_path ) );
        readfile( $zip_path );
        @unlink( $zip_path );
        exit;
    }

    private static function render_bulk_invoice_document( $orders, $fields ) {
        ?>
        <!doctype html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <title>فاکتور گروهی سفارشات</title>
            <?php echo self::invoice_print_styles(); ?>
        </head>
        <body>
            <div class="toolbar">
                <strong>فاکتور گروهی سفارشات - تعداد: <?php echo esc_html( count( $orders ) ); ?></strong>
                <button class="btn" onclick="window.print()">چاپ / ذخیره PDF</button>
            </div>
            <?php foreach ( $orders as $order ) :
                if ( ! $order || ! is_a( $order, 'WC_Order' ) ) { continue; }
                echo self::render_single_invoice_section( $order, $fields );
            endforeach; ?>
            <script>window.addEventListener('load',function(){ setTimeout(function(){ window.print(); }, 500); });</script>
        </body>
        </html>
        <?php
    }

    private static function get_single_invoice_html( $order, $fields, $auto_print = false ) {
        $html  = '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>فاکتور #' . esc_html( $order->get_order_number() ) . '</title>';
        $html .= self::invoice_print_styles();
        $html .= '</head><body>';
        if ( ! $auto_print ) {
            $html .= '<div class="toolbar"><strong>فاکتور #' . esc_html( $order->get_order_number() ) . '</strong><button class="btn" onclick="window.print()">چاپ / ذخیره PDF</button></div>';
        }
        $html .= self::render_single_invoice_section( $order, $fields, true );
        if ( $auto_print ) {
            $html .= '<script>window.addEventListener("load",function(){ setTimeout(function(){ window.print(); }, 500); });</script>';
        }
        $html .= '</body></html>';
        return $html;
    }

    private static function render_single_invoice_section( $order, $fields, $single = false ) {
        $settings = class_exists( 'Faktorak_Shipping_Invoice_Settings' ) ? new Faktorak_Shipping_Invoice_Settings() : ( class_exists( 'ShippingInvoiceSettings' ) ? new ShippingInvoiceSettings() : null );
        $store_name = $settings ? ( $settings->get_setting( 'sender_name' ) ?: get_bloginfo( 'name' ) ) : get_bloginfo( 'name' );
        $store_logo = $settings ? $settings->get_setting( 'logo_url' ) : '';
        $store_address = $settings ? $settings->get_setting( 'sender_address' ) : '';
        $store_phone = $settings ? $settings->get_setting( 'sender_phone' ) : '';
        $data = self::get_order_export_data( $order );
        $show = function( $key ) use ( $fields ) { return in_array( $key, $fields, true ); };
        ob_start();
        ?>
        <section class="invoice<?php echo $single ? ' single-invoice' : ''; ?>">
            <div class="head">
                <div class="brand">
                    <?php if ( $store_logo ) : ?><img src="<?php echo esc_url( $store_logo ); ?>" alt="<?php echo esc_attr( $store_name ); ?>"><?php endif; ?>
                    <div>
                        <h1><?php echo esc_html( $store_name ); ?></h1>
                        <div class="muted store-meta"><?php echo nl2br( esc_html( trim( (string) $store_address ) ) ); ?><?php echo $store_phone ? '<br>تلفن: ' . esc_html( $store_phone ) : ''; ?></div>
                    </div>
                </div>
                <div class="invoice-meta">
                    <h2>فاکتور #<?php echo esc_html( $order->get_order_number() ); ?></h2>
                    <div class="muted"><?php if ( $show( 'date_created' ) ) : ?>تاریخ: <?php echo esc_html( $data['date_created'] ); ?><br><?php endif; ?><?php if ( $show( 'status' ) ) : ?>وضعیت: <?php echo esc_html( $data['status'] ); ?><?php endif; ?></div>
                </div>
            </div>

            <div class="info-table-wrap">
                <table class="info-table">
                    <tbody>
                    <?php if ( $show( 'billing_full_name' ) || $show( 'billing_phone' ) || $show( 'billing_email' ) || $show( 'billing_address' ) ) : ?>
                        <tr><th>اطلاعات خریدار</th><td><?php if ( $show( 'billing_full_name' ) ) : ?><strong><?php echo esc_html( $data['billing_full_name'] ); ?></strong><br><?php endif; ?><?php if ( $show( 'billing_phone' ) ) : ?>شماره: <?php echo esc_html( $data['billing_phone'] ?: '—' ); ?><br><?php endif; ?><?php if ( $show( 'billing_email' ) ) : ?>ایمیل: <?php echo esc_html( $data['billing_email'] ?: '—' ); ?><br><?php endif; ?><?php if ( $show( 'billing_address' ) ) : ?>آدرس صورتحساب: <?php echo nl2br( self::format_address_for_print( $data['billing_address'] ?: '—' ) ); ?><?php endif; ?></td></tr>
                    <?php endif; ?>
                    <?php if ( $show( 'shipping_full_name' ) || $show( 'shipping_phone' ) || $show( 'shipping_address' ) || $show( 'shipping_method' ) ) : ?>
                        <tr><th>اطلاعات ارسال</th><td><?php if ( $show( 'shipping_full_name' ) ) : ?><strong><?php echo esc_html( $data['shipping_full_name'] ); ?></strong><br><?php endif; ?><?php if ( $show( 'shipping_phone' ) ) : ?>شماره گیرنده: <?php echo esc_html( $data['shipping_phone'] ?: '—' ); ?><br><?php endif; ?><?php if ( $show( 'shipping_method' ) ) : ?>روش ارسال: <?php echo esc_html( $data['shipping_method'] ?: '—' ); ?><br><?php endif; ?><?php if ( $show( 'shipping_address' ) ) : ?>آدرس ارسال: <?php echo nl2br( self::format_address_for_print( $data['shipping_address'] ?: $data['billing_address'] ) ); ?><?php endif; ?></td></tr>
                    <?php endif; ?>
                    <?php if ( $show( 'payment_method' ) || $show( 'currency' ) || $show( 'items_count' ) ) : ?>
                        <tr><th>پرداخت و سفارش</th><td><?php if ( $show( 'payment_method' ) ) : ?>روش پرداخت: <?php echo esc_html( $data['payment_method'] ?: '—' ); ?><br><?php endif; ?><?php if ( $show( 'currency' ) ) : ?>واحد پول: <?php echo esc_html( $data['currency'] ); ?><br><?php endif; ?><?php if ( $show( 'items_count' ) ) : ?>تعداد کل اقلام: <?php echo esc_html( $data['items_count'] ); ?><?php endif; ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <table class="items-table">
                <thead><tr><th>ردیف</th><th class="right">محصول / نوع سفارش</th><th>تعداد</th><th>قیمت واحد</th><th>جمع</th></tr></thead>
                <tbody>
                <?php $i = 1; foreach ( $order->get_items() as $item ) : ?>
                    <tr>
                        <td><?php echo esc_html( $i++ ); ?></td>
                        <td class="right"><?php echo esc_html( $item->get_name() ); ?></td>
                        <td><?php echo esc_html( $item->get_quantity() ); ?></td>
                        <td><?php echo wp_kses_post( wc_price( $order->get_item_subtotal( $item, false, false ), array( 'currency' => $order->get_currency() ) ) ); ?></td>
                        <td><?php echo wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="totals">
                <?php if ( $show( 'subtotal' ) ) : ?><div class="row"><span>جمع جزء</span><span><?php echo wp_kses_post( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ); ?></span></div><?php endif; ?>
                <?php if ( $show( 'discount_total' ) ) : ?><div class="row"><span>تخفیف</span><span><?php echo wp_kses_post( wc_price( $order->get_discount_total(), array( 'currency' => $order->get_currency() ) ) ); ?></span></div><?php endif; ?>
                <?php if ( $show( 'shipping_total' ) ) : ?><div class="row"><span>ارسال</span><span><?php echo wp_kses_post( wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) ); ?></span></div><?php endif; ?>
                <?php if ( $show( 'tax_total' ) ) : ?><div class="row"><span>مالیات</span><span><?php echo wp_kses_post( wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ) ); ?></span></div><?php endif; ?>
                <div class="row final"><span>مبلغ نهایی</span><span><?php echo wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></span></div>
            </div>
            <?php if ( $show( 'customer_note' ) && $data['customer_note'] ) : ?><div class="note-box"><strong>یادداشت مشتری</strong><br><?php echo esc_html( $data['customer_note'] ); ?></div><?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    private static function invoice_print_styles() {
        $font_url = function_exists( 'faktorak_get_assets_url' ) ? faktorak_get_assets_url() . 'fonts/iranyekanwebregularfanum.woff' : '';
        $font_face = $font_url ? "@font-face{font-family:'iranyekan';src:url('" . esc_url( $font_url ) . "') format('woff');font-weight:400;font-style:normal;font-display:swap;}" : '';

        return '<style>
            ' . $font_face . '
            body{font-family:"iranyekan",tahoma,arial,sans-serif;background:#f5f6f7;color:#111827;margin:0;padding:22px;direction:rtl;font-size:12px;line-height:1.8}
            body *{font-family:"iranyekan",tahoma,arial,sans-serif}
            .toolbar{position:sticky;top:0;z-index:5;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:11px 13px;margin:0 auto 16px;max-width:880px;display:flex;justify-content:space-between;gap:12px;align-items:center;box-shadow:0 8px 22px rgba(0,0,0,.055)}
            .btn{background:#111827;color:#fff;border:none;border-radius:9px;padding:9px 15px;cursor:pointer;text-decoration:none;font-size:12px}
            .invoice{background:#fff;max-width:880px;margin:0 auto 16px;border:1px solid #e5e7eb;border-radius:16px;padding:18px;page-break-after:always;box-sizing:border-box;box-shadow:0 10px 30px rgba(17,24,39,.05)}
            .single-invoice{page-break-after:auto}.head{display:flex;justify-content:space-between;gap:16px;border-bottom:1px solid #eceff3;padding-bottom:12px;margin-bottom:12px;align-items:center}.brand{display:flex;gap:10px;align-items:center}.brand img{width:50px;height:50px;object-fit:contain;border:1px solid #eef0f3;border-radius:10px;padding:4px}.brand h1{margin:0;font-size:16px;line-height:1.5;color:#111827;font-weight:700}.invoice-meta{text-align:left}.invoice-meta h2{margin:0 0 4px;font-size:13px;font-weight:700;color:#111827}.muted{color:#6b7280;font-size:10.5px;line-height:1.8}.store-meta{max-width:420px}
            table{width:100%;border-collapse:separate;border-spacing:0;font-size:11.5px}.info-table-wrap{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:12px}.info-table th{width:130px;background:#fafafa;color:#374151;border-bottom:1px solid #eceff3;padding:8px 10px;text-align:right;vertical-align:top;font-weight:700}.info-table td{border-bottom:1px solid #eceff3;padding:8px 10px;text-align:right;vertical-align:top;color:#111827}.info-table tr:last-child th,.info-table tr:last-child td{border-bottom:0}
            .items-table{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;border-collapse:separate;border-spacing:0}.items-table th,.items-table td{border-left:1px solid #eceff3;border-bottom:1px solid #eceff3;padding:8px;text-align:center;vertical-align:top}.items-table th:last-child,.items-table td:last-child{border-left:0}.items-table tr:last-child td{border-bottom:0}.items-table th{background:#fafafa;color:#374151;font-weight:700}.right{text-align:right!important}.totals{width:300px;margin-right:auto;margin-top:12px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}.totals .row{display:flex;justify-content:space-between;gap:16px;border-bottom:1px solid #eceff3;padding:7px 10px;background:#fff}.totals .row:last-child{border-bottom:0}.totals .final{background:#111827;color:#fff;font-weight:700}.note-box{margin-top:12px;border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:#fafafa}
            @media print{body{background:#fff;padding:0}.toolbar{display:none}.invoice{border:0;border-radius:0;margin:0 auto;padding:7mm;max-width:none;box-shadow:none}@page{size:A4;margin:8mm}.head{padding-bottom:8px;margin-bottom:8px}.brand h1{font-size:15px}.invoice-meta h2{font-size:12px}.items-table th,.items-table td,.info-table th,.info-table td{padding:6px}.totals .row{padding:6px 9px}}
        </style>';
    }
}

=== فاکتورک (Faktorak) ===
Contributors: sajjadshadloo
Tags: invoice, shipping-label, woocommerce, proforma, checkout-map
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Faktorak adds printable WooCommerce invoices and shipping labels, optional proforma invoices, optional checkout map location capture, and a manual invoice workflow inside wp-admin.

== Features ==
* Print-friendly invoice and shipping label (A4).
* Two invoice templates (modern and classic).
* Optional proforma invoices (custom order status + admin action to convert to payable order).
* Optional checkout map to capture delivery location (saved in order meta).
* Manual invoice builder in wp-admin (create official invoice or proforma from selected products).
* Manual invoices list page (`لیست فاکتورها`) with search/filter/delete and quick customer/payment link copy.
* AJAX product and customer search with customer autofill in manual invoice form.
* Order list quick actions column (invoice + shipping label) in both legacy and HPOS order screens.
* Shortcodes:
  * `[faktorak_invoice_button]`
  * `[faktorak_proforma_button]`
* Local QR code rendering (no external QR service).
* Token-based protection for frontend invoice/proforma links.
* Payment button inside invoice view for unpaid manual invoices/proformas.
* Automatic conversion of paid proforma document type to official invoice.
* Optional seller signature image on invoice templates.
* Customer note is shown in both invoice templates (classic and modern).
* Unified IRANYekan typography across plugin admin pages and submenu UI.

== Installation ==
1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate the plugin.
3. Go to the Faktorak menu in wp-admin to configure invoice settings.

== Usage ==
* Admin: Open an order and use the metabox to print the invoice or shipping label.
* Admin: Use `Faktorak > فاکتور دستی` to create manual invoices/proformas and manage them from `لیست فاکتورها`.
* Customer: Use the shortcode button (or enable customer buttons in settings) to view/print invoices.
* Proforma: Enable the proforma option to allow creating a proforma invoice from the cart.
* Manual proforma payment: send the generated payment link to customer or use the payment button directly in invoice view.
* Checkout map: Enable the map option to let customers select their delivery location on the checkout page.

== External services ==
This plugin includes an optional “Checkout Map” feature. When it is enabled, the customer’s browser connects to the following third‑party services in order to display the map and provide address search:

1) OpenStreetMap Tile Servers (https://tile.openstreetmap.org/)

* Used for: Loading map tiles on the checkout page.
* Data sent: The customer’s IP address and the requested tile coordinates (as part of normal HTTP requests).
* Terms/Policies: https://www.openstreetmap.org/terms, https://wiki.osmfoundation.org/wiki/Privacy_Policy, https://operations.osmfoundation.org/policies/tiles/

2) Nominatim (OpenStreetMap) (https://nominatim.openstreetmap.org/)

* Used for: Address/city search in the map geocoder.
* Data sent: The customer’s search query text and the customer’s IP address (as part of normal HTTP requests).
* Terms/Policies: https://operations.osmfoundation.org/policies/nominatim/, https://wiki.osmfoundation.org/wiki/Privacy_Policy

The selected latitude/longitude is saved to the WooCommerce order meta (`_delivery_location`) only after the customer interacts with the map. In the admin order screen, a Google Maps link (https://www.google.com/maps) may be shown for convenience; it is only opened when an admin clicks the link.

== Changelog ==
= 1.5 =
* اضافه شدن خروجی سفارشات ووکامرس.
* اضافه شدن خروجی CSV و Excel از سفارش‌ها.
* اضافه شدن خروجی PDF دسته‌جمعی سفارشات.
* اضافه شدن خروجی ZIP شامل فاکتور جداگانه برای هر سفارش.
* امکان انتخاب دستی سفارش‌ها برای خروجی.
* فیلتر سفارشات بر اساس وضعیت، تعداد و بازه تاریخ.
* امکان انتخاب اطلاعات دلخواه داخل خروجی.
* سازگاری با HPOS ووکامرس.
* یکپارچه‌سازی ظاهر پیشخوان فاکتورک.
* بهبود UI صفحه تنظیمات، فاکتور دستی، لیست فاکتورها و خروجی سفارشات.
* بهبود دکمه‌ها، فیلترها، جدول‌ها و چیدمان صفحات مدیریت.
* سبک‌تر شدن نوتیس‌های داخلی افزونه.
* اصلاح ایرادات ظاهری گزارش‌شده.
* بهبود تجربه کاربری در پنل مدیریت.
* بهینه‌سازی نمایش فایل‌های CSS و جلوگیری از کش شدن تغییرات.

= 1.4 =
* Added manual invoice workflow in wp-admin (create/list/delete; official invoice or proforma).
* Added AJAX product/customer search and customer autofill for manual invoice creation.
* Added order list quick actions column for invoice/shipping label on both legacy and HPOS order screens.
* Added token validation for frontend invoice/proforma links to harden document access.
* Added payment links for manual proformas plus payment button in invoice view.
* Added automatic document-type conversion from proforma to official after successful payment.
* Added customer-note rendering in both classic and modern invoice templates.
* Improved IRANYekan coverage across plugin admin pages and submenu typography.
* Renamed the manual list label to `لیست فاکتورها`.
* Added optional signature support improvements in invoice templates.
* Added compatibility improvements for print rendering and HPOS environments.

= 1.3.1 =
* Improvements and security hardening.

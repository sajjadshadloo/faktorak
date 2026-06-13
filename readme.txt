<<<<<<< HEAD
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
=======
=== فاکتورک (Factork) ===
Contributors: sajjadshadloo
Tags: invoice, shipping label, woocommerce, invoice printing, label printing, iran, persian, faktur
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== توضیحات ==
افزونه **فاکتورک (Factork)** یک ابزار پیشرفته و بهینه برای مدیریت **فاکتور** و **برچسب پستی** در فروشگاه‌های ووکامرسی است.  
در این نسخه، علاوه‌بر ارتقای امنیت لینک‌ها با **Nonce**، دو **قالب فاکتور** (مدرن/کلاسیک) اضافه شده و چاپ A4 کاملاً تمیز و هم‌تراز شده است. رابط تنظیمات هم بازطراحی و امکانات حرفه‌ای‌تری مثل **پیش‌فاکتور**، **نقشه در تسویه‌حساب** و **QR** فراهم شده است.

== ویژگی‌ها ==
* 🧾 **تولید فاکتور و برچسب پستی بهینه برای چاپ** (A4-ready، جلوگیری از شکستن ردیف‌ها، حاشیه استاندارد)  
* 🎭 **دو قالب فاکتور**:  
  - **مدرن** (UI جدید، فونت ایران‌یکان، جمع‌وجور و مینیمال)  
  - **کلاسیک** (ظاهر سنتی‌تر، هدر تکمیل‌شده با باکس لوگوی ریسپانسیو)  
  از تنظیمات می‌توانید بینشان سوییچ کنید.  
* 🧭 **نقشه تسویه‌حساب (Checkout Map)** با OpenStreetMap + Geocoder + Locate؛ ذخیره لوکیشن در سفارش و نمایش QR موقعیت در برچسب پستی  
* 🔐 **امن‌سازی لینک‌های فاکتور/پیش‌فاکتور با Nonce** + اعتبارسنجی سمت سرور  
* 🖊️ **امضا/مهر دیجیتال** (آپلود تصویر و نمایش در انتهای فاکتور)  
* 🧩 **کلاس دکمه اختصاصی** `faktorak-btn` برای جلوگیری از تداخل با استایل‌های قالب/ووکامرس  
* 🖨️ **چاپ ریسپانسیو**؛ ستون‌ها کنار هم، فاصله‌ها منظم، اشیای غیرضروری در Print مخفی  
* 🧠 **سازگاری کامل با HPOS** (High Performance Order Storage)  
* ⚙️ **تنظیمات انعطاف‌پذیر فروشگاه**: لوگو، نام، آدرس، کدپستی، تلفن، ایمیل، وب‌سایت (دریافت داینامیک یا دستی)  
* 🏷️ **برچسب پستی بهینه** با قاب لوگوی ریسپانسیو (هر نسبت تصویری بدون کشیدگی) و QR لوکیشن  
* 🧾 **پیش‌فاکتور واقعی**:  
  - وضعیت سفارش سفارشی `wc-proforma-invoice`  
  - دکمه «تبدیل به سفارش قابل پرداخت» در ادمین (ارسال ایمیل پرداخت)  
  - امکان ساخت پیش‌فاکتور از سبد خرید  
* 🔗 **دکمه‌های جلوی کاربر** (در صفحه سفارش کاربر) با امکان فعال/غیرفعال‌سازی از تنظیمات  
* 🔎 **شورت‌کدهای آماده** + «کپی با یک کلیک» داخل صفحه تنظیمات:  
  - `[faktorak_invoice_button]` → دکمه دیدن فاکتور سفارش (با Nonce)  
  - `[faktorak_proforma_button]` → دکمه صدور پیش‌فاکتور  
* 🧩 **QR پیگیری سفارش** در فاکتور (لینک مستقیم به view-order)  
* 🧰 **متاباکس ادمین شکیل** با گوشه‌های گرد و فاصله‌های استاندارد (بدون تداخل با هسته)  
* 🌐 سازگار با وردپرس 6.6 و ووکامرس 9.0  

== جدید در نسخه 1.3.1 ==
* ✅ **دو قالب فاکتور (مدرن/کلاسیک)** + گزینه انتخاب در تنظیمات  
* ✅ **چاپ A4 بهینه**: ردیف‌های جدول نمی‌شکنند، کارت‌ها کنار هم می‌مانند، هدر مرتب  
* ✅ **فونت فارسی ایران‌یکان** فقط روی خروجی‌های افزونه (بدون دست‌کاری پیشخوان/سایت)  
* ✅ **برچسب پستی با جای‌گذاری هوشمند لوگو** (باکس ریسپانسیو برای مربع/دایره/مستطیل)  
* ✅ **QR پیگیری سفارش** در فاکتور + **QR لوکیشن ارسال** در برچسب پستی  
* ✅ **Nonce** برای همه لینک‌های فاکتور/پیش‌فاکتور + بررسی توکن در `template_redirect`  
* ✅ **وضعیت سفارش Proforma** + اکشن «تبدیل به سفارش قابل پرداخت» در ادمین و ارسال ایمیل پرداخت  
* ✅ **شورت‌کدهای قابل کپی** داخل تنظیمات؛ دکمه‌های یکتا با کلاس `faktorak-btn`  
* ✅ **استایل متاباکس ادمین** (border-radius، padding و …)  
* ✅ **اعلام سازگاری HPOS** فقط یک‌بار در فایل اصلی + Flush rewrite صرفاً هنگام فعال/غیرفعال‌سازی  

== نصب ==
1. پوشه افزونه را در `wp-content/plugins/` آپلود کنید یا از پیشخوان نصب کنید.  
2. افزونه را فعال کنید.  
3. از منوی «**فاکتورک**» تنظیمات فروشگاه، قالب فاکتور، امضا و … را ست کنید.  
4. در صفحه سفارش‌ها، متاباکس «فاکتورک» برای چاپ فاکتور/برچسب پستی در دسترس است.  
5. برای نمایش دکمه فاکتور در حساب کاربری، گزینه مربوطه را در تنظیمات فعال کنید.

== استفاده ==
* **ادمین**: از متاباکس سفارش، فاکتور یا برچسب پستی را باز و چاپ کنید.  
* **کاربر**: دکمه «مشاهده فاکتور» در صفحه جزئیات سفارش قابل دسترسی است.  
* **پیش‌فاکتور**: با دکمه سبد خرید یا شورت‌کد می‌توان پیش‌فاکتور ساخت.  
* **نقشه**: با فعال‌سازی گزینه مربوطه، لوکیشن کاربر در Checkout ثبت می‌شود و در ادمین با QR قابل مشاهده است.  
* **شورت‌کدها:**  
  - `[faktorak_invoice_button]`  
  - `[faktorak_proforma_button]`

== سوالات متداول ==
= چطور بین «مدرن» و «کلاسیک» جابه‌جا شوم؟ =  
به **تنظیمات فاکتورک → قالب فاکتور** بروید و یکی را انتخاب کنید.

= متن بالای فاکتور «فاکتور فروش / پیش‌فاکتور» چطور تنظیم می‌شود؟ =  
هنگام ساخت پیش‌فاکتور، افزونه به‌صورت خودکار **badge** را «پیش‌فاکتور» و در حالت سفارش عادی «فاکتور فروش» نمایش می‌دهد.

= امنیت لینک‌های فاکتور چطور تأمین می‌شود؟ =  
برای هر لینک یک **Nonce** تولید و در صفحه فاکتور اعتبارسنجی می‌شود؛ بدون توکن معتبر، دسترسی مسدود خواهد شد.

= آیا برچسب پستی باید لوگو داشته باشد؟ =  
اختیاری است. اگر لوگو بگذارید، داخل **باکس ریسپانسیو** بدون کشیدگی نمایش داده می‌شود.

= آیا می‌توان سفارش پیش‌فاکتور را به پرداختی تبدیل کرد؟ =  
بله. در ادمین از اکشن «**تبدیل به سفارش قابل پرداخت**» استفاده کنید؛ ایمیل پرداخت برای مشتری ارسال می‌شود.

= فاکتورها برای چاپ بهینه شده‌اند؟ =  
کاملاً. ردیف‌ها و کارت‌ها در چاپ نمی‌شکنند و اسکریپت/دکمه‌های اضافی مخفی می‌شوند.

== تغییرات (Changelog) ==
= 1.3.1 =
* افزودن **دو قالب فاکتور (مدرن/کلاسیک)** و سوییچر انتخاب قالب  
* بهینه‌سازی چاپ A4 و جلوگیری از شکستن خطوط جدول/کارت‌ها  
* فونت **ایران‌یکان** فقط در خروجی‌های افزونه  
* برچسب پستی با **باکس لوگوی ریسپانسیو** و **QR لوکیشن**  
* **Nonce** برای لینک‌های فاکتور/پیش‌فاکتور + اعتبارسنجی  
* وضعیت `wc-proforma-invoice` + اکشن تبدیل به سفارش قابل پرداخت  
* شورت‌کدهای آماده با دکمه کپی در تنظیمات  
* استایل‌دهی متاباکس ادمین (گوشه گرد/فاصله‌ها)  
* اعلام سازگاری HPOS + بهبود flush rewrite فقط هنگام فعال/غیرفعال  

= 1.3 =
* رفع باگ نمایش دکمه فاکتور در حساب کاربری  
* رفع مسیر «بازگشت به سفارش» و افزودن `context`  
* بهینه‌سازی منطق بازگشت و ساده‌سازی کد  

= 1.2 =
* محدود کردن فونت به بخش‌های افزونه  
* افزودن امضا/مهر  
* سازگاری با ووکامرس 9.0 و HPOS  
* بهبود متدهای دریافت اطلاعات سفارش  
* تست با وردپرس 6.6  

= 1.1 =
* بهبود چاپ فاکتور (ریسپانسیو و بدون حاشیه)  
* بازنگری ساختار قالب به Flex  
* حذف «شماره اقتصادی»  
* نمایش پویا ستون مالیات  
* آیکن منو `dashicons-media-document`  

= 1.0 =
* انتشار اولیه (فاکتور + برچسب پستی)

== پشتیبانی ==
برای گزارش مشکل یا پیشنهاد:  
📩 https://sajjadshadloo.ir/product/faktorak-plugin/

== مجوز ==
این افزونه تحت مجوز GPLv2 یا بالاتر منتشر شده است.  
کلیه حقوق توسعه برای **سجاد شادلو** محفوظ است.
>>>>>>> 1bb510fb4a53ee2d86c429d2c046eeeee2945d67

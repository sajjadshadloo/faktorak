/* global faktorakManualInvoice */
jQuery(function ($) {
  if (typeof faktorakManualInvoice === "undefined") {
    return;
  }

  const minChars = parseInt(faktorakManualInvoice.searchMinChars || 2, 10);
  const $itemsBody = $("#faktorak-manual-items");
  const $addItemBtn = $("#faktorak-add-item");
  const $customerSearch = $("#faktorak-customer-search");
  const $customerResults = $("#faktorak-customer-results");
  const $customerUserId = $("#faktorak-customer-user-id");

  function esc(value) {
    return $("<div>").text(value || "").html();
  }

  function decodeHtml(value) {
    return $("<textarea>").html(value || "").text();
  }

  function normalizePrice(value) {
    const decoded = decodeHtml(value || "");
    const plain = $("<div>").html(decoded).text();
    return $.trim(
      String(plain)
        .replace(/\u00a0/g, " ")
        .replace(/&nbsp;/gi, " ")
        .replace(/\s+/g, " ")
    );
  }

  function debounce(fn, delay) {
    let timer = null;
    return function () {
      const args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(null, args);
      }, delay);
    };
  }

  function hideResult($container) {
    $container.empty().hide();
  }

  function findRow($el) {
    return $el.closest(".faktorak-item-row");
  }

  function clearRowSelection($row) {
    $row.find(".faktorak-product-id").val("");
    $row.find(".faktorak-product-name").val("");
    $row.find(".faktorak-product-sku").val("");
    $row.find(".faktorak-product-price").val("");
  }

  function renderProductResults($row, items) {
    const $results = $row.find(".faktorak-search-results");
    if (!items || !items.length) {
      hideResult($results);
      return;
    }

    const html = items
      .map(function (item) {
        const sku = item.sku || "";
        const price = normalizePrice(item.price_html || "");
        const priceForView = price || "نامشخص";
        return (
          '<div class="faktorak-search-item" data-kind="product" data-id="' +
          esc(String(item.id)) +
          '" data-name="' +
          esc(item.name) +
          '" data-sku="' +
          esc(sku) +
          '" data-price="' +
          esc(price || "-") +
          '">' +
          '<div class="name">' +
          esc(item.name) +
          "</div>" +
          '<div class="meta">شناسه: ' +
          esc(sku || "ندارد") +
          " | قیمت: " +
          esc(priceForView) +
          "</div>" +
          "</div>"
        );
      })
      .join("");
    $results.html(html).show();
  }

  const requestProduct = debounce(function ($row, term) {
    $.ajax({
      url: faktorakManualInvoice.ajaxUrl,
      type: "POST",
      dataType: "json",
      data: {
        action: "faktorak_search_products",
        nonce: faktorakManualInvoice.productNonce,
        q: term,
      },
    })
      .done(function (response) {
        if (response && response.success && $.isArray(response.data)) {
          renderProductResults($row, response.data);
        } else {
          hideResult($row.find(".faktorak-search-results"));
        }
      })
      .fail(function () {
        hideResult($row.find(".faktorak-search-results"));
      });
  }, 220);

  $itemsBody.on("input", ".faktorak-product-search", function () {
    const $input = $(this);
    const $row = findRow($input);
    const term = $.trim($input.val());
    clearRowSelection($row);

    if (term.length < minChars) {
      hideResult($row.find(".faktorak-search-results"));
      return;
    }
    requestProduct($row, term);
  });

  $itemsBody.on("click", '.faktorak-search-item[data-kind="product"]', function () {
    const $item = $(this);
    const $row = findRow($item);
    const id = $item.attr("data-id") || "";
    const name = $item.attr("data-name") || "";
    const sku = $item.attr("data-sku") || "";
    const price = $item.attr("data-price") || "";

    $row.find(".faktorak-product-id").val(id);
    $row.find(".faktorak-product-search").val(name);
    $row.find(".faktorak-product-name").val(name);
    $row.find(".faktorak-product-sku").val(sku === "-" ? "" : sku);
    $row.find(".faktorak-product-price").val(price === "-" ? "" : price);
    hideResult($row.find(".faktorak-search-results"));
  });

  function addItemRow() {
    const $first = $itemsBody.find(".faktorak-item-row").first();
    const $clone = $first.clone();
    $clone.find("input").val("");
    $clone.find('input[name="quantity[]"]').val("1");
    hideResult($clone.find(".faktorak-search-results"));
    $itemsBody.append($clone);
  }

  $addItemBtn.on("click", function (e) {
    e.preventDefault();
    addItemRow();
  });

  $itemsBody.on("click", ".faktorak-remove-item", function (e) {
    e.preventDefault();
    const $rows = $itemsBody.find(".faktorak-item-row");
    if ($rows.length <= 1) {
      clearRowSelection($rows.first());
      $rows.first().find(".faktorak-product-search").val("");
      $rows.first().find('input[name="quantity[]"]').val("1");
      hideResult($rows.first().find(".faktorak-search-results"));
      return;
    }
    $(this).closest(".faktorak-item-row").remove();
  });

  $itemsBody.on("click", ".faktorak-qty-plus, .faktorak-qty-minus", function (e) {
    e.preventDefault();
    const $btn = $(this);
    const $input = $btn.closest(".faktorak-qty-control").find('input[name="quantity[]"]');
    let qty = parseInt($input.val(), 10);
    if (isNaN(qty) || qty < 1) qty = 1;
    qty = $btn.hasClass("faktorak-qty-plus") ? qty + 1 : qty - 1;
    if (qty < 1) qty = 1;
    $input.val(String(qty));
  });

  $itemsBody.on("input change", 'input[name="quantity[]"]', function () {
    const $input = $(this);
    let qty = parseInt($input.val(), 10);
    if (isNaN(qty) || qty < 1) qty = 1;
    $input.val(String(qty));
  });

  function renderCustomerResults(items) {
    if (!items || !items.length) {
      hideResult($customerResults);
      return;
    }
    const html = items
      .map(function (item) {
        return (
          '<div class="faktorak-search-item" data-kind="customer" data-id="' +
          esc(String(item.id)) +
          '" data-first-name="' +
          esc(item.first_name) +
          '" data-last-name="' +
          esc(item.last_name) +
          '" data-email="' +
          esc(item.email) +
          '" data-phone="' +
          esc(item.phone) +
          '" data-state="' +
          esc(item.state) +
          '" data-city="' +
          esc(item.city) +
          '" data-postcode="' +
          esc(item.postcode) +
          '" data-address="' +
          esc(item.address_1) +
          '" data-name="' +
          esc(item.name) +
          '">' +
          '<div class="name">' +
          esc(item.name || "کاربر") +
          "</div>" +
          '<div class="meta">' +
          esc(item.email || "-") +
          " | " +
          esc(item.phone || "-") +
          "</div>" +
          "</div>"
        );
      })
      .join("");
    $customerResults.html(html).show();
  }

  const requestCustomer = debounce(function (term) {
    $.ajax({
      url: faktorakManualInvoice.ajaxUrl,
      type: "POST",
      dataType: "json",
      data: {
        action: "faktorak_search_customers",
        nonce: faktorakManualInvoice.customerNonce,
        q: term,
      },
    })
      .done(function (response) {
        if (response && response.success && $.isArray(response.data)) {
          renderCustomerResults(response.data);
        } else {
          hideResult($customerResults);
        }
      })
      .fail(function () {
        hideResult($customerResults);
      });
  }, 220);

  $customerSearch.on("input", function () {
    const term = $.trim($customerSearch.val());
    $customerUserId.val("");
    if (term.length < minChars) {
      hideResult($customerResults);
      return;
    }
    requestCustomer(term);
  });

  $(document).on("click", '.faktorak-search-item[data-kind="customer"]', function () {
    const $item = $(this);
    const data = {
      id: $item.attr("data-id") || "",
      name: $item.attr("data-name") || "",
      firstName: $item.attr("data-first-name") || "",
      lastName: $item.attr("data-last-name") || "",
      email: $item.attr("data-email") || "",
      phone: $item.attr("data-phone") || "",
      state: $item.attr("data-state") || "",
      city: $item.attr("data-city") || "",
      postcode: $item.attr("data-postcode") || "",
      address: $item.attr("data-address") || "",
    };
    $customerUserId.val(data.id);
    $customerSearch.val(data.name);

    $("#fak-billing-first-name").val(data.firstName);
    $("#fak-billing-last-name").val(data.lastName);
    $("#fak-billing-email").val(data.email);
    $("#fak-billing-phone").val(data.phone);
    $("#fak-billing-state").val(data.state);
    $("#fak-billing-city").val(data.city);
    $("#fak-billing-postcode").val(data.postcode);
    $("#fak-billing-address").val(data.address);

    hideResult($customerResults);
  });

  $(document).on("click", function (e) {
    if (!$(e.target).closest(".faktorak-search-results, .faktorak-product-search, #faktorak-customer-search").length) {
      $(".faktorak-search-results").empty().hide();
    }
  });
});

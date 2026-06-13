/* global wp */
jQuery(function ($) {
  function bindMediaUploader(buttonSelector, inputSelector, title) {
    const $button = $(buttonSelector);
    const $input = $(inputSelector);

    if (!$button.length || !$input.length || typeof wp === 'undefined' || !wp.media) {
      return;
    }

    let frame;
    $button.on('click', function (e) {
      e.preventDefault();

      if (frame) {
        frame.open();
        return;
      }

      frame = wp.media({
        title: title,
        button: { text: 'انتخاب' },
        multiple: false,
      });

      frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON();
        if (attachment && attachment.url) {
          $input.val(attachment.url).trigger('change');
        }
      });

      frame.open();
    });
  }

  bindMediaUploader('#upload_logo_button', '#logo_url', 'انتخاب لوگو');
  bindMediaUploader('#upload_signature_button', '#signature_url', 'انتخاب امضا');

  function toggleManualListMobileLayout() {
    const hasListTable = $('.faktorak-manual-wrap .faktorak-list-table').length > 0;
    if (!hasListTable) return;

    const isNarrow = window.innerWidth <= 980;
    const isTouch = window.matchMedia && window.matchMedia('(hover: none) and (pointer: coarse)').matches;
    $('body').toggleClass('faktorak-force-mobile-list', Boolean(isNarrow || isTouch));
  }

  let resizeTimer = null;
  $(window).on('resize orientationchange', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(toggleManualListMobileLayout, 120);
  });
  toggleManualListMobileLayout();

  const $bulkForm = $('.faktorak-bulk-print-form');
  if ($bulkForm.length) {
    function updateBulkSelectedCount() {
      const selected = $bulkForm.find('.faktorak-bulk-item:checked').length;
      $bulkForm.find('.faktorak-bulk-selected-count').text(selected + ' انتخاب شده');
    }

    $bulkForm.on('change', '.faktorak-bulk-check-all', function () {
      const checked = $(this).is(':checked');
      $bulkForm.find('.faktorak-bulk-item').prop('checked', checked);
      updateBulkSelectedCount();
    });

    $bulkForm.on('change', '.faktorak-bulk-item', function () {
      const total = $bulkForm.find('.faktorak-bulk-item').length;
      const selected = $bulkForm.find('.faktorak-bulk-item:checked').length;
      $bulkForm.find('.faktorak-bulk-check-all').prop('checked', total > 0 && total === selected);
      updateBulkSelectedCount();
    });

    $bulkForm.on('submit', function (e) {
      const selected = $bulkForm.find('.faktorak-bulk-item:checked').length;
      if (!selected) {
        e.preventDefault();
        window.alert('حداقل یک فاکتور را انتخاب کنید.');
      }
    });

    updateBulkSelectedCount();
  }

  $(document).on('click', '.fak-copy-btn', function (e) {
    e.preventDefault();

    const $button = $(this);
    const selector = $button.attr('data-target');
    const $input = selector ? $(selector) : $();
    if (!$input.length) return;

    const originalText = $button.text();
    $input.trigger('focus').trigger('select');
    $input[0].setSelectionRange(0, 99999);

    let ok = false;
    try {
      ok = document.execCommand('copy');
    } catch (err) {
      ok = false;
    }

    $button.text(ok ? 'کپی شد!' : 'کپی نشد');
    setTimeout(function () {
      $button.text(originalText);
    }, 1200);
  });

  function closeLinksModal($modal) {
    if (!$modal.length) return;
    $modal.attr('hidden', true).removeClass('is-open');
    if (!$('.faktorak-links-modal.is-open').length) {
      $('body').removeClass('faktorak-modal-open');
    }
  }

  $(document).on('click', '.faktorak-open-links-modal', function (e) {
    e.preventDefault();
    const selector = $(this).attr('data-modal-target');
    const $modal = selector ? $(selector) : $();
    if (!$modal.length) return;
    $modal.removeAttr('hidden').addClass('is-open');
    $('body').addClass('faktorak-modal-open');
  });

  $(document).on('click', '.faktorak-modal-close', function (e) {
    e.preventDefault();
    closeLinksModal($(this).closest('.faktorak-links-modal'));
  });

  $(document).on('click', '.faktorak-links-modal', function (e) {
    if (e.target === this) {
      closeLinksModal($(this));
    }
  });

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      $('.faktorak-links-modal.is-open').each(function () {
        closeLinksModal($(this));
      });
    }
  });
});

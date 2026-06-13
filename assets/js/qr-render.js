/* global QRCode */
(function () {
  function renderQr(el) {
    if (!el || el.getAttribute('data-qr-rendered') === '1') return;

    var text = el.getAttribute('data-qr');
    if (!text) return;

    var size = parseInt(el.getAttribute('data-qr-size') || '128', 10);
    if (!size || size < 32) size = 128;

    el.innerHTML = '';
    try {
      // eslint-disable-next-line no-new
      new QRCode(el, {
        text: text,
        width: size,
        height: size,
        correctLevel: QRCode.CorrectLevel.M,
      });
      el.setAttribute('data-qr-rendered', '1');
    } catch (e) {
      // no-op
    }
  }

  function renderAll() {
    if (typeof QRCode === 'undefined') return;
    var nodes = document.querySelectorAll('.faktorak-qr[data-qr]');
    for (var i = 0; i < nodes.length; i++) {
      renderQr(nodes[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderAll);
  } else {
    renderAll();
  }
})();

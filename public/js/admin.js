/* Admin conveniences: confirmation prompts and print buttons. */
(function () {
  'use strict';
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.dataset && form.dataset.confirm && !window.confirm(form.dataset.confirm)) e.preventDefault();
  });
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-print]')) window.print();
  });
})();

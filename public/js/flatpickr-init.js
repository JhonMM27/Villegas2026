(function () {
    'use strict';

    function initFlatpickr() {
        if (typeof window.flatpickr === 'undefined') {
            console.warn('Flatpickr no esta cargado.');
            return;
        }

        var locale = (window.flatpickr.l10ns && window.flatpickr.l10ns.es) || 'es';

        if (window.jQuery) {
            window.jQuery('.datetime-picker').each(function () {
                if (this._flatpickr) return;
                window.flatpickr(this, {
                    locale: locale,
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i',
                    altInput: false,
                    time_24hr: true,
                    minuteIncrement: 1,
                    allowInput: true,
                    disableMobile: false
                });
            });

            window.jQuery('.date-picker').each(function () {
                if (this._flatpickr) return;
                window.flatpickr(this, {
                    locale: locale,
                    dateFormat: 'Y-m-d',
                    altInput: false,
                    allowInput: true,
                    disableMobile: false
                });
            });
        } else {
            document.querySelectorAll('.datetime-picker').forEach(function (el) {
                if (el._flatpickr) return;
                window.flatpickr(el, {
                    locale: locale,
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i',
                    altInput: false,
                    time_24hr: true,
                    minuteIncrement: 1,
                    allowInput: true,
                    disableMobile: false
                });
            });

            document.querySelectorAll('.date-picker').forEach(function (el) {
                if (el._flatpickr) return;
                window.flatpickr(el, {
                    locale: locale,
                    dateFormat: 'Y-m-d',
                    altInput: false,
                    allowInput: true,
                    disableMobile: false
                });
            });
        }
    }

    function bindNowButtons() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('.btn-now');
            if (!btn) return;
            var targetId = btn.getAttribute('data-target');
            if (!targetId) return;
            var input = document.getElementById(targetId);
            if (input && input._flatpickr) {
                input._flatpickr.setDate(new Date(), false);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initFlatpickr();
            bindNowButtons();
        });
    } else {
        initFlatpickr();
        bindNowButtons();
    }

    window.reinitFlatpickr = initFlatpickr;
})();

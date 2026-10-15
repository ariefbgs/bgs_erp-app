/* BGS: Select2 search focus and debounced GET filters. */
(function () {
    'use strict';
    function start() {
        if (window.jQuery) {
            window.jQuery(document).off('select2:open.bgsFocus').on('select2:open.bgsFocus', function (event) {
                window.requestAnimationFrame(function () {
                    const instance = window.jQuery(event.target).data('select2');
                    const search = instance && ((instance.dropdown && instance.dropdown.$search) || (instance.selection && instance.selection.$search));
                    if (search && search.length && search[0].getClientRects().length) search[0].focus();
                });
            });
        }
        document.querySelectorAll('form').forEach(function (form) {
            if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get') return;
            const search = form.querySelector('input[name="search"], input[type="search"]');
            if (!search || !search.name || form.dataset.bgsAutoSearch === 'off') return;
            let timer, composing = false;
            const key = 'bgs-search:' + location.pathname + ':' + search.name;
            function submit() {
                clearTimeout(timer);
                form.querySelectorAll('[name="page"]').forEach(function (input) { input.value = '1'; });
                try { sessionStorage.setItem(key, JSON.stringify({value:search.value, caret:search.selectionStart, time:Date.now()})); } catch (_) {}
                if (form.requestSubmit) form.requestSubmit(); else form.submit();
            }
            search.addEventListener('compositionstart', function () { composing = true; clearTimeout(timer); });
            search.addEventListener('compositionend', function () { composing = false; clearTimeout(timer); timer = setTimeout(submit, 350); });
            search.addEventListener('input', function () { clearTimeout(timer); if (!composing) timer = setTimeout(submit, 350); });
            form.addEventListener('submit', function () { clearTimeout(timer); });
            form.querySelectorAll('select, input[type="date"]').forEach(function (input) { input.addEventListener('change', submit); });
            try {
                const state = JSON.parse(sessionStorage.getItem(key) || 'null');
                sessionStorage.removeItem(key);
                if (state && state.value === search.value && Date.now()-state.time < 15000) {
                    search.focus();
                    if (search.setSelectionRange && typeof state.caret === 'number') search.setSelectionRange(state.caret, state.caret);
                }
            } catch (_) {}
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();

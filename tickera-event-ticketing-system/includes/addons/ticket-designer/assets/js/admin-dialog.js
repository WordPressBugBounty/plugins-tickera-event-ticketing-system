/**
 * Venuera — shared admin confirm/alert dialog.
 *
 * window.VenueraDialog.confirm({ title, message, confirmText, cancelText, type })
 *   → Promise<boolean>  (true = confirmed, false = cancelled)
 *
 * window.VenueraDialog.alert({ title, message, okText, type })
 *   → Promise<void>  (resolves when dismissed). type: success | warning | danger | info
 *
 * Matches the Venue Designer "Unsaved Changes" dialog look. Markup is injected
 * on demand so any admin page that enqueues this script can use it.
 */
(function () {
    'use strict';

    var ICONS = {
        warning: '<svg viewBox="0 0 24 24" width="28" height="28"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/></svg>',
        danger:  '<svg viewBox="0 0 24 24" width="28" height="28"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/></svg>',
        info:    '<svg viewBox="0 0 24 24" width="28" height="28"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor"/></svg>',
        success: '<svg viewBox="0 0 24 24" width="28" height="28"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/></svg>'
    };
    var WARN_ICON = ICONS.warning;

    function ensureMarkup() {
        var existing = document.getElementById('venuera-shared-confirm-dialog');
        if (existing) { return existing; }

        var overlay = document.createElement('div');
        overlay.id = 'venuera-shared-confirm-dialog';
        overlay.className = 'venuera-dialog-overlay';
        overlay.style.display = 'none';
        overlay.innerHTML =
            '<div class="venuera-dialog" role="dialog" aria-modal="true">' +
                '<div class="venuera-dialog-icon venuera-dialog-icon-warning">' + WARN_ICON + '</div>' +
                '<div class="venuera-dialog-content">' +
                    '<h3 class="venuera-dialog-title"></h3>' +
                    '<p class="venuera-dialog-message"></p>' +
                '</div>' +
                '<div class="venuera-dialog-actions">' +
                    '<button type="button" class="venuera-dialog-btn venuera-dialog-cancel"></button>' +
                    '<button type="button" class="venuera-dialog-btn venuera-dialog-confirm"></button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(overlay);
        return overlay;
    }

    function applyType(iconEl, type) {
        var t = ICONS[type] ? type : 'warning';
        iconEl.className = 'venuera-dialog-icon venuera-dialog-icon-' + t;
        iconEl.innerHTML = ICONS[t];
    }

    function confirm(options) {
        options = options || {};
        return new Promise(function (resolve) {
            var overlay = ensureMarkup();
            var dialog = overlay.querySelector('.venuera-dialog');
            var titleEl = overlay.querySelector('.venuera-dialog-title');
            var msgEl = overlay.querySelector('.venuera-dialog-message');
            var confirmBtn = overlay.querySelector('.venuera-dialog-confirm');
            var cancelBtn = overlay.querySelector('.venuera-dialog-cancel');
            var iconEl = overlay.querySelector('.venuera-dialog-icon');

            titleEl.textContent = options.title || 'Confirm';
            msgEl.textContent = options.message || 'Are you sure?';
            confirmBtn.textContent = options.confirmText || 'Confirm';
            cancelBtn.textContent = options.cancelText || 'Cancel';

            cancelBtn.style.display = '';
            confirmBtn.classList.toggle('danger', options.type === 'danger');
            applyType(iconEl, options.type === 'danger' ? 'danger' : 'warning');

            overlay.classList.remove('closing');
            overlay.style.display = 'flex';

            function cleanup(result) {
                overlay.classList.add('closing');
                window.setTimeout(function () {
                    overlay.style.display = 'none';
                    overlay.classList.remove('closing');
                    confirmBtn.removeEventListener('click', onConfirm);
                    cancelBtn.removeEventListener('click', onCancel);
                    overlay.removeEventListener('click', onOverlay);
                    document.removeEventListener('keydown', onKey);
                    resolve(result);
                }, 150);
            }
            function onConfirm() { cleanup(true); }
            function onCancel() { cleanup(false); }
            function onOverlay(e) { if (e.target === overlay) { cleanup(false); } }
            function onKey(e) { if (e.key === 'Escape') { cleanup(false); } }

            confirmBtn.addEventListener('click', onConfirm);
            cancelBtn.addEventListener('click', onCancel);
            overlay.addEventListener('click', onOverlay);
            document.addEventListener('keydown', onKey);
            confirmBtn.focus();
        });
    }

    function alert(options) {
        options = options || {};
        return new Promise(function (resolve) {
            var overlay = ensureMarkup();
            var titleEl = overlay.querySelector('.venuera-dialog-title');
            var msgEl = overlay.querySelector('.venuera-dialog-message');
            var confirmBtn = overlay.querySelector('.venuera-dialog-confirm');
            var cancelBtn = overlay.querySelector('.venuera-dialog-cancel');
            var iconEl = overlay.querySelector('.venuera-dialog-icon');

            titleEl.textContent = options.title || '';
            msgEl.textContent = options.message || '';
            msgEl.style.display = options.message ? '' : 'none';
            confirmBtn.textContent = options.okText || 'OK';
            confirmBtn.classList.remove('danger');
            cancelBtn.style.display = 'none';
            applyType(iconEl, options.type || 'info');

            overlay.classList.remove('closing');
            overlay.style.display = 'flex';

            function cleanup() {
                overlay.classList.add('closing');
                window.setTimeout(function () {
                    overlay.style.display = 'none';
                    overlay.classList.remove('closing');
                    msgEl.style.display = '';
                    confirmBtn.removeEventListener('click', onOk);
                    overlay.removeEventListener('click', onOverlay);
                    document.removeEventListener('keydown', onKey);
                    resolve();
                }, 150);
            }
            function onOk() { cleanup(); }
            function onOverlay(e) { if (e.target === overlay) { cleanup(); } }
            function onKey(e) { if (e.key === 'Escape' || e.key === 'Enter') { cleanup(); } }

            confirmBtn.addEventListener('click', onOk);
            overlay.addEventListener('click', onOverlay);
            document.addEventListener('keydown', onKey);
            confirmBtn.focus();
        });
    }

    window.VenueraDialog = { confirm: confirm, alert: alert };
})();

/*
 * House fixes that need script. Loaded on every panel page, like house.css.
 *
 * copyable(): Filament copies on a click on a plain <span>, so the keyboard can't reach it and the result isn't
 * announced (WCAG 2.1.1, 4.1.3). Each copyable value becomes a button, named "Copy …", that Enter and Space copy too
 * (Carbon code snippet: "reachable by Tab and copied with Space or Enter"), and a status message says it was copied.
 */
/*
 * Row actions and fields edited in a table: every row's "Edit" or "Status" would otherwise have the same name for a
 * screen reader. Each is named after its row's first value, "Edit C-0001" or "Status, C-0001", starting with the visible label (Carbon link: "Avoid using the same link text for different
 * destinations"; WCAG 2.4.4, 2.5.3). Done here rather than on each action, so Filament keeps its fast row rendering.
 */
(() => {
    const status = () => {
        let region = document.getElementById('fs-house-status');

        if (! region) {
            region = Object.assign(document.createElement('div'), { id: 'fs-house-status', className: 'fi-sr-only' });
            region.setAttribute('role', 'status');
            document.body.append(region);
        }

        return region;
    };

    const text = (element) => element.textContent.replace(/\s+/g, ' ').trim();

    // Livewire's morph drops attributes the server didn't render, so they are set again after every change.
    const enhance = () => {
        document.querySelectorAll('.fi-copyable:not([tabindex])').forEach((value) => {
            value.tabIndex = 0;
            value.setAttribute('role', 'button');
            value.setAttribute('aria-label', `Copy ${text(value)}`);
        });

        document.querySelectorAll('.fi-ta-row').forEach((row) => {
            const cell = [...row.querySelectorAll(':scope > td:not(.fi-ta-selection-cell)')].find((td) => ! td.querySelector('.fi-ta-actions') && text(td) !== '');
            const name = cell ? text(cell).slice(0, 60) : '';

            if (name === '') {
                return;
            }

            row.querySelectorAll('.fi-ta-actions :is(a, button):not([aria-label])').forEach((action) => {
                if (text(action) !== '') {
                    action.setAttribute('aria-label', `${text(action)} ${name}`);
                }
            });

            // A field edited in the table is named by its column on every row; add the row ("Status, C-0001").
            row.querySelectorAll(':is(.fi-ta-text-input, .fi-ta-select, .fi-ta-checkbox, .fi-ta-toggle):is([aria-label]), :is(.fi-ta-text-input, .fi-ta-select, .fi-ta-checkbox, .fi-ta-toggle) [aria-label]').forEach((field) => {
                if (field.dataset.fsRow !== name) {
                    field.setAttribute('aria-label', `${field.getAttribute('aria-label')}, ${name}`);
                    field.dataset.fsRow = name;
                }
            });
        });
    };

    document.addEventListener('keydown', (event) => {
        if (! ['Enter', ' '].includes(event.key) || ! event.target.classList?.contains('fi-copyable')) {
            return;
        }

        event.preventDefault();
        event.target.click();
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest?.('.fi-copyable')) {
            // Cleared first, so copying the same value twice is announced twice.
            status().textContent = '';
            setTimeout(() => (status().textContent = 'Copied to clipboard'), 50);
        }
    });

    new MutationObserver(enhance).observe(document.documentElement, { childList: true, subtree: true });
    document.addEventListener('DOMContentLoaded', enhance);
})();

/*
 * When an action's modal closes, focus falls to the page: Filament doesn't give it back to the button that opened it,
 * and that button's row may be gone (Delete, Remove, Void). It goes back to the button, or to the same action on the
 * row that took its place, or the last row, or the table (W3C WAI-ARIA APG dialog: focus returns to the element that
 * opened it, or when that "no longer exists … another element that provides logical work flow"; WCAG 2.4.3).
 */
(() => {
    let last = null;
    let modal = false;

    document.addEventListener('focusin', (event) => {
        if (event.target.closest('.fi-modal')) {
            modal = true;

            return;
        }

        const row = event.target.closest('.fi-ta-row');
        const table = event.target.closest('.fi-ta');

        last = { element: event.target, table, label: event.target.textContent.trim(), index: row ? [...table.querySelectorAll('.fi-ta-row')].indexOf(row) : -1 };
    });

    // Once the modal has closed, which Filament may do by taking it out of the page without a closing event, and the
    // action's answer has come back, possibly without the row: settle on the closest thing left.
    const settle = (tries = 0) => {
        if (! modal || ! last || document.querySelector('.fi-modal-open') || ! [null, document.body].includes(document.activeElement)) {
            return;
        }

        if (last.element.isConnected && tries < 10) {
            return setTimeout(() => settle(tries + 1), 100);
        }

        modal = false;

        if (last.element.isConnected) {
            return last.element.focus();
        }

        if (! last.table?.isConnected) {
            return;
        }

        const rows = last.table.querySelectorAll('.fi-ta-row');
        const row = rows[Math.min(last.index, rows.length - 1)];
        const actions = row ? [...row.querySelectorAll('.fi-ta-actions :is(a, button)')].filter((action) => action.checkVisibility()) : [];
        const target = actions.find((action) => action.textContent.trim() === last.label) ?? actions[0] ?? last.table;

        if (target === last.table) {
            target.tabIndex = -1;
        }

        target.focus();
    };

    window.addEventListener('modal-closed', () => setTimeout(settle, 100));

    const hook = () => window.Livewire.hook('request', ({ succeed }) => succeed(() => setTimeout(settle, 100)));
    window.Livewire ? hook() : document.addEventListener('livewire:init', hook);
})();

/*
 * Chart axes: Filament gives a chart its text colour for the light or dark mode, but Chart.js draws each axis's ticks
 * and title in its own #666, about 3:1 on Filament's dark background (WCAG 1.4.3 asks 4.5:1). They take the chart's
 * colour, worked out on each draw so a mode switch follows, unless the chart sets its own.
 */
window.filamentChartJsGlobalPlugins = [...(window.filamentChartJsGlobalPlugins ?? []), {
    id: 'fsAxisText',
    // By now Chart.js has copied its default colour into each axis, so that is what gets replaced.
    beforeInit: (chart) => Object.values(chart.config.options.scales ?? {}).forEach((scale) => ['ticks', 'title'].forEach((part) => {
        scale[part] ??= {};

        if ([undefined, chart.constructor.defaults.color].includes(scale[part].color)) {
            scale[part].color = (context) => context.chart.options.color;
        }
    })),
}];

/*
 * An idle session ends without warning, and Livewire then reloads the page, losing what was typed (WCAG 2.2.1). The
 * session-warning modal opens a little before it ends; "Stay logged in", × or Esc touch the server, which starts the
 * idle time again. Every request does, so the clock restarts on each one, in any tab of the app.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Rendered after the scripts, at the end of the page.
    const settings = document.querySelector('[data-fs-session-lifetime]');

    if (! settings) {
        return;
    }

    const id = 'fs-session-ending';
    const key = 'fs-session-active-at';
    const lifetime = Number(settings.dataset.fsSessionLifetime) * 1000;
    const warning = Number(settings.dataset.fsSessionWarning) * 1000;
    let activeAt = Date.now();
    let timer;
    let open = false;

    const schedule = () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            open = true;
            window.dispatchEvent(new CustomEvent('open-modal', { detail: { id } }));
        }, Math.max(0, activeAt + lifetime - warning - Date.now()));
    };

    const active = (at = Date.now()) => {
        activeAt = Math.max(activeAt, at);

        try {
            localStorage.setItem(key, String(activeAt));
        } catch {}

        schedule();
    };

    // Another tab's request keeps this session alive too.
    window.addEventListener('storage', (event) => {
        if (event.key === key && Number(event.newValue) > activeAt) {
            activeAt = Number(event.newValue);
            schedule();

            if (open) {
                window.dispatchEvent(new CustomEvent('close-modal-quietly', { detail: { id } }));
                open = false;
            }
        }
    });

    window.addEventListener('modal-closed', (event) => {
        if (event.detail?.id !== id || ! open) {
            return;
        }

        open = false;
        // Any request restarts the idle time; if the session has already ended, the page goes to the log in screen.
        fetch(window.location.href, { method: 'HEAD', credentials: 'same-origin', redirect: 'manual' })
            .then((response) => (response.ok ? active() : window.location.reload()));
    });

    const hook = () => window.Livewire.hook('request', ({ succeed }) => succeed(() => active()));
    window.Livewire ? hook() : document.addEventListener('livewire:init', hook);
    document.addEventListener('livewire:navigated', () => active());

    schedule();
});

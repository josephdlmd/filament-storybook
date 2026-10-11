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

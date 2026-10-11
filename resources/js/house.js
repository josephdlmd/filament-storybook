/*
 * House fixes that need script. Loaded on every panel page, like house.css.
 *
 * copyable(): Filament copies on a click on a plain <span>, so the keyboard can't reach it and the result isn't
 * announced (WCAG 2.1.1, 4.1.3). Each copyable value becomes a button, named "Copy …", that Enter and Space copy too
 * (Carbon code snippet: "reachable by Tab and copied with Space or Enter"), and a status message says it was copied.
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

    // Livewire's morph drops attributes the server didn't render, so they are set again after every change.
    const enhance = () => document.querySelectorAll('.fi-copyable:not([tabindex])').forEach((value) => {
        value.tabIndex = 0;
        value.setAttribute('role', 'button');
        value.setAttribute('aria-label', `Copy ${value.textContent.trim()}`);
    });

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

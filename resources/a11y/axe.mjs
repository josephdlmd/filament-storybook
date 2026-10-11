// Runs axe-core on each story's rendered HTML, with the panel's CSS inlined, in headless Chromium, then checks it at
// 320px wide. Reads {css: [paths], stories: [{key, html, page?}]} on stdin; writes {key: [violation]} on stdout.
// playwright-core and axe-core resolve from the app first, then from this package (for its own tests).
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { join } from 'node:path';

const load = (name) => {
    for (const from of [join(process.cwd(), 'package.json'), import.meta.url]) {
        try {
            return createRequire(from).resolve(name);
        } catch {}
    }
    throw new Error(`${name} is not installed`);
};

const input = JSON.parse(readFileSync(0, 'utf8'));
const playwright = await import(load('playwright-core'));
const { chromium } = playwright.default ?? playwright;
const axe = readFileSync(load('axe-core/axe.min.js'), 'utf8');
const css = [...new Set(input.css)].map((path) => readFileSync(path, 'utf8')).join('\n');

const browser = await chromium.launch({ executablePath: process.env.STORYBOOK_CHROMIUM || undefined });
const page = await browser.newPage();
const results = {};

// Elements cut off at the right at 320px wide, unless they sit in a box that scrolls (a data table may; WCAG 1.4.10).
const reflow = () => [...document.querySelectorAll('main *, body > *')].filter((element) => {
    const box = element.getBoundingClientRect();

    // A dropdown's panel is closed until the script opens it, and then placed to fit the screen.
    if (box.width === 0 || box.right <= window.innerWidth + 1 || getComputedStyle(element).visibility === 'hidden' || element.closest('.fi-dropdown-panel')) {
        return false;
    }

    for (let parent = element.parentElement; parent; parent = parent.parentElement) {
        if (['auto', 'scroll'].includes(getComputedStyle(parent).overflowX)) {
            return false;
        }
    }

    return true;
}).filter((element, index, all) => ! all.includes(element.parentElement)).slice(0, 3)
    .map((element) => element.tagName.toLowerCase() + [...element.classList].slice(0, 2).map((name) => `.${name}`).join(''));

for (const story of input.stories) {
    await page.setViewportSize({ width: 1280, height: 720 });

    if (story.page) {
        // A whole page as the browser gets it, with its stylesheets and scripts swapped for the CSS read from disk.
        const html = story.html.replace(/<link[^>]+rel="stylesheet"[^>]*>/g, '').replace(/<script\b[\s\S]*?<\/script>/g, '');
        await page.setContent(html.replace('</head>', `<style>${css}</style></head>`));
    } else {
        await page.setContent(`<!doctype html><html lang="en"><head><title>${story.key}</title><style>${css}</style></head><body class="fi-body"><main>${story.html}</main></body></html>`);
        // A mounted action's modal is in the HTML, hidden until Alpine opens it; show it as the browser would.
        await page.evaluate(() => document.querySelectorAll('[wire\\:partial="action-modals"] .fi-modal').forEach((modal) => {
            modal.classList.add('fi-modal-open');
            [modal, ...modal.querySelectorAll('[x-cloak]:is(.fi-modal-close-overlay, .fi-modal-window-ctn, .fi-modal-window)')].forEach((element) => element.removeAttribute('x-cloak'));
        }));
    }

    await page.addScriptTag({ content: axe });
    // A story is scoped to itself, so whole-page rules (a level-one heading, landmarks) do not apply to a fragment.
    const { violations } = await page.evaluate((whole) => window.axe.run(whole ? document : document.querySelector('main'), { runOnly: ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'] }), Boolean(story.page));
    results[story.key] = violations.map((v) => ({ id: v.id, impact: v.impact, help: v.help, elements: v.nodes.map((n) => n.target.join(' ')).slice(0, 3) }));

    await page.setViewportSize({ width: 320, height: 640 });
    const cut = await page.evaluate(reflow);

    if (cut.length) {
        results[story.key].push({ id: 'reflow', impact: 'serious', help: 'Content is cut off or scrolls the page sideways at 320 CSS pixels wide (WCAG 1.4.10)', elements: cut });
    }
}

await browser.close();
process.stdout.write(JSON.stringify(results));

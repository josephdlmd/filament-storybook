// Runs axe-core on each story's rendered HTML, with the panel's CSS inlined, in headless Chromium.
// Reads {css: [paths], stories: [{key, html}]} on stdin; writes {key: [violation]} on stdout.
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

for (const story of input.stories) {
    await page.setContent(`<!doctype html><html lang="en"><head><title>${story.key}</title><style>${css}</style></head><body class="fi-body"><main>${story.html}</main></body></html>`);
    await page.addScriptTag({ content: axe });
    // Scoped to the story, so whole-page rules (a level-one heading, landmarks) do not apply to a fragment.
    const { violations } = await page.evaluate(() => window.axe.run(document.querySelector('main'), { runOnly: ['wcag2a', 'wcag2aa', 'wcag21aa'] }));
    results[story.key] = violations.map((v) => ({ id: v.id, impact: v.impact, help: v.help, elements: v.nodes.map((n) => n.target.join(' ')).slice(0, 3) }));
}

await browser.close();
process.stdout.write(JSON.stringify(results));

<?php

return [
    // A Chromium binary for the accessibility checks; empty uses Playwright's own (npx playwright-core install chromium).
    'chromium' => env('STORYBOOK_CHROMIUM'),
];

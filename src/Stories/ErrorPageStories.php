<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Schemas\Components\View;
use Josephdlmd\FilamentStorybook\Stories;

class ErrorPageStories extends Stories
{
    public function title(): string
    {
        return 'Feedback/Error pages';
    }

    public function description(): string
    {
        return 'The page the app shows in place of Laravel\'s own when a request fails: what happened in plain words as its heading, what to do next under it, one button that leads out, and the error\'s code small at the bottom, never as the heading. Each sits in Filament\'s simple layout, the login page\'s, with the panel\'s logo and theme; an error outside every panel\'s routes, such as a 404 for an address no route has, takes the default panel\'s. A 403 says who to ask for access, so change "an administrator" to the person or team who gives it; a 503 during maintenance leads back to the same address, since the rest of the app is down too. Run `php artisan vendor:publish --tag=filament-storybook-errors` once: it copies a file per page into `resources/views/errors` (403, 404, 419, 429, 500 and 503), each with only its words, which the app may change, while the layout stays in the package. `storybook-audit` warns while Laravel\'s own pages are still in use. Basis: Carbon empty states (Aug 2026: "plain language (no codes)"; "Suggest steps or process to request access"); USWDS 404 page (Mar 2023); NN/g error messages (May 2023).';
    }

    /** An address no page has: check it, or start again from the dashboard. */
    public function default(): View
    {
        return View::make('filament-storybook::errors.page')
            ->viewData(['code' => 404, 'heading' => 'Page not found', 'message' => 'Check the address for a typing mistake, or start again from the dashboard.']);
    }

    /** A page the user may not open says who gives access. */
    public function forbidden(): View
    {
        return View::make('filament-storybook::errors.page')
            ->viewData(['code' => 403, 'heading' => 'You don\'t have access to this page', 'message' => 'Ask an administrator to give you access.']);
    }

    /** A form sent after its page expired (a 419). */
    public function expired(): View
    {
        return View::make('filament-storybook::errors.page')
            ->viewData(['code' => 419, 'heading' => 'This page has expired', 'message' => 'Pages expire after a while for your security. Go back, reload the page and try again.']);
    }

    /** The server failed: nothing for the user to fix but to try again, or say so if it keeps happening. */
    public function serverError(): View
    {
        return View::make('filament-storybook::errors.page')
            ->viewData(['code' => 500, 'heading' => 'Something went wrong', 'message' => 'Try again in a few minutes. If it keeps happening, tell an administrator.']);
    }

    /** The whole app is down for maintenance, so the way out is to try the same address again. */
    public function maintenance(): View
    {
        return View::make('filament-storybook::errors.page')
            ->viewData(['code' => 503, 'heading' => 'Down for maintenance', 'message' => 'The app is being updated. Try again in a few minutes.', 'action' => 'Try again', 'url' => '#']);
    }
}

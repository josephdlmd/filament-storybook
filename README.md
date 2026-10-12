# Filament Storybook

Storybook's promise for Laravel: agents get structured UI context (patterns, stories, the real method signatures) and test feedback, so they can self-correct and ship production Filament UI.

| Storybook | Here |
|---|---|
| CSF file: meta + named exports | A class extending `Stories`: `title()`, `description()`, one public method per story |
| Renderer + iframe | `StoryCanvas`: each story in its own Livewire component, with real state and actions |
| Docs page + sidebar | The **Storybook** panel page: live story beside its PHP snippet, grouped by title |
| `components.json` manifest | `StoryRegistry::manifest()`: imports, snippets and docblocks, read by reflection |
| `docs-list` / `docs-show` | `storybook-list` and `storybook-show`, with an API section of the real signatures of the methods each story uses |
| `test-run` + a11y addon | `storybook-test`: renders stories through the real canvas, reports failures with the story line to fix, house-rule warnings, and WCAG 2.2 AA issues from axe-core |
| `get-storybook-story-instructions` | `storybook-instructions`: the rules for writing a story and the test-and-fix loop |
| (no equivalent) | `storybook-audit`: renders the app's real resource pages, their related lists and the dashboard's widgets, and reads the menu, signed in as a panel user, and reports render errors, rule breaks (red delete triggers, unconfirmed deletes, Filament's "Are you sure" text, a delete called Remove or the reverse, buttons labelled OK, Yes, Done, Submit or Confirm, Title Case labels, headings and page titles, label colons, more than one primary action, more than two inline row actions, filters hidden in a dropdown, empty states with no next step, toggles in saved forms, untypeable dates, uploads without limits, error messages that don't say what to do, money not right-aligned, placeholders, hidden labels, line-item columns that don't match their fields, rows that reorder only by dragging, read-only fields that are still saved, a View page that shows a disabled form, related lists in tabs, widgets that refresh themselves, a menu deeper than two levels, Title Case menu groups, a sidebar that shrinks to icons only) and WCAG 2.2 AA issues |
| Storybook Test | A Pest test that renders every story in CI |
| AGENTS.md instructions | `resources/boost/guidelines/core.blade.php`, loaded by Boost |

## Install

### The standard

The package works with apps that follow one standard: the `filament-starter` template's stack.
- Laravel with Filament 5, and a default panel in `app/Providers/Filament`
- Laravel Boost, installed for development
- Pest
- npm (the `package.json` Laravel ships with)

Apps are brought up to this standard; the package doesn't adapt to exceptions. The installer checks it first and, if anything is missing, says what to change and touches nothing.

### Connect an app

```bash
composer config repositories.filament-storybook vcs https://github.com/josephdlmd/filament-storybook
composer require josephdlmd/filament-storybook:^0.11
php artisan storybook:install
```

The installer:
- adds the plugin to the default panel;
- registers the package with Boost and refreshes the agent guidelines;
- writes the standard `tests/Feature/StoriesTest.php`, which renders every story and fails if Boost stops serving the tools;
- publishes the house CSS and ignores it in git;
- installs the accessibility check packages.

It's safe to run again. Then:
1. Once per machine, install Chromium for the accessibility checks: `npx playwright-core install chromium`.
2. Run the tests. The house defaults change required-field errors to "Enter name", outline delete buttons, keep error notifications open and relabel some actions (see House defaults).
3. Restart your coding agent and ask it to run `storybook-audit`. On an app built before these rules, treat what it reports as a backlog to fix screen by screen.

### Accessibility checks

`storybook-test` runs axe-core on every story that renders, in headless Chromium with the panel's theme CSS, so contrast is judged on real colours. It needs Node and, in the app:

```bash
npm install --save-dev playwright-core axe-core
npx playwright-core install chromium
```

Without them the tool still runs and says the check was skipped. Set `STORYBOOK_CHROMIUM` to use another Chromium binary.

## The agent loop

1. `storybook-list`, then `storybook-show`: the agent copies an approved snippet and passes only the arguments its API section shows.
2. No pattern fits: `storybook-instructions`, then it writes a story in `app/Stories`.
3. `storybook-test` for stories and `storybook-audit` for the screens built from them: failures, house-rule warnings and accessibility issues come back with what to fix. Failures name the story line that caused them, for example `Error: Call to a member function upper() on string at app/Stories/StatusStories.php:29`. It fixes and re-runs until everything passes.

## Write a story

```php
namespace App\Stories;

use Filament\Forms\Components\TextInput;
use Josephdlmd\FilamentStorybook\Stories;

class CustomerCodeStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Customer code';
    }

    public function description(): string
    {
        return 'The code on every customer form. Basis: GOV.UK text input (example in the hint, no placeholder).';
    }

    public function default(): TextInput
    {
        return TextInput::make('code')->helperText('For example, CUS-0001')->maxWidth('xs');
    }
}
```

The return type picks the canvas: a schema component (field, entry, layout), a table column, or an action. Column and entry stories take sample data from `records()`.

A whole screen is a story too. It takes and returns what the resource's `configure()` would, so its snippet pastes straight in:

```php
public function default(Table $table): Table
{
    return $table->columns([...])->filters([...]);
}
```

`Tables/List table`, `Forms/Record form` and `Records/View page` ship as the shared screen patterns, `Forms/Line items` as the lines of an invoice, bill or order, `Records/Details` as a record's summary of facts, and `Records/Locked` as a record that can no longer change. For invoices, orders and payments, `Forms/Document totals`, `Records/Document lifecycle`, `Actions/Void`, `Actions/Record payment` and `Forms/Record picker` cover the totals and VAT, posting, voiding, payments against the balance and picking a customer, supplier or item, with the money and numbering in classes to copy. `Tables/Bulk actions`, `Actions/Archive`, `Actions/Invite a user`, `Forms/Contact details` (email, Philippine phone and address), `Forms/Percentage` and `Forms/Settings page` cover the everyday flows of an internal tool; the invitation email and the page where an invited user sets their password are classes to copy. `Records/Paper receipt` lays out a collection for staff to copy onto the hand-written BIR receipt. `Actions/Download PDF` prints any other document in one shared, tagged A4 layout with spatie/laravel-pdf (Browsershot driver, which needs puppeteer and a Chrome on the server) and the app's details in `config/company.php` (name, address, TIN, VAT registration and the email address documents are sent from). `Actions/Email document` sends a posted document's PDF to the billing contact, queued, with a notification when it has gone; `PrintedDocument` also builds the payment acknowledgement and the customer's statement of account with its ageing. `Actions/Create next document` turns a quotation into an order, an order into deliveries (in parts) and a delivery into its invoice, with `Fulfilment` to copy. `Records/Stock` and `Actions/Adjust stock` keep stock at weighted average cost, never below zero, with `Stock` to copy. `Actions/Download supplier statement` prints what is owed to one supplier, to check against theirs, and `Actions/Credit limit` holds an order that would take a customer over their limit for approval, with `CreditLimit` to copy. `Reports/Tax withheld from suppliers` (quarterly 2307 details and the QAP) and `Reports/Tax withheld by customers` (the SAWT, with `Actions/Mark 2307 received`) cover withholding, with codes and rates in `WithholdingCode` rows. `Reports/Sales`, `Reports/Collections`, `Reports/Receivables ageing` and `Reports/Purchases and payables` are report pages: filters, a totals row, a PDF and a CSV or Excel export, each with its exporter to copy. House CSS and a house script cover what Filament has no setting for: the fields in a line-item table keep their labels for screen readers under the column headers, read-only fields look read-only, a closed menu is out of the keyboard's reach, small buttons in fields are 24px, copyable values and row actions work and are named for the keyboard and screen readers, a field's helper text and error are read with it, and focus moves to the next row when a dialog's row is deleted. The package registers both as Filament assets and `php artisan filament:upgrade` publishes them. They load on every panel page, since the patterns depend on them; `houseDefaults(false)` turns off only the defaults listed below.

## Where the rules come from

Storybook supplies the workshop, not the rules: a design system's guidelines say what good looks like, with their evidence, and Storybook hosts them beside the code. Here that is **[docs/rules.md](docs/rules.md)**: every rule the patterns and tools apply, with its source and date, how to do it in Filament, and how it is checked, plus the rules that were dropped and why.

The base design system is **Carbon** (IBM's system for enterprise products). Form wording and error messages follow **GOV.UK**, and **WCAG 2.2 AA** always wins. A rule that follows Carbon needs no further evidence; anything beyond or against Carbon carries its own current source, and the rulebook lists where we differ from Carbon and why. House preferences and older research were removed. Real-app references from Mobbin confirm or question a rule but never justify one. `storybook-instructions` gives agents the whole rulebook.

### Adding to the standards

Ask for it in plain words, in any session:
- **A UI rule** is added when Carbon, GOV.UK form guidance, WCAG or another current source supports it. If none does, the session says so instead of adding it.
- **A business fact** (a format, a term, a domain convention) is taken from you as given.

Shared standards change only in this repo, then reach the apps through a release. Patterns for a single app go in that app's `app/Stories`.

### House defaults

Where a Filament default breaks a sourced rule, the plugin corrects it app-wide:
- A delete button among other actions is outlined (low-emphasis danger); the confirm button stays solid red (Carbon button).
- Errors use GOV.UK's templates and the field's own label: "Enter name" or "Select payment terms", not "The name field is required"; "Credit limit must be a number", "… must be 0 or more", "… must be the same as or after …", "Enter TIN in the correct format" (GOV.UK error message, text input and date input). A field's own message for one rule leaves the others worded so.
- Filament's log in, sign-up and profile pages tell the browser which fields hold the user's own name and email (`autocomplete`), so it can fill them in (WCAG 1.3.5).
- A signed-in user is warned 2 minutes before an idle session ends, with Stay logged in and Log out (WCAG 2.2.1).
- An error notification, and a notification with an action, stay until dismissed; others still close after 6 seconds. An explicit `->duration()` or `->persistent()` wins (Carbon notifications).
- A modal's confirm button repeats the action's label (or is "Save" for an "Edit …" form), never "Submit" or "Confirm"; the New modal is titled "New supplier", not "Create Supplier"; modal headings name the model in sentence case (Carbon modal, button and content).
- An import states its row limit (`maxRows()`) before the upload, beside Filament's example file (USWDS file input).
- Filament's import wording: the failed-rows link is "Download errors", and a row's system error has no "please" (Carbon notifications, GOV.UK error message).
- Carbon's verbs replace Filament's: Attach and Associate are "Add", Detach and Dissociate are "Remove", Replicate is "Copy". The View modal closes with its × only, with no Close button (Carbon common actions).
- A modal with buttons (a form or a confirmation) doesn't close when the user clicks outside it, so a stray click loses no input; a View modal still does (Carbon modal).
- Pages use the full width of the browser instead of stopping at 1280px, unless the panel sets its own `maxContentWidth()` (Carbon 2x grid).
- A new password needs 15 characters (8 when the panel requires two-factor) and is checked against breached passwords through Have I Been Pwned, which gets only the first 5 characters of its hash; the field says "At least 15 characters" (NIST SP 800-63B-4).
- Filament's two-factor screens are worded to the house rules ("Security code", errors that say what to do, no links in the setup dialog, "log in"), and the user gets an email when the authenticator app is turned on or off (NIST SP 800-63B-4).
- A table's row checkboxes are named after their row ("Select SUP-0001"), "Deselect all" and the Reset links are not danger red, and a bulk delete is outlined (Carbon data table and button).
- The empty notifications panel says what will appear in it (Carbon empty states).

Turn this off with `StorybookPlugin::make()->houseDefaults(false)`.

For a data-dense app, `StorybookPlugin::make()->compactTables()` gives every table one even height: Carbon's 40px medium rows and header, and a toolbar and pagination with 32px controls and 8px around them, instead of Filament's mix of 37 to 49px, with the same 14px text. It's off by default, since Filament's rows already break no rule. A package test fails if a Filament upgrade renames the classes it adjusts.

`StorybookPlugin::make()->compact()` goes further, for an app that is data-dense throughout: compact tables, plus Carbon's small sizes wherever Filament's Compact theme is looser. The menu is 256px wide with 32px items, no gap and 16px icons, a field's label sits 8px above its input and its helper text 4px below, stacked radios are 8px apart and checkboxes 4px, breadcrumbs are 12px, and line items fit their 32px fields with 4px above and below. Use it with the Compact theme, which already makes inputs and buttons 32px.

One thing a plugin can't reach: Filament title-cases the model in each resource's page titles, navigation and breadcrumbs ("Create Supplier"). Add `use \Josephdlmd\FilamentStorybook\Concerns\HasSentenceCaseLabels;` and a `$recordTitleAttribute` to each resource; `storybook-audit` warns where it's missing.

## Render every story in the app's tests

`storybook:install` writes the standard `tests/Feature/StoriesTest.php` ([stubs/StoriesTest.php](stubs/StoriesTest.php)). The first test renders every story. The second fails the build if a Boost update ever stops giving agents the tools: they reach Boost through the `boost.mcp.tools.include` setting, which isn't in Boost's config file. Every app has the same file, so don't edit it there; change the stub.

## Agent eval

`eval/run.sh <app> <out> [prompt]` gives a headless Claude Code an ordinary feature request (by default "add Suppliers") in a throwaway app that has this package and Boost, without mentioning Storybook. `eval/score.py` then reports which Storybook tools it called, whether it looked up patterns before writing Filament code, and whether the code reuses them. Run it after changing the tools, the guideline or the instructions. On v0.5.0 the agent looked up the screen and field patterns before writing code, built the resource on `Tables/List table` and `Forms/Record form` with every field pattern including the shared TIN, then ran `storybook-audit`, added a record so the edit page could be audited too, and finished clean, for $0.83 in 146s. On v0.20.0 to v0.24.0, prompts for a purchase order with approvals and line items, a suppliers import and an admin dashboard each had the agent look up the matching patterns unprompted (approve and reject, related records, line items, import and export, dashboards), pass every applicable check, and finish with a clean audit, for $0.60 to $2.31 each. On v0.27.0, a prompt for sales invoices and purchase orders with VAT, posting, cancelling, withholding, payments both ways and printing had the agent look up 11 patterns, pass all 17 applicable checks (taking numbers inside the posting transaction and line totals from `DocumentTotals`) and finish with a clean audit, for $4.17 in 673s. On v0.58.0, a Suppliers prompt with contact details, putting several on hold at once and never deleting a used supplier had the agent look up 10 patterns unprompted (contact details, bulk actions and archive among them), pass all 16 applicable checks and finish with a clean audit, for $1.39 in 190s; it also found that the TIN pattern's `unique()` compared the TIN as typed, fixed in v0.58.1.

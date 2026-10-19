# Filament Storybook

Storybook's promise for Laravel: agents get structured UI context (patterns, stories, the real method signatures) and test feedback, so they can self-correct and ship production Filament UI.

| Storybook | Here |
|---|---|
| CSF file: meta + named exports | A class extending `Stories`: `title()`, `description()`, one public method per story |
| Renderer + iframe | `StoryCanvas`: each story in its own Livewire component, with real state and actions, and a screen in each of its five states (loading, empty, populated, error, forbidden), one tab each |
| Docs page + sidebar | The **Storybook** panel page: live story beside its PHP snippet, grouped by title |
| `components.json` manifest | `StoryRegistry::manifest()`: imports, snippets and docblocks, read by reflection |
| `docs-list` / `docs-show` | `storybook:list` and `storybook:show`, with an API section of the real signatures of the methods each story uses |
| `test-run` + a11y addon | `storybook:test`: renders stories through the real canvas, reports failures with the story line to fix, house-rule checks (each fails by its id unless the owner settled it), and WCAG 2.2 AA issues from axe-core |
| `get-storybook-story-instructions` | `storybook:instructions`: the rules for writing a story and the test-and-fix loop |
| (no equivalent) | `storybook-audit`: renders one panel at a time (`panel`, for an app with several, failing when one lacks the plugin) and its real resource pages, including a resource's custom page of one record, their related lists, the panel's own pages (following one that sends the user back to itself with a query as it opens) and the dashboard's widgets, and reads the menu, signed in as a panel user, and reports render errors, rule breaks (red delete triggers, unconfirmed deletes, Filament's "Are you sure" text, a delete called Remove or the reverse, buttons labelled OK, Yes, Done, Submit or Confirm, Title Case labels, headings and page titles, label colons, more than one primary action, more than two inline row actions, filters hidden in a dropdown, empty states with no next step, toggles in saved forms, untypeable dates, uploads without limits, error messages that don't say what to do, money not right-aligned, placeholders, hidden labels, line-item columns that don't match their fields, rows that reorder only by dragging, read-only fields that are still saved, a View page that shows a disabled form, related lists in tabs, widgets that refresh themselves, a menu deeper than two levels, Title Case menu groups, a sidebar that shrinks to icons only) and WCAG 2.2 AA issues |
| Storybook Test | A Pest test that renders every story in CI |
| AGENTS.md instructions | `resources/boost/guidelines/core.blade.php`, loaded by Boost |

## Install

### The standard

The package works with apps that follow one standard: the `filament-starter` template's stack.
- Laravel with Filament 5, and a default panel in `app/Providers/Filament`
- Filament's Compact theme (a paid licence), which the look is built on
- Chromium from Playwright, locally and in CI, for the accessibility checks the tests run
- Laravel Boost, installed for development
- Pest
- npm (the `package.json` Laravel ships with)

Apps are brought up to this standard; the package doesn't adapt to exceptions. The installer checks it first and, if anything is missing, says what to change and touches nothing.

### Connect an app

```bash
composer config repositories.filament-storybook vcs https://github.com/josephdlmd/filament-storybook
composer require josephdlmd/filament-storybook:^0.87
php artisan storybook:install
```

The installer:
- adds the plugin to the default panel;
- registers the package with Boost and refreshes the agent guidelines;
- lets agents run `php artisan storybook:*` without asking, in `.claude/settings.json`;
- writes the standard `tests/Feature/StoriesTest.php`, which renders every story, fails if Boost stops serving the tools, and runs `storybook:test` and `storybook-audit` in strict mode;
- publishes the house CSS and ignores it in git;
- installs the accessibility check packages.

It's safe to run again. Then:
1. Once per machine, install Chromium for the accessibility checks: `npx playwright-core install chromium`.
2. Make each panel's theme (`resources/css/filament/<panel>/theme.css`, set with `->viteTheme()`) the package theme and its `@source` lines, then `npm run build`:
   ```css
   @import '../../../../vendor/josephdlmd/filament-storybook/resources/css/theme.css';

   @source '../../../../app/Filament/**/*';
   @source '../../../../resources/views/filament/**/*';
   ```
3. Run the tests. The house defaults change required-field errors to "Enter name", outline delete buttons, keep error notifications open and relabel some actions (see House defaults).
4. Restart your coding agent and ask it to run `storybook-audit`. On an app built before these rules, treat what it reports as a backlog to fix screen by screen.

### Accessibility checks

`storybook:test` runs axe-core on every story that renders, in headless Chromium with the panel's theme CSS, so contrast is judged on real colours. It needs Node and, in the app:

```bash
npm install --save-dev playwright-core axe-core
npx playwright-core install chromium
```

Without them, `storybook:test` and `storybook-audit` fail with `a11y.unchecked`: strict mode has no skipped checks. Set `STORYBOOK_CHROMIUM` to use another Chromium binary.

## The agent loop

Agents run the commands through Bash, in the checkout they work in; `storybook:install` allows `php artisan storybook:*` in `.claude/settings.json`. The Boost MCP tools of the same names stay for one release as a fallback.

1. `storybook:list`, then `storybook:show`: the agent copies an approved snippet and passes only the arguments its API section shows.
2. No pattern fits: `storybook:instructions`, then it writes a story in `app/Stories`.
3. `storybook:test` for stories and `storybook-audit` for the screens built from them: failures, failed house-rule checks and accessibility issues come back with what to fix. Failures name the story line that caused them, for example `Error: Call to a member function upper() on string at app/Stories/StatusStories.php:29`. It fixes and re-runs until everything passes.

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

`Tables/List table`, `Forms/Record form` and `Records/View page` ship as the shared screen patterns, `Forms/Line items` as the lines of an invoice, bill or order, `Records/Details` as a record's summary of facts, `Records/Key figures` as the few figures at the top of a page of one task, `Records/Previous and next` for going through a series of records, and `Records/Locked` as a record that can no longer change. For invoices, orders and payments, `Forms/Document totals`, `Records/Document lifecycle`, `Actions/Void`, `Actions/Record payment` and `Forms/Record picker` (with a + to add a record not in the list yet) cover the totals, posting, voiding, payments against the balance and picking a customer, supplier or item, with the money, numbering and statuses in package classes the app calls (`DocumentTotals`, `DocumentNumber`, `DocumentStatus`, `PaymentStatus`). `Tables/Bulk actions`, `Actions/Archive`, `Actions/Invite a user`, `Forms/Contact details` (email, phone and a Philippine address), `Forms/Percentage` and `Forms/Settings page` cover the everyday flows of an internal tool; the invitation email and the page where an invited user sets their password are classes to copy. `Actions/Download PDF` prints any other document in one shared, tagged A4 layout with spatie/laravel-pdf (Browsershot driver, which needs puppeteer and a Chrome on the server) and the app's details in `config/company.php` (name, address and the email address documents are sent from). `Actions/Email document` sends a posted document's PDF to the billing contact, queued, with a notification when it has gone; `PrintedDocument` also builds the payment acknowledgement and the customer's statement of account with its ageing. `Actions/Create next document` turns a quotation into an order, an order into deliveries (in parts) and a delivery into its invoice, with `Fulfilment` to copy. `Records/Stock` and `Actions/Adjust stock` keep stock at weighted average cost, never below zero, with `Stock` to copy. `Actions/Download supplier statement` prints what is owed to one supplier, to check against theirs, and `Actions/Credit limit` holds an order that would take a customer over their limit for approval, with `CreditLimit` to copy. `Reports/Sales`, `Reports/Collections`, `Reports/Receivables ageing` and `Reports/Purchases and payables` are report pages: filters, a totals row, a PDF and a CSV or Excel export, each with its exporter to copy. House CSS and a house script cover what Filament has no setting for: the fields in a line-item table keep their labels for screen readers under the column headers, read-only fields look read-only, a closed menu is out of the keyboard's reach, small buttons in fields are 24px, copyable values and row actions work and are named for the keyboard and screen readers, a field's helper text and error are read with it, and focus moves to the next row when a dialog's row is deleted. The package registers both as Filament assets and `php artisan filament:upgrade` publishes them. They load on every panel page, since the patterns depend on them, as do the defaults listed below.

**Philippine books:** VAT, withholding, TINs and BIR forms are in [filament-storybook-ph](https://github.com/josephdlmd/filament-storybook-ph), an add-on an app that keeps Philippine books requires alongside this package (`composer require josephdlmd/filament-storybook-ph:^0.1`, after adding its repository as its README says). It layers on the core patterns: its stories show what it adds (VAT on documents and reports, the tax withheld from payments, TINs, the 2307, SAWT and QAP, the paper receipt), and `storybook:instructions` returns its rules after these.

## Where the rules come from

Storybook supplies the workshop, not the rules: a design system's guidelines say what good looks like, with their evidence, and Storybook hosts them beside the code. Here that is **[docs/rules.md](docs/rules.md)**: every rule the patterns and tools apply, with its source and date, how to do it in Filament, and how it is checked, plus the rules that were dropped and why.

The base design system is **Carbon** (IBM's system for enterprise products). Form wording and error messages follow **GOV.UK**, and **WCAG 2.2 AA** always wins. A rule that follows Carbon needs no further evidence; anything beyond or against Carbon carries its own current source, and the rulebook lists where we differ from Carbon and why. House preferences and older research were removed. Real-app references from Mobbin confirm or question a rule but never justify one. `storybook:instructions` gives agents the whole rulebook.

### Strict mode and settled exceptions

Every finding fails: a render error, a broken house rule (each named by its check id, such as `forms.read-only-saved`) and an accessibility issue. There is no warning tier and no setting to turn it off. Where the owner decides a screen keeps something a check flags, the app lists it in `config/storybook-exceptions.php`; the tools then show it under "Settled exceptions" instead of failing:

```php
<?php

return [
    ['check' => 'forms.read-only-saved', 'target' => 'EditInvoice', 'field' => 'total', 'reason' => 'The total is checked again on posting.', 'approved' => 'owner, 2026-10-15'],
];
```

`target` is the page or story key the report names, and `field` (optional) the field or column the finding is about. An entry missing its reason or approval fails, and so, on a full run, does one that no longer matches anything. Accessibility issues can't be settled. Agents are told never to add an entry themselves.

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
- Every table keeps its applied filters, search and sort for the session, until the user clears them; a table that sets its own persistence keeps it (Carbon search).
- Pages use the full width of the browser instead of stopping at 1280px, unless the panel sets its own `maxContentWidth()` (Carbon 2x grid).
- A new password needs 15 characters (8 when the panel requires two-factor) and is checked against breached passwords through Have I Been Pwned, which gets only the first 5 characters of its hash; the field says "At least 15 characters" (NIST SP 800-63B-4).
- Filament's two-factor screens are worded to the house rules ("Security code", errors that say what to do, no links in the setup dialog, "log in"), and the user gets an email when the authenticator app is turned on or off (NIST SP 800-63B-4).
- A table's row checkboxes are named after their row ("Select SUP-0001"), "Deselect all" and the Reset links are not danger red, and a bulk delete is outlined (Carbon data table and button).
- The empty notifications panel says what will appear in it (Carbon empty states).
- Global search heads each resource's group in sentence case ("Purchase orders"), as `HasSentenceCaseLabels` leaves the model label lower case; a panel that sets its own `globalSearch()` provider keeps it (Carbon content).

### The look

Every app has the same look, which the plugin and the package theme set: Inter, Filament's blue as primary with its zinc greys, Filament's corners and icons, light and dark, and compact as the only density. An app sets only its name, logo and favicon; `storybook-audit` fails a panel that sets its own colours or font, or adds CSS to its theme (see "The look" in `docs/rules.md`).

Compact is Filament's Compact theme plus Carbon's small sizes wherever it is looser. Tables have 40px rows and header, with a toolbar and pagination of 32px controls and 8px around them; the menu is 256px wide with 32px items, no gap and 16px icons; a field's label sits 8px above its input and its helper text 4px below; stacked radios are 8px apart and checkboxes 4px; breadcrumbs are 12px; and line items fit their 32px fields with 4px above and below. A package test fails if a Filament upgrade renames the classes it adjusts.

One thing a plugin can't reach: Filament title-cases the model in each resource's page titles, navigation and breadcrumbs ("Create Supplier"). Add `use \Josephdlmd\FilamentStorybook\Concerns\HasSentenceCaseLabels;` and a `$recordTitleAttribute` to each resource; `storybook-audit` warns where it's missing.

## Render every story in the app's tests

`storybook:install` writes the standard `tests/Feature/StoriesTest.php` ([stubs/StoriesTest.php](stubs/StoriesTest.php)). The first test renders every story. The second fails the build if a Boost update ever stops giving agents the tools: they reach Boost through the `boost.mcp.tools.include` setting, which isn't in Boost's config file. The last two run `storybook:test` and `storybook-audit`, accessibility included, and fail on anything the owner hasn't settled (see Strict mode). For the audit, the test runs the app's database seeder (so one of its users may open each panel), lets every policy allow, makes one record per resource with its model's factory, and audits every panel; a page it still can't open fails, so give the model a factory, seed what it needs, or ask the owner to settle it. CI needs the built assets and a Playwright Chromium (`npx playwright-core install chromium`). Every app has the same file, so don't edit it there; change the stub.

## Agent eval

`eval/run.sh <app> <out> [prompt]` gives a headless Claude Code an ordinary feature request (by default "add Suppliers") in a throwaway app that has this package and Boost, without mentioning Storybook. `eval/score.py` then reports which Storybook tools it called, whether it looked up patterns before writing Filament code, and whether the code reuses them. Run it after changing the tools, the guideline or the instructions, in an app that is migrated, seeded and built (`php artisan migrate --seed`, `npm run build`), so the agent can run its tests and `storybook-audit` can sign in. On v0.5.0 the agent looked up the screen and field patterns before writing code, built the resource on `Tables/List table` and `Forms/Record form` with every field pattern including the shared TIN, then ran `storybook-audit`, added a record so the edit page could be audited too, and finished clean, for $0.83 in 146s. On v0.20.0 to v0.24.0, prompts for a purchase order with approvals and line items, a suppliers import and an admin dashboard each had the agent look up the matching patterns unprompted (approve and reject, related records, line items, import and export, dashboards), pass every applicable check, and finish with a clean audit, for $0.60 to $2.31 each. On v0.27.0, a prompt for sales invoices and purchase orders with VAT, posting, cancelling, withholding, payments both ways and printing had the agent look up 11 patterns, pass all 17 applicable checks (taking numbers inside the posting transaction and line totals from `DocumentTotals`) and finish with a clean audit, for $4.17 in 673s. On v0.58.0, a Suppliers prompt with contact details, putting several on hold at once and never deleting a used supplier had the agent look up 10 patterns unprompted (contact details, bulk actions and archive among them), pass all 16 applicable checks and finish with a clean audit, for $1.39 in 190s; it also found that the TIN pattern's `unique()` compared the TIN as typed, fixed in v0.58.1. On v0.60.0, a prompt for sales orders delivered in parts, one invoice per delivery, a credit-limit approval, cancelling and payments with withholding had the agent look up 13 patterns unprompted (create next document and credit limit among them) and pass all 24 applicable checks, for $4.25 in 693s; its credit-limit rule, delivery quantities and serial checks matched the business standards. On v0.61.0, a prompt for customer collections with withholding for the SAWT and an ageing report as of a date had the agent look up 8 patterns unprompted and pass all 14 applicable checks, for $3.04 in 468s; it kept each payment's income payment and ATC, based the VAT withheld on the VATable sales and left invoices dated after the as-of date out of the ageing. On v0.63.0, a Suppliers prompt with contact people, putting several on hold at once and seeing only those on hold had the agent look up 11 patterns unprompted and pass all 8 applicable checks with a clean audit, for $1.80 in 246s; it filtered by status, cleared the selection after a bulk action and left the short contacts list without search, as the rules say where FilaCheck differs. On v0.67.0, a prompt for customers imported from CSV with their withholding code, archived several at once, sales orders with a status and a dashboard narrowed to a date range had the agent look up 12 patterns unprompted and pass all 18 applicable checks with a clean audit, for $2.40 in 311s; it put the status's word and colour on an enum, imported the withholding code through `relationship()` with the house error wording, and gave the dashboard one date filter the chart reads. On v0.78.2, a prompt for purchase orders with a supplier added without leaving the order, going through orders one after another, a report whose rows open their order and a chart of what is unpaid by days past due had the agent look up 8 patterns unprompted, use the record picker's add, previous and next, the report's drill-down and filters in the URL and the stacked ageing bar, and finish with a clean audit of 11 pages and 42 passing tests, for $1.84 in 274s; its ageing counted orders dated after today, so the chart's comment now says to leave them out. On v0.80.0, a prompt for sales invoices that are posted and voided, with who did what and when, notes, and finding an invoice from the panel's search had the agent look up 9 patterns unprompted (history, notes and the View page among them) and pass all 9 applicable checks, for $2.21 in 270s: it wrote each event with `activity()` inside the posting and voiding transactions, kept notes with their writer's id and the 1,000-character limit, let only the writer edit or delete one, made the number and customer searchable, and raised the activity log's `clean_after_days` to five years on its own. Its app was neither seeded nor built, so it couldn't audit; seeded afterwards, its 48 tests pass and the audit is clean on 12 pages. On v0.84.0 with Filament 5.10.2, a prompt for customers and sales orders made in steps, a customer's orders on a page of their own, a dashboard of the last 12 months and this month's figures, and finding an order from the search at the top had the agent look up 8 patterns unprompted and pass 16 of 17 applicable checks, finishing with a clean audit of 13 pages and 42 passing tests, for $2.23 in 329s: its wizard ended with Check answers, its chart listed every month and its search loaded the customer with the results, but it didn't cache the dashboard's figures, which the pattern's text never asked for. The user's own Claude Code plugins were loaded in that run. On the breaking release's branch (locked look, strict checks, Screen states, PH parts in the add-on), the default Suppliers prompt without the TIN had the agent look up 7 patterns before writing code and pass all 9 applicable checks, for $1.24 in 151s; it audited its screens, and its 48 tests pass, the strict audit in `StoriesTest` included. Setting up that run found entry placeholders under 4.5:1, fixed in the same release.

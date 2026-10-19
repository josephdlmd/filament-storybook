# Which Screens come first, and their basis

Research for [#21](https://github.com/josephdlmd/filament-storybook/issues/21), 2026-10-19. For the owner to review; nothing here is a rule until it is added to `docs/rules.md`.

The question: which Screens (GLOSSARY: a pattern for a whole page, owing loading, empty, populated, error and forbidden, with no empty state on a record screen) the breaking release ships, what covers each today, its basis under the rules policy in `AGENTS.md`, and which Filament 5 components build it natively.

## Findings in short

1. **Most candidate screens already have a pattern, but none is a Screen yet.** The stories show the populated state, with the empty state configured inside it (`ListTableStories::default` sets `emptyStateHeading`). No story renders a screen in each of the five states, so the work is mostly adding state stories to existing patterns, not new patterns.
2. **Three states are already solved app-wide and need only a story each, not new rules:**
   - **Loading:** the house default turns on `Table::loadingSkeleton()` (`src/StorybookPlugin.php:446`), and widgets use Filament's skeleton card (rule in `docs/rules.md`, Tables and finding). Carbon loading pattern (updated Aug 11, 2026: skeletons "for a page's initial load" on data tables, structured lists and cards; dashboards are its main case for progressive loading). <https://carbondesignsystem.com/patterns/loading-pattern/>
   - **Forbidden:** a page the user may not open is Filament's 403 (`canAccess()` on a page, policies on a resource, `canAccessPanel()` on a panel), rendered by `ErrorPageStories::forbidden`. "Can view but not change" is populated with actions hidden: `RecordLockedStories::default`. Carbon empty states (Aug 11, 2026: permissions, "suggest how to request access") <https://carbondesignsystem.com/patterns/empty-states-pattern/>; Carbon read-only states (Aug 2026).
   - **Error:** a whole page failing is `ErrorPageStories::serverError` (and `maintenance`). A section failing inside a working page is Carbon's "error management" empty state ("Explain why the data is missing and what the user can do", "always provide a path forward"). No story shows that section-level error yet.
3. **Outside-user pages are not new Screens.** A supplier's orders is a list screen, a customer's statement is a report or record screen, in a second panel. Filament builds the separation natively: `canAccessPanel(Panel $panel)` on the user model (Filament 5, Users overview) <https://filamentphp.com/docs/5.x/users/overview>, and for one outside party per user, global scopes rather than full tenancy ("stick to simple one-to-many tenancy"; "Tenant scoping is not record authorization") <https://filamentphp.com/docs/5.x/users/tenancy>. No qualifying source sets UI rules for customer portals as such; what outsiders may see is a **business fact for the owner**.
4. **The glossary's "no empty state" exception is too narrow.** Sign-in, a create form, a wizard and the settings page have no empty state either: nothing is listed on them. Proposal: "a record or form screen has no empty state" (owner to decide; GLOSSARY edit).
5. **Sign-in is Filament's own page, reworded by the house defaults, with no story.** Its basis is complete (Carbon login, NIST SP 800-63B-4, already in `docs/rules.md` Sign-in), so it needs stories only, rendered from Filament's `Login` page.

## Per candidate

Coverage cites `src/Stories/<File>.php::<method>`. "Missing states" are the ones no story shows.

### 1. Sign-in (log in, forgotten password, reset, two-factor)

- **Coverage:** none as a story. House defaults reword Filament's auth pages (`src/StorybookPlugin.php:279–383`: "Log in", "Incorrect email or password. Try again.", password helper text). `Invitations/AcceptInvitation.php` is Filament's reset-password page, used by `InviteUserStories::default`.
- **Basis:** Carbon login (updated Aug 12, 2026: the title says "Log in"; inline errors below the field; one generic message for a wrong email or password; a "Forgot password" link; optional "Remember ID") <https://carbondesignsystem.com/patterns/login-pattern/>. NIST SP 800-63B-4 (Aug 2025) and WCAG 3.3.8 for passwords and multi-factor (in `docs/rules.md`). GOV.UK Passwords and Create accounts patterns cover form wording only. USWDS authentication pages (v3.14.0, no date shown) only point to login.gov and link templates, so add nothing <https://designsystem.digital.gov/templates/authentication-pages/>.
- **Not adopted:** Carbon's two-step flow (email, Continue, then password) exists for IBM's SSO routing; Filament asks both on one page and no source requires two steps. Flag only, no rule.
- **Native:** the panel's `login()`, `passwordReset()`, `emailVerification()`, `profile()` and multi-factor (`AppAuthentication`); Filament's simple layout.
- **States:** populated (the form), error (failed log in, throttled 429), forbidden (`canAccessPanel()` false: Filament's 403). Loading and empty don't apply.

### 2. List (a resource's List page)

- **Coverage:** `ListTableStories::default`, `::rowActions`, `::dateRange`; `BulkActionStories`, `ImportExportStories`, `StatusBadgeStories`, `MoneyColumnStories`, `ArchiveStories`, `InviteUserStories::list`.
- **Basis:** in `ListTableStories::description()`: Carbon data table, filtering, search, menu (Sep 2026) and empty states (Aug 2026); NN/g data tables 2022; USWDS table; WCAG 2.5.7.
- **Native:** `Table` with `TextColumn`, `SelectFilter`/`TernaryFilter` with `FiltersLayout::AboveContent`, `ActionGroup`, `BulkActionGroup`, `emptyState*()`, `loadingSkeleton()`, `getTabs()` on the List page.
- **Missing states:** loading (skeleton), "no results" after a search (empty, second kind), error (section-level), forbidden.

### 3. Record view (a resource's View page)

- **Coverage:** `RecordViewPageStories::default` (layout), `RecordDetailsStories::default`, `RelatedRecordsStories::default`/`::linked`, `RecordHistoryStories`, `RecordNotesStories`, `RecordNeighboursStories`, `RecordLockedStories::default`, `KeyFiguresStories::default`, `DocumentLifecycleStories::default`.
- **Basis:** in the descriptions: Carbon 2x grid, structured list, tabs, read-only states (Aug–Sep 2026); NN/g tabs 2024; WCAG 1.3.2, 1.4.3, 1.4.10.
- **Native:** infolist `Schema` with `Grid`, `Section`, `TextEntry`, `RepeatableEntry`, `Livewire` (embedded relation managers), header actions.
- **Missing states:** loading (`deferLoading()` sections, engineering rule in `docs/rules.md`), error (a related list that fails), forbidden (403 and actions hidden). No empty.

### 4. Create and edit form

- **Coverage:** `RecordFormStories::default`, `FormContainerStories::default`/`::slideOver`/`::createAnother`, every field pattern (`TinInputStories`, `MoneyInputStories`, `ChoiceStories`, `DateStories`, `LineItemsStories`, `DocumentTotalsStories`, …).
- **Basis:** Carbon forms (Aug 2026), W3C WAI forms tutorial, USWDS text input, GOV.UK error messages (all in `RecordFormStories::description()` and `docs/rules.md` Forms).
- **Native:** form `Schema` on the resource's Create and Edit pages, `Section`, fields, the house defaults (no asterisk, browser validation off).
- **Missing states:** error (validation failed, errors marked: GOV.UK "Recover from validation errors" pattern <https://design-system.service.gov.uk/patterns/>), forbidden. No empty; loading only for an edit form with deferred parts.

### 5. Wizard

- **Coverage:** `FormContainerStories::wizard` (steps, Check answers).
- **Basis:** in `docs/rules.md` (Feedback and dialogs): Carbon progress indicator, forms, modal (Aug–Sep 2026); WCAG 3.3.4, 3.3.7; GOV.UK check answers. W3C WAI multi-page forms (updated 27 Jul 2019, "Step 2 of 4" in the title) is older than the policy's 2022 line, so it can't add a rule; WCAG itself still applies <https://www.w3.org/WAI/tutorials/forms/multi-page/>.
- **Native:** `Wizard` and `Step`; `HasWizard` on a Create page.
- **Missing states:** error (a step fails its check), forbidden. A Screen only when it is its own Create page; in a modal it stays part of the form pattern.

### 6. Report

- **Coverage:** `SalesReportStories::default` (the pattern every report follows), `CollectionsReportStories::default`, `ReceivablesAgeingStories::default`, `PurchasesReportStories::default`/`::payables`, `CustomerWithholdingStories`, `SupplierWithholdingStories`.
- **Basis:** owner's business standards (2026-10-12); USWDS table; Carbon data table, filtering, dashboards (Sep 2026).
- **Native:** a custom page with `InteractsWithTable`, summarizers (`Sum`), `ExportAction`, `canAccess()`, `#[Url] $tableFilters`.
- **Missing states:** loading, empty ("nothing posted in this range": Carbon's user-action empty state), error, forbidden (no "view reports" permission).

### 7. Dashboard

- **Coverage:** `DashboardStories::default`/`::twoSeries`/`::chart`/`::ageing`, with `Widgets/Dashboard.php`, `OrderStats`, `OrdersPerMonthChart`, `CashFlowChart`, `ReceivablesAgeingChart`.
- **Basis:** Carbon dashboards, chart pages, color palettes, status indicators (Sep 2026); W3C WAI complex images (Apr 2026); WCAG (in `DashboardStories::description()`). Carbon loading (Aug 11, 2026) names dashboards as the case for progressive loading.
- **Native:** `Dashboard` page with `HasFiltersForm`, `StatsOverviewWidget`, `ChartWidget`, `TableWidget`, `$pollingInterval = null`.
- **Missing states:** loading (skeleton cards), empty (rule exists: "An empty widget says why there is no data"; no story), error (one widget fails), forbidden (a widget hidden by `canView()`).

### 8. Settings

- **Coverage:** `SettingsPageStories::default`.
- **Basis:** "No source sets rules for settings pages as such; this applies the form rules" (its description): Carbon forms, global header (Aug 2026), NN/g accordions 2023.
- **Native:** spatie settings plugin's `SettingsPage`, `Section`, fields, `canAccess()`.
- **Missing states:** error (validation), forbidden. No empty.

### 9. Outside-user pages (a supplier's orders, a customer's statement)

- **Coverage:** the documents exist for staff: `DownloadPdfStories::statementOfAccount` and `::supplierStatement`, `SupplierStatementStories::default`. Nothing in an outside panel.
- **Basis:** no qualifying UI source for portals beyond the general rules (same Look, same rules: GLOSSARY "Panel"). Carbon global header (Aug 2026: "an appropriate domain model rather than an org chart") argues for an outside panel's menu named by the outsider's tasks. Everything else, such as what a supplier sees, whether they confirm orders and how they get an account (invitation, `InviteUserStories`, or `registration()`), is a business fact.
- **Native:** a second `Panel` with `canAccessPanel()`, resources whose `getEloquentQuery()` or a global scope keeps the party's own records, plus policies; the same list, record and report screens.
- **Recommendation:** no separate Screen. Ship one note in the list and record screen descriptions on scoping to the signed-in party, and ask the owner the business questions below before any portal story.

### 10. Found beyond the candidates

| Screen | Coverage | Basis | Recommendation |
|---|---|---|---|
| Error pages (403, 404, 419, 429, 500, 503) | `ErrorPageStories::default`, `::forbidden`, `::expired`, `::serverError`, `::maintenance` | Carbon empty states; USWDS 404 page (Mar 2023); NN/g 2023 | Already a full screen set; they carry every other screen's page-level error and forbidden states |
| Profile and two-factor | Filament's `profile()` and MFA pages, house defaults; no story | NIST SP 800-63B-4; Carbon login | Stories only, with sign-in |
| Notifications panel | Filament's bell, house defaults | rules.md Notifications | A component, not a screen |
| Task list (setup checklist) | none; roadmap "Later" | GOV.UK complete multiple tasks (no date shown: "only for longer transactions … over several sessions") <https://design-system.service.gov.uk/patterns/complete-multiple-tasks/>; Carbon has no page | Later: GOV.UK is form-wording only under the policy, so it needs Carbon or another source before it's a rule |
| Confirmation page | none; Filament uses toasts and redirects | GOV.UK confirmation pages (outside form wording) | Not adopted: Filament's toast plus the View page cover it |

## Recommended first-release set

The seven staff screens every panel needs, each as a Screen story set (one story per owed state) built from the patterns that exist:

1. **List** (`tables-list-table`)
2. **Record view** (`records-view-page`)
3. **Create and edit form** (`forms-record-form`, with the wizard as its long variant, `forms-form-container.wizard`)
4. **Report** (`reports-sales`)
5. **Dashboard** (`dashboards-key-figures-and-charts`)
6. **Settings** (`forms-settings-page`)
7. **Sign-in** (Filament's auth pages, house defaults)

Error pages already ship and serve as the forbidden and error states of all seven. Outside-user pages reuse the list, record and report screens in a second panel; their story waits for the owner's answers.

Work implied: new stories for loading, empty (both kinds), section error and forbidden on each screen; no new rules except a section-level error state from Carbon empty states (error management), which `docs/rules.md` doesn't state yet.

## Ask the owner

- Change the glossary to "a record or form screen has no empty state" (sign-in, forms, wizard, settings)?
- Outside panels: which outsiders first (suppliers, customers, both), what each sees and does (view orders, confirm, download statements), and how they get an account (invited by staff, or registering)?
- Is a wizard its own Screen, or stays a variant of the form screen?

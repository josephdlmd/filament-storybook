# Screen states: which stories are screens, and what each state must hold

Research for issue #8 (map #3). Checked 2026-10-19 against commit `512f8a0`. Read-only look at the factory repo (`~/Projects/laravel-ai-software-factory-starter`).

## 1. Which stories are screens

`Stories::kind()` picks the canvas from the return type (`src/Stories.php:82-93`): Column → column, Action → action, Table → table, Schema → schema, anything else (a field, `View`, `Callout`) its own kind. Only a `Table`, `Schema` or whole-page `View` can be a screen. Everything returning a field, column, `Action`, `Callout`, `Section`, `Repeater`, `Fieldset` or `Flex` is a part.

**Whole screens** (a page of their own):

| Story | Method(s) | Kind |
|---|---|---|
| `tables-list-table` | `default`, `rowActions`, `dateRange` (`src/Stories/ListTableStories.php:42,93,119`) | List page |
| `BulkActionStories` | `default` (`:43`) | List page |
| `ArchiveStories` | `default` (`:45`) | List page |
| `InviteUserStories` | `list` (`:79`) | List page (Users) |
| `StockStories` | `default` (`:33`) | List page |
| `CollectionsReportStories`, `SalesReportStories`, `PurchasesReportStories` (`default`, `payables`), `ReceivablesAgeingStories`, `CustomerWithholdingStories` `default`, `SupplierWithholdingStories` | `:41/43/122/41/41/41` | Report page |
| `DashboardStories` | `default`, `twoSeries`, `chart`, `ageing` (`:32-59`) | Dashboard |
| `RecordViewPageStories` | `default` (`:57`) | View page |
| `RecordFormStories` | `default` (`:28`) | Create/Edit page |
| `SettingsPageStories` | `default` (`:37`) | Settings page |
| `FormContainerStories` | `wizard` (`:127`) | Create page as wizard |
| `ErrorPageStories` | `default` 404, `forbidden` 403, `expired` 419, `serverError` 500, `maintenance` 503 (`:21-49`) | Error page |

**Screen sections** (a table or schema that sits on another page, usually a View page): `RecordHistoryStories:24`, `RecordNotesStories:34`, `RelatedRecordsStories:36,59`, `StockStories` `lowStock:78` (dashboard) and `movements:101`, `CustomerWithholdingStories` `awaiting:114`, `RecordLockedStories:42`, `PaperReceiptStories:46`, `DocumentTotalsStories:53`. `DownloadPdfStories` views (`:63-190`) are printed documents, not screens.

**Parts** (everything else): fields (`MoneyInput`, `TinInput`, `Date`, `Choice`, …), columns (`MoneyColumn`, `StatusBadge`, `DocumentLifecycle::status`, …), actions (`Approval`, `Void`, `Payment`, `Notification`, …), `RecordDetails` (Section), `KeyFigures` (Flex), `CreditLimit` (Callout), `LineItems` (Repeater).

## 2. Which states each shows today

A story is one method, and every story renders one state: the populated one, from `records()` or the in-memory sample database (`src/Livewire/StoryCanvas.php:67,88-89`; `src/Stories/Documents/SampleDatabase.php`). No screen has a variant per state.

| Screen | Loading | Empty | Populated | Error | Forbidden |
|---|---|---|---|---|---|
| List pages (ListTable, BulkAction, Archive, InviteUser list, Stock) | House default only: every table gets `loadingSkeleton()` (`src/StorybookPlugin.php:444-447`); no story shows it | Declared on `ListTable default` only (`ListTableStories.php:83-85`, heading + description + action); not rendered, sample rows exist | Yes | No | Partly: `authorize()` / `authorizeIndividualRecords()` on actions (`BulkActionStories.php:71-108`, `ArchiveStories.php:73-109`, `InviteUserStories.php:48,110,123`) hide actions or report skipped rows; no page-level state |
| Reports | Skeleton by house default | Not declared | Yes | No | No |
| Dashboard | Filament's lazy widget skeleton card (rule, `docs/rules.md:163`) | Rule only (`docs/rules.md:188`), no story | Yes | No | No |
| View page, Record form, Settings, Wizard | No | n/a (one record) | Yes | Not as a story; `storybook-audit` renders a real Create page "with errors" (`src/Mcp/AuditPages.php:328-362`) | No |
| Sections: RecordNotes, Stock lowStock, CustomerWithholding awaiting | Skeleton by house default | Declared (`RecordNotesStories.php:75-76`, `StockStories.php:96-97`, `CustomerWithholdingStories.php:137-138`); not rendered, sample rows match | Yes | No | No |
| Error pages | – | – | – | Yes: 404, 419, 500, 503 (`ErrorPageStories.php:21-49`) | Yes: 403 (`:28-31`) |

So: **populated** is shown everywhere; **empty** is written on 4 tables and shown on none; **loading** exists as a house default (table skeleton) and Filament's widget skeleton, shown by no story; **error** and **forbidden** exist only as whole-app error pages and toasts (`NotificationStories::notSaved`, `:50`), not as states of a given screen. Related checks already in place: `storybook-test` warns when a record argument is non-nullable, which stops an empty list rendering (`src/Mcp/TestStories.php:181`); `storybook-audit` warns when a list's empty state gives no next step (`src/Mcp/AuditPages.php:570-571`) and while Laravel's own error pages show (`:284-287`).

## 3. What each state must contain (sources under the rules policy)

**Loading.** Carbon loading pattern (updated Aug 11, 2026, https://carbondesignsystem.com/patterns/loading-pattern/): skeleton states are "simplified versions of components used on an initial page load", "should only appear for only a few seconds", and only "on container-based components like tiles and structured lists"; a spinner signals "a user action is processing"; "If a process will take more than a moment or two to complete, use a progress indicator"; more than a few minutes, "consider including a notification"; "A screen reader should notify a user if an application is loading, busy, gets stuck, or if a process fails." WCAG 2.2 SC 4.1.3 Status Messages (Understanding page updated 11 May 2026, https://www.w3.org/WAI/WCAG22/Understanding/status-messages.html): busy/progress must be announced without taking focus ("The screen reader announces 'application busy'"). Already in `docs/rules.md:163`. → Must contain: the screen's skeleton (not a spinner) for data containers, announced as loading.

**Empty.** Carbon empty states pattern (updated Aug 11, 2026, https://carbondesignsystem.com/patterns/empty-states-pattern/): three types, "No data", "User action" (no search results, a finished process) and "Error management". Anatomy: optional image; title "A short and concise explanation"; body "Explain clearly the next action to populate the space"; optional primary action and optional secondary link. Never a dead end. Already `docs/rules.md:73` (two empty states: nothing yet / nothing matches) and `:153` (search with no results says so and suggests a next step; count announced per WCAG 4.1.3). → Must contain: short title, a body naming the next step, at most one action; a separate "no results" variant for search/filter.

**Populated.** No source sets content beyond the component's own guidance (data table, structured list); the existing patterns are the standard. No new rule.

**Error.** Carbon empty states, error type: "Error messages should be expressed in plain language (no codes)"; always a path to a solution. Carbon notifications pattern (updated Aug 12, 2026, https://carbondesignsystem.com/patterns/notification-pattern/): "Do not leave users without next steps"; inline notifications "Be descriptive and give users clear next steps"; toasts for system messages that don't belong to one part of the UI. GOV.UK "There is a problem with the service" pages (no date shown, https://design-system.service.gov.uk/patterns/problem-with-the-service-pages/): H1 "Sorry, there is a problem with the service", "Try again later", say what happened to the user's answers, no "500" or "bad request", no red text; keep entered information so the user can resume. WCAG 4.1.3: error status ("5 errors on page") announced. Already `docs/rules.md:100,139,233`. → Must contain: what failed in plain words, what to do next, what happened to entered data, announced; code small or absent. Note: a screen whose data failed to load (Carbon's "error management empty state") is a state no story or rule covers yet; today only the whole-page 500 does.

**Forbidden.** Carbon empty states (error type): say "The user does not have permission to view the data" and suggest steps to request access. Already `docs/rules.md:233` (403 says who to ask). Carbon disabled states (cited in `ApprovalStories`/`DocumentLifecycleStories` descriptions): hide what the user may not do. GOV.UK has no access-denied pattern. → Must contain: plain statement of no access, who to ask, a way out; for a part of a screen, the action is hidden rather than shown disabled.

## 4. Naming against ScreenSpec

Factory examples list `"interaction_states": ["loading", "empty", "populated", "error", "forbidden"]` (`examples/screens/leave-requests.json:11`, `examples/screens/asset-detail.json:11`). The validator requires only four: `const REQUIRED_STATES = ['loading', 'empty', 'populated', 'error'];` (`scripts/lib/validate.mjs:5`, checked at `:58-59`). ADR-007 names all five as required Storybook variants.

Mismatches:

1. **`forbidden` is required by ADR-007 and the examples, not by `validate.mjs`.** One of them must change.
2. **`empty` is one state in ScreenSpec, two in the rules** (nothing yet vs. no results, `docs/rules.md:73`; Carbon's "no data" vs "user action"). A list screen needs both, or ScreenSpec's `empty` must be defined as the first only.
3. **`error` is two things here:** a whole-page error (the 500/404/419/503 error pages, shared by every screen) and a screen-level failure (Carbon's error-management empty state, or a form "with errors" as `storybook-audit` names it, `AuditPages.php:355`). ScreenSpec doesn't say which.
4. **`forbidden` likewise** is the shared 403 page today, not a per-screen variant; per-action permission is "hidden", which isn't a screen state.
5. **Story method names differ:** `ErrorPageStories::forbidden` and `serverError` vs ScreenSpec `forbidden` / `error`; no story method is named `loading`, `empty` or `populated` (`default` is the populated one). A variant convention (method names or a `states()` map) is undecided.
6. **Not every screen has every state:** a single-record page (View, form, settings) has no "empty"; an error page has only itself. ScreenSpec makes all five (four) unconditional.

## Open questions for the map

- Should state variants be story methods (`empty()`, `loading()`, …) on each screen class, or one shared rendering that forces a state on any `Table`/`Schema` story (empty records, skeleton, 403/500 wrapper)?
- Do `error` and `forbidden` mean per-screen variants or a link to the shared error pages?
- Does `empty` split into `empty` and `no-results` in ScreenSpec?

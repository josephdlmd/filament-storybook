# Filament coverage

Every component Filament 5 ships, checked against `docs/rules.md`, the patterns in `src/Stories`, the house defaults, the compact CSS and the checks in `storybook-test` and `storybook-audit`, on 2026-10-11. `docs/carbon-coverage.md` asks whether we follow each Carbon page; this asks the other way: whether an agent using any Filament component finds guidance for it.

Status: **Pattern** (a story shows it), **Rule** (a rule covers it by name or by what it does), **General** (no rule of its own, but general rules apply, named in the row), **Gap** (no guidance), **Not applicable** (the policy rules it out, or it isn't for business screens).

## Form fields

| Component | Status | Guidance |
|---|---|---|
| `TextInput` | Pattern | `forms-record-form`, `forms-tin`, `forms-contact-details`, `forms-percentage`, `forms-money-input`, `forms-quantity`, `forms-unit-cost`, `forms-settings-page`; house defaults (numeric as text, no asterisk, browser validation off); house script (units named); audit (email and phone fields) |
| `Textarea` | Pattern | `forms-record-form`; house CSS (vertical resize) |
| `Select` | Pattern | `forms-choice.manyOptions`, `forms-record-picker`; audit (searchable relationship) |
| `MorphToSelect` | General | the select rules (`forms-choice`, `forms-record-picker`) |
| `Radio` | Pattern | `forms-choice`; audit and test (stacked, required or "None") |
| `Checkbox` | Pattern | `forms-yes-or-no` |
| `CheckboxList` | Rule | choices rule; compact CSS (4px stacks); audit (stacked) |
| `Toggle` | Rule | only for settings that apply at once; audit fails a saved toggle |
| `ToggleButtons` | General | the choice rules; house default (required message) |
| `DatePicker` | Pattern | `forms-date` (typeable, min and max, width, range) |
| `DateTimePicker` | Rule | typeable; audit (`native(false)`); a time shows its zone |
| `TimePicker` | Rule | AM and PM in capitals; a time shows its zone |
| `FileUpload` | Pattern | `forms-file-upload` (limits, GOV.UK errors); audit (types and size); no multiple upload in a modal; house default (drop zone wording in sentence case) |
| `Repeater` (and its `TableColumn`) | Pattern | `forms-line-items`, `forms-document-totals`; audit (reorder by buttons, header matches label); compact CSS |
| `Slider` | Rule | not for amounts, dates or wide ranges; typeable |
| `Hidden` | Rule | engineering rule: set trusted values on the server |
| `Placeholder` | Rule | deprecated: use `TextEntry` |
| `ModalTableSelect`, `TableSelect` | Rule | engineering rule (restrict records in `relationship()`); never inside a modal form |
| `TagsInput` | Rule | only for labels the user makes up; known choices are a multiple select |
| `KeyValue` | General | no source says when free-form keys beat fixed fields; Filament names each input |
| `RichEditor`, `MarkdownEditor` | Rule | not for now: their toolbars can't be reached by keyboard (WCAG 2.1.1); a `Textarea` instead; audit |
| `ColorPicker` | Rule | the value shows as text beside the swatch |
| `Builder` (and its `Block`) | Not applicable | content blocks for pages, not business records |
| `CodeEditor` | Not applicable | a developer tool |
| `OneTimeCodeInput` | Rule | Filament's own multi-factor screens, under the sign-in rules (NIST, GOV.UK security codes); house defaults (two-factor wording, email when turned on or off, password rule) |
| `ViewField` | Not applicable | native first: patterns use native components; a custom component only for a gap (AGENTS.md) |

## Read-only entries

| Component | Status | Guidance |
|---|---|---|
| `TextEntry` | Pattern | `records-details`, `records-view-page`, `records-paper-receipt`, `forms-document-totals`; read-only rules; house script (copyable) |
| `RepeatableEntry` (and its `TableColumn`) | Pattern | `records-view-page` (read-only tables); compact CSS |
| `IconEntry` | Rule | an icon alone only for yes or no; otherwise a badge with its word |
| `ImageEntry` | Rule | empty alt text beside text that names it; `->alt()` when it carries information |
| `ColorEntry` | Rule | the value shows as text beside the swatch |
| `KeyValueEntry` | Rule | column titles of one or two words |
| `CodeEntry` | Not applicable | a developer tool |
| `ViewEntry` | Not applicable | native first: patterns use native components; a custom component only for a gap (AGENTS.md) |

## Table columns

| Component | Status | Guidance |
|---|---|---|
| `TextColumn` | Pattern | `tables-list-table`, `tables-money-column`, `tables-status-badge`; audit (money aligned, Title Case, tooltips) |
| `ToggleColumn` | Rule | the only toggle; house default (tick when on) |
| `IconColumn` | Rule | an icon alone only for yes or no; otherwise a badge with its word |
| `ImageColumn` | Rule | empty alt text beside text that names it; `->alt()` when it carries information |
| `ColorColumn` | Rule | the value shows as text beside the swatch |
| `TextInputColumn`, `SelectColumn`, `CheckboxColumn` | Rule | row editing in a slide-over; editable cells named after their row (house script) |
| `ColumnGroup` | Rule | one level deep |
| `Split`, `Stack`, `Panel`, layout `Grid` and `contentGrid()` (a table laid out as rows of stacked values or as cards) | General | the table rules; they drop the column titles, so a list people compare across columns stays a table with headers, and Filament already stacks a table on a narrow screen (WCAG 1.4.10) |
| layout `View` | Not applicable | native first: patterns use native components; a custom component only for a gap (AGENTS.md) |
| `ViewColumn` | Not applicable | native first: patterns use native components; a custom component only for a gap (AGENTS.md) |
| `Sum`, `Average`, `Count`, `Range`, `Values` (summarizers) | Pattern | `reports-sales` and the other reports (a totals row adding every row the filters keep); house CSS (4.5:1 values) |

## Table filters

| Component | Status | Guidance |
|---|---|---|
| `SelectFilter`, `TernaryFilter` | Pattern | `tables-list-table` (open above the table with `FiltersLayout::AboveContent`, applied as they change, kept for the session; never the collapsible layout); house default ("All" as a yes-or-no filter's empty choice); test and audit (the collapsible layout) |
| `TrashedFilter` | Pattern | `actions-archive` (labelled "Archived"); `actions-delete.restorable` |
| `QueryBuilder` (and its `Constraint`s: `TextConstraint`, `NumberConstraint`, `DateConstraint`, `BooleanConstraint`, `SelectConstraint`, `RelationshipConstraint`) | Rule | applies on an explicit action; the closed filter shows the count and a clear option |
| `Filter` (a filter with its own form) | Rule | the filter rules: open above the table, applied as they change, a way to clear them all; its fields follow the form patterns |

## Layout and schema components

| Component | Status | Guidance |
|---|---|---|
| `Section` | Pattern | used throughout; collapsible rule; audit and test |
| `Grid`, `Group` | Pattern | `records-view-page` (halves and quarters), `forms-document-totals`; one-column rule |
| `Flex`, `FusedGroup` | General | one-column rule: side by side only when the fields belong together |
| `Fieldset` | Pattern | `forms-date.range`, `forms-contact-details.address` |
| `Tabs` (and each `Tab`) | Rule | tabs rules; audit (related lists not in tabs) |
| `Cluster` | Rule | the menu rules: a cluster is one menu item whose pages are its own navigation, tabs at the top (house default) |
| `Wizard` (and each `Step`) | Pattern | `forms-form-container.wizard`; test (step names) |
| `Callout` | Pattern | `feedback-notifications.inline`; test (info or warning only) |
| `EmptyState` | General | the empty-state rule |
| `Text` | Rule | helper text and descriptions; type table |
| `Livewire` | Pattern | `records-view-page` (embedded related lists) |
| `EmbeddedTable` | General | the table rules |
| `Actions` | General | the button rules |
| `Image` | Rule | alt text as for image columns and entries |
| `Icon` | General | decorative beside text; an icon-only control needs a name |
| `UnorderedList` | General | writing rules |
| `Html`, `View` | Not applicable | native first: patterns use native components; a custom component only for a gap (AGENTS.md) |

## Actions

| Component | Status | Guidance |
|---|---|---|
| `Action` | Pattern | many; button, dialog and label rules; house defaults |
| `ActionGroup` | Rule | menu rules; house defaults (tooltip, chevron, offset); audit |
| `CreateAction`, `EditAction`, `ViewAction` | Rule | label table; house defaults (titles, Close button) |
| `DeleteAction`, `ForceDeleteAction` | Pattern | `actions-delete`, `actions-archive` (force delete for unused records); house defaults; audit |
| `RestoreAction`, `ReplicateAction` | Pattern, Rule | `actions-archive` (Restore); label table (Restore; Copy, a house default) |
| `AttachAction`, `DetachAction`, `AssociateAction`, `DissociateAction` | Pattern | `records-related-records`; house defaults (Add, Remove) |
| `BulkAction`, `BulkActionGroup`, `DeleteBulkAction`, `ForceDeleteBulkAction`, `RestoreBulkAction`, `DetachBulkAction`, `DissociateBulkAction` | Pattern | `tables-bulk-actions`; engineering rule (`authorizeIndividualRecords()`); house defaults (headings, outlined delete, checkbox names); audit (a `BulkActionGroup` menu) |
| `ImportAction`, `ExportAction`, `ExportBulkAction` | Pattern | `tables-import-and-export`, `tables-bulk-actions` (Export selected); house defaults |
| `SelectAction` | General | the choice rules |

## Widgets and charts

| Component | Status | Guidance |
|---|---|---|
| `StatsOverviewWidget` | Pattern | `dashboards-key-figures-and-charts` (`OrderStats`); audit (polling) |
| `ChartWidget`, bar and line | Pattern | `OrdersPerMonthChart`, `CashFlowChart`; audit (polling, legends, colours) |
| Pie and doughnut charts | Rule | slices labelled with their share |
| Scatter and bubble charts | Rule | a correlation; a bubble's size is a third measure |
| Radar and polar area charts | General | no source covers them, so there is no rule |
| `TableWidget` | General | the table and widget rules |

## Notifications

| Component | Status | Guidance |
|---|---|---|
| `Notification` (toast) | Pattern | `feedback-notifications`; house defaults (errors persist) |
| Database notifications | Rule | not sent twice while unread; long work ends with one; the panel (house defaults: empty wording, full-width items) |

## Deprecated, base and internal classes

| Component | Status | Guidance |
|---|---|---|
| `BadgeColumn`, `TagsColumn` | Not applicable | deprecated: `TextColumn` with `->badge()` (`tables-status-badge`) |
| `BooleanColumn` | Not applicable | deprecated: `IconColumn` with `->boolean()` (the icon rule) |
| `MultiSelect`, `MultiSelectFilter` | Not applicable | deprecated: `Select` or `SelectFilter` with `->multiple()` |
| `RelationshipRepeater` | Not applicable | deprecated: `Repeater` with `->relationship()` (`forms-line-items`) |
| `ButtonAction`, `IconButtonAction` | Not applicable | deprecated: `Action` with `->button()` or `->iconButton()` |
| `Field`, `Entry`, `Column`, `Component`, `Summarizer`, `BaseFilter`, `BaseFileUpload`, `LivewireField`, `EmbeddedSchema`, `Form`, `RenderHook`, `Indicator`, `ActionName`, `ActionsServiceProvider` | Not applicable | base classes and plumbing, not used on their own |

## Gaps

None. The ten gaps found on 2026-10-11 were closed the same day with rules from Carbon's tag, dropdown, structured list, text toolbar, status indicators, forms, filtering and simple charts pages, W3C WAI and WCAG. Two findings came out of it:

- **Filament's rich-text and Markdown editors fail WCAG 2.1.1:** none of their toolbar buttons can be reached by keyboard (0 of 20 and 0 of 13, checked in a browser). The rules keep them out until Filament fixes them, and `storybook-audit` warns.
- **No source says** when free-form key-value pairs beat fixed fields, when to edit in a table cell, when to offer an advanced filter, or how to use radar and polar-area charts; those have no rule.

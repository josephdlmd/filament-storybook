# Filament coverage

Every component Filament 5 ships, checked against `docs/rules.md`, the patterns in `src/Stories`, the house defaults, `compact()` and the checks in `storybook-test` and `storybook-audit`, on 2026-10-11. `docs/carbon-coverage.md` asks whether we follow each Carbon page; this asks the other way: whether an agent using any Filament component finds guidance for it.

Status: **Pattern** (a story shows it), **Rule** (a rule covers it by name or by what it does), **General** (no rule of its own, but general rules apply, named in the row), **Gap** (no guidance), **Not applicable** (the policy rules it out, or it isn't for business screens). A gap's number refers to the list at the end.

## Form fields

| Component | Status | Guidance |
|---|---|---|
| `TextInput` | Pattern | `forms-record-form`, `forms-tin`, `forms-money-input`, `forms-quantity`, `forms-unit-cost`; house defaults (numeric as text, no asterisk, browser validation off) |
| `Textarea` | Pattern | `forms-record-form`; house CSS (vertical resize) |
| `Select` | Pattern | `forms-choice.manyOptions`, `forms-record-picker`; audit (searchable relationship) |
| `MorphToSelect` | General | the select rules (`forms-choice`, `forms-record-picker`) |
| `Radio` | Pattern | `forms-choice`; audit and test (stacked, required or "None") |
| `Checkbox` | Pattern | `forms-yes-or-no` |
| `CheckboxList` | Rule | choices rule; compact CSS (4px stacks); audit (stacked) |
| `Toggle` | Rule | only for settings that apply at once; audit warns on a saved toggle |
| `ToggleButtons` | General | the choice rules; house default (required message) |
| `DatePicker` | Pattern | `forms-date` (typeable, min and max, width, range) |
| `DateTimePicker` | Rule | typeable; audit (`native(false)`); a time shows its zone |
| `TimePicker` | Rule | AM and PM in capitals; a time shows its zone |
| `FileUpload` | Pattern | `forms-file-upload` (limits, GOV.UK errors); audit (types and size); no multiple upload in a modal |
| `Repeater` | Pattern | `forms-line-items`, `forms-document-totals`; audit (reorder by buttons, header matches label); compact CSS |
| `Slider` | Rule | not for amounts, dates or wide ranges; typeable |
| `Hidden` | Rule | engineering rule: set trusted values on the server |
| `Placeholder` | Rule | deprecated: use `TextEntry` |
| `ModalTableSelect`, `TableSelect` | Rule | engineering rule (restrict records in `relationship()`); never inside a modal form |
| `TagsInput` | Gap | 1 |
| `KeyValue` | Gap | 2 |
| `RichEditor`, `MarkdownEditor` | Gap | 3 |
| `ColorPicker` | Gap | 4 |
| `Builder` | Not applicable | content blocks for pages, not business records |
| `CodeEditor` | Not applicable | a developer tool |
| `OneTimeCodeInput` | Rule | Filament's own multi-factor screens, under the sign-in rules (NIST) |
| `ViewField` | Not applicable | patterns use native components only |

## Read-only entries

| Component | Status | Guidance |
|---|---|---|
| `TextEntry` | Pattern | `records-details`, `records-view-page`, `records-paper-receipt`, `forms-document-totals`; read-only rules; house script (copyable) |
| `RepeatableEntry` | Pattern | `records-view-page` (read-only tables); compact CSS |
| `IconEntry` | Gap | 5 |
| `ImageEntry` | Gap | 6 |
| `ColorEntry` | Gap | 4 |
| `KeyValueEntry` | Gap | 2 |
| `CodeEntry` | Not applicable | a developer tool |
| `ViewEntry` | Not applicable | patterns use native components only |

## Table columns

| Component | Status | Guidance |
|---|---|---|
| `TextColumn` | Pattern | `tables-list-table`, `tables-money-column`, `tables-status-badge`; audit (money aligned, Title Case, tooltips) |
| `ToggleColumn` | Rule | the only toggle; house default (tick when on) |
| `IconColumn` | Gap | 5 |
| `ImageColumn` | Gap | 6 |
| `ColorColumn` | Gap | 4 |
| `TextInputColumn`, `SelectColumn`, `CheckboxColumn` | Gap | 7 |
| `ColumnGroup` | Gap | 8 |
| `ViewColumn` | Not applicable | patterns use native components only |

## Table filters

| Component | Status | Guidance |
|---|---|---|
| `SelectFilter`, `TernaryFilter` | Pattern | `tables-list-table` (above the table, kept for the session); filter defaults rule |
| `TrashedFilter` | Pattern | `actions-delete.restorable` ("filtered by Deleted records") |
| `QueryBuilder` | Gap | 9 |

## Layout and schema components

| Component | Status | Guidance |
|---|---|---|
| `Section` | Pattern | used throughout; collapsible rule; audit and test |
| `Grid`, `Group` | Pattern | `records-view-page` (halves and quarters), `forms-document-totals`; one-column rule |
| `Flex`, `FusedGroup` | General | one-column rule: side by side only when the fields belong together |
| `Fieldset` | Pattern | `forms-date.range` |
| `Tabs` | Rule | tabs rules; audit (related lists not in tabs) |
| `Wizard` | Pattern | `forms-form-container.wizard`; test (step names) |
| `Callout` | Pattern | `feedback-notifications.inline`; test (info or warning only) |
| `EmptyState` | General | the empty-state rule |
| `Text` | Rule | helper text and descriptions; type table |
| `Livewire` | Pattern | `records-view-page` (embedded related lists) |
| `EmbeddedTable` | General | the table rules |
| `Actions` | General | the button rules |
| `Image` | Gap | 6 |
| `Icon` | General | decorative beside text; an icon-only control needs a name |
| `UnorderedList` | General | writing rules |
| `Html`, `View` | Not applicable | patterns use native components only |

## Actions

| Component | Status | Guidance |
|---|---|---|
| `Action` | Pattern | many; button, dialog and label rules; house defaults |
| `ActionGroup` | Rule | menu rules; house defaults (tooltip, chevron, offset); audit |
| `CreateAction`, `EditAction`, `ViewAction` | Rule | label table; house defaults (titles, Close button) |
| `DeleteAction`, `ForceDeleteAction` | Pattern | `actions-delete`; house defaults; audit |
| `RestoreAction`, `ReplicateAction` | Rule | label table (Restore; Copy, a house default) |
| `AttachAction`, `DetachAction`, `AssociateAction`, `DissociateAction` | Pattern | `records-related-records`; house defaults (Add, Remove) |
| `BulkAction`, `BulkActionGroup`, bulk delete, restore and detach | Rule | bulk changes rule; engineering rule (`authorizeIndividualRecords()`); house defaults (headings) |
| `ImportAction`, `ExportAction`, `ExportBulkAction` | Pattern | `tables-import-and-export`; house defaults |
| `SelectAction` | General | the choice rules |

## Widgets and charts

| Component | Status | Guidance |
|---|---|---|
| `StatsOverviewWidget` | Pattern | `dashboards-key-figures-and-charts` (`OrderStats`); audit (polling) |
| `ChartWidget`, bar and line | Pattern | `OrdersPerMonthChart`, `CashFlowChart`; audit (polling, legends, colours) |
| Pie and doughnut charts | Rule | slices labelled with their share |
| Bubble, scatter, polar area and radar charts | Gap | 10 |
| `TableWidget` | General | the table and widget rules |

## Notifications

| Component | Status | Guidance |
|---|---|---|
| `Notification` (toast) | Pattern | `feedback-notifications`; house defaults (errors persist) |
| Database notifications | Rule | not sent twice while unread; long work ends with one |

## Gaps

1. **`TagsInput`:** free-text tags. Nothing says when to use them instead of a select of fixed options, or how long a tag may be.
2. **`KeyValue`, `KeyValueEntry`:** pairs of keys and values. No rule; Carbon's structured list would apply.
3. **`RichEditor`, `MarkdownEditor`:** formatted text. No pattern; Carbon's text toolbar page applies if one is used.
4. **`ColorPicker`, `ColorColumn`, `ColorEntry`:** a colour shown as a swatch. Filament names it for screen readers, but on screen the colour alone carries it; its value or name should show too (WCAG 1.4.1).
5. **`IconColumn`, `IconEntry`:** a state shown as an icon (a tick or a cross). Filament already gives screen readers text ("Yes", "No", or the value's label), and the icon's shape, not only its colour, differs. No rule says when an icon beats a word or a status badge.
6. **`ImageColumn`, `ImageEntry`, `Image`:** no rule on alternative text (WCAG 1.1.1): an image that carries meaning describes it, a decorative one is empty.
7. **`TextInputColumn`, `SelectColumn`, `CheckboxColumn`:** editing in a table cell. No rule on when to edit in the table rather than a form, and their names are the column's label on every row, as row actions were before the house script.
8. **`ColumnGroup`:** headings over groups of columns. No rule.
9. **`QueryBuilder`:** the advanced filter. No rule on when to offer it beside the ordinary filters.
10. **Bubble, scatter, polar area and radar charts:** the chart-purpose rule covers lines, bars, stacked bars and pies only.

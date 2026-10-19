# Which of the accounting app's tighter spacings does Carbon support? (#24)

Checked 2026-10-19 against the accounting app's `origin/main` (976e238): `resources/css/filament/admin/theme.css` and `.ai/rules/ux-exceptions.md` (E11, E18). Sources are the live carbondesignsystem.com pages, with their "Last updated" dates; the archived `carbon-design-system/carbon-website` snapshot only where the live pages are silent, marked *(archived)*. Filament defaults are from `vendor/filament` in this repo; the Compact theme's (`filament/compact-theme`, which both `compact()` apps use) were read in the accounting app's installed vendor copy without changing it.

## Pages read

| Page | Updated | What it says on these questions |
|---|---|---|
| [Spacing](https://www.carbondesignsystem.com/building-blocks/foundations/spacing/overview) | Sep 30, 2026 | Scale: 2, 4, 8, 12, 16, 24, 32, 40, 48, 64… ("$spacing-01" to "$spacing-13"). "deviating from the spacing scales should be avoided whenever possible." "The top level headers have more space surrounding them giving them focus and prominence." "Sections of a UI are allowed to be dense, but the whole page should not be crowded." |
| [UI shell header, specifications](https://www.carbondesignsystem.com/building-blocks/core/components/ui-shell-header/specifications) and [guidelines](https://www.carbondesignsystem.com/building-blocks/core/components/ui-shell-header/guidelines) | Oct 2, 2026 | Header height 48 / 3. Nothing on the space between the header and the page title or content. |
| Page header | none | No live page (`/components/page-header/*` 404s after the 2026-09-30 move; not in the building-blocks sitemap) and none in the archived snapshot (no `page-header` path in the repo tree). |
| [Data table, specifications](https://www.carbondesignsystem.com/building-blocks/core/components/data-table/specifications) | Sep 30, 2026 | Columns: "Column widths can vary by content and only require a minimum spacing between columns." Spacing between columns: padding 16 / 1, `$spacing-05`. Table header: padding left, padding right 16 / 1; margin-top 16 / 1; margin-bottom 24 / 1.5. No other horizontal padding for the extra small or small sizes. |
| [Data table, guidelines](https://www.carbondesignsystem.com/building-blocks/core/components/data-table/guidelines) | Sep 30, 2026 | Nothing on inline editing or inputs in cells. Sizes 24/32/40/48/64; header row "should always match the row size". |
| [Text input, specifications](https://www.carbondesignsystem.com/building-blocks/core/components/text-input/specifications) | Sep 30, 2026 | Field text: padding-left, padding-right 16 / 1, `$spacing-05`, with no per-size values (xs 24, sm 32, md 40, lg 48 are heights only). Label margin-bottom 8 / 0.5. |
| [Forms](https://www.carbondesignsystem.com/building-blocks/core/patterns/forms) | Aug 11, 2026 | 32px between fields on a page; "In contained forms, such as side panels or modals, designers can revert to 24px or even 16px between inputs"; 48px above buttons. Nothing on a form's heading, tables or input padding. |
| [Structured list, specifications](https://www.carbondesignsystem.com/building-blocks/core/components/structured-list/specifications) | Sep 30, 2026 | Row height 60 / 3.75 default, 36 / 2.25 condensed. Row text padding-right 16 / 1, `$spacing-05`. |
| [2x Grid, guidelines](https://www.carbondesignsystem.com/building-blocks/foundations/2x-grid/guidelines) | Sep 25, 2026 | Gutters 32 (wide), 16 (narrow), 1 (condensed); "Fixed components with labels such as input fields and dropdowns MUST use the wide gutter mode." Nothing on title-to-content spacing or table columns. |

## Verdicts

### E11: 16px from the top bar to the page heading, and from the heading to the content: **not covered**

`.fi-page-header-main-ctn { padding-block: 1rem; row-gap: 1rem; }`. Filament's own value is 32px (`py-8 gap-y-8`, `page.css`); the issue's 24px is the Compact theme's (`sm:gap-y-6 sm:py-6`). `compact()` doesn't change it.

- No live or archived Carbon page sets this distance: there is no page header page, and the UI shell header, spacing and 2x Grid pages are silent on it.
- 16px is on the scale (`$spacing-05`), so it doesn't break "deviating from the spacing scales should be avoided".
- The spacing page's principle weighs mildly against it: "The top level headers have more space surrounding them giving them focus and prominence". At 16px the page heading gets the same space as two fields (16px apart in a compact app).
- The nearest analogue is a data table's own title: 16px above (`margin-top`) and 24px below (`margin-bottom`). That supports 16px above a heading but not 16px below it. It's a table's title, not a page's.

No source sets the number, so under the rules policy it can't be a package rule. It stays an app exception.

### E18: line-item cells 4px apart, 6px inside each box: **contradicted**

`.fi-numbered-lines.fi-fo-table-repeater … :is(th, td) { padding-inline: 4px; }` and `:is(input.fi-input, .fi-input-wrp-prefix) { padding-inline: 6px; }`. Each cell pads 4px on both sides, so neighbouring boxes are actually **8px** apart. Filament's `px-3` puts them 24px apart, and the Compact theme's `--compact-theme-cell-px: 0.625rem` 20px. `compact()` doesn't change it. The input's own padding is 12px in Filament and 10px in the Compact theme (`px-2.5`).

- Between the boxes: Carbon's data table asks for a "minimum spacing between columns" of 16 / 1 (`$spacing-05`). 8px is half that.
- Inside a box: Carbon's text input puts "padding-left, padding-right 16 / 1" on the field text at every size. 6px is below that and also off the scale, which goes 4 then 8.
- Carbon has no page on editable cells, so it gives no exception for inputs inside a table. This repo's rules already treat line items as a data table, which is how the 16px minimum applies.

### Document sheet columns "8px apart": **supported, since they are really 16px apart**

`.fi-invoice-sheet .fi-in-table-repeatable … :is(th, td)` puts 8px (`padding-inline-start/end: 0.5rem`) on each inner side of a cell, so neighbouring columns are **16px** apart. The comment's "8px" is per side; Filament's "24px" is 12 + 12 (20px, 10 + 10, under the Compact theme). The sheet's outer edges have 0.

- 16px is exactly Carbon's minimum spacing between columns (data table, 16 / 1, `$spacing-05`). The structured list's row text padding-right (16 / 1) agrees.
- So this setting matches Carbon, and `compact()` could adopt it for read-only tables (`.fi-in-table-repeatable`, today 20px under the Compact theme), and for line items (`.fi-fo-table-repeater`), where 16px is the floor E18 goes below.

## Anything else in `theme.css` tighter than `compact()` today

| Setting (theme.css) | Effect | Carbon |
|---|---|---|
| `:is(.fi-invoice-sheet, .fi-minor-details) .fi-in-table-repeatable td { padding: 0.5rem 0.75rem }` | Read-only rows 36px (8 + 20 + 8), against `compact()`'s 40px (10px block) | Contradicts the data table sizes (24/32/40/48/64), which `docs/rules.md` applies to read-only tables. It matches the structured list's condensed row (36 / 2.25), which the rules turned down for the View page's details column. Only supported if these tables count as structured lists. |
| `.fi-invoice-details > .fi-sc { row-gap: 0.375rem }` (6px) | Details 6px apart | Off the scale. `compact()` already turns the Compact theme's 6px gaps into 8px for this reason (Carbon spacing). |
| `.fi-document-title > .fi-sc { row-gap: 0.125rem; } > :last-child { margin-top: 0.375rem }` | Title block: 2px, then 8px (2 + 6) to the status | 2px is on the scale (`$spacing-01`). The 6px margin is off it, but the gap it makes (8px) is on it. Not covered: no title-block spec. |
| `.fi-record-pager` gap 12px with −4px margins | Arrows' targets 4px apart | On the scale; app-specific, not a `compact()` candidate. |
| `.fi-attached-copy-*` gaps of 8px and 4px | Copy column | On the scale; app-specific. |
| `.fi-beside-input { padding-top: calc(1.25rem + 6px) }` | Lines a checkbox up with an input under a 20px label "and its 6px gap" | Not a spacing choice, but a mismatch: `compact()` makes the label gap 8px (`.fi-fo-field { row-gap: 0.5rem }`, Carbon text input `margin-bottom 8 / 0.5`), so the offset is 2px short if `compact()` is on. |

`.fi-main { padding-bottom: 4rem }` and `.fi-header { min-height: 2.75rem }` are looser than Filament, not tighter. The 12px type in `.fi-minor-details` is type, not spacing, so it's left out here.

## Summary

| Setting | Verdict | Basis |
|---|---|---|
| E11 heading 16px from the top bar and the content | Not covered | No Carbon page header spec; spacing page favours more space round top-level headers |
| E18 line items 4px cells (8px apart), 6px inside the box | Contradicted | Data table: 16px minimum between columns; text input: 16px field padding at every size |
| Document sheet columns (16px apart) | Supported | Data table: 16px minimum between columns |
| Sheet read-only rows 36px | Contradicted (data table sizes); matches structured list condensed | Data table and structured list specifications |
| 6px gaps (details, title block) | Contradicted (off the scale) | Spacing |

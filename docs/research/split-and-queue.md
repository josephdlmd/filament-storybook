# Split workspace and approval queue: what are they based on?

Research for issue #9, 2026-10-19. The question: what basis do two screens have under the rules policy in `AGENTS.md`? A **split workspace** is a list with the selected record shown beside it. An **approval queue** is the documents waiting for a decision. The research also asks which native Filament 5 features build them, and whether either needs a custom component.

Filament was checked at v5.10.1 in `vendor/filament`. Web pages were read on 2026-10-19, and each one is cited with the "last updated" date it shows.

## Short answer

- **Split workspace.** Carbon supports it, though only weakly. Carbon has **no list-detail pattern**. Its core pattern index (13 pages) has none, and `/patterns/list-detail/` returns 404. What Carbon has is the *slide-in side panel*. It is used "when a user needs to reference the page along with the panel information", and it "re-sizes the page content, and reduces the number of columns in the grid" (2x Grid usage). Carbon's forms page backs it as the container "for repeated requests for user input which require the user needs to reference the affected information". The detailed side-panel guidance is Carbon for IBM Products' and is IBMers-only. W3C supplies the keyboard, focus and selection basis: WCAG 2.2 (1.4.10, 2.4.3, 2.4.11, 3.2.2) and WAI-ARIA 1.2 `aria-current`. Filament can build it natively. Two small things are missing: exposing which row is the selected one (`aria-current`) needs the house script, and the narrow-screen behaviour needs a decision.
- **Approval queue.** **No permitted source has a queue or approval pattern.** Carbon has none (the rules already say so under Approvals), and neither do USWDS, GOV.UK or APG. NN/g turned up nothing from 2022 on. So the queue is a **list table** under rules we already have: Carbon data table, filtering, content switcher (`getTabs()`), status indicators, batch actions, and the existing Approvals rules and business standards. Filament builds it natively, with no gap. Anything else (sort order, ageing, assignment, bulk approve with one reason) has no source, so it is either not a rule or a business fact for the owner.
- **No custom component is needed for either screen.** The only things beyond native Filament are a few lines of house script (`aria-current` on the selected row) and decisions only the owner can make.

## 1. Split workspace

### What Carbon says

| Topic | Carbon | Source |
|---|---|---|
| List-detail as a pattern | None. Core patterns are common actions, dialogs, disabled states, disclosures, empty states, filtering, forms, global header, loading, login, notifications, read-only states and text toolbar. `carbondesignsystem.com/patterns/list-detail/` is 404. | [Patterns overview](https://carbondesignsystem.com/patterns/overview/) (Aug 12, 2026) |
| Side panel beside the page | "Slide-in side panels come into play when a user needs to reference the page along with the panel information"; it "re-sizes the page content, and reduces the number of columns in the grid" (a *grid influencer*). Sizes and best practice are left to Carbon for IBM Products, "IBMers only". | [2x Grid usage](https://carbondesignsystem.com/elements/2x-grid/usage/) (Sep 25, 2026) |
| Side panel for repeated work | Side panel: "For repeated requests for user input which require the user needs to reference the affected information", for example "Calibrating row information in a data table"; "more than five inputs"; "Do not hide information in accordions or tabs". | [Forms pattern](https://carbondesignsystem.com/building-blocks/core/patterns/forms) (Aug 11, 2026) |
| Side panel width | "Fixed containers, like a side panel, maintain a static width, cannot be collapsed". | Fluid styles *(archived)*, `carbon-website/src/pages/patterns/fluid-styles/index.mdx` |
| UI shell right panel | This is for system-level actions tied to a header icon, not for record details. It "always float[s] over page content". It doesn't apply here. | [UI shell right panel](https://carbondesignsystem.com/components/ui-shell-right-panel/usage/) (Oct 2, 2026) |
| Expandable rows (the other way to show details in a list) | "Use the expanded section for supplementary information or data that needs additional query time." When it feels cramped, use "a dedicated page" instead (the archived text adds "side panel"). Expandable rows "operate in the same manner as accordions"; tab order runs through the expansion icons, then to a link inside an expanded row. | [Data table usage](https://carbondesignsystem.com/components/data-table/usage/) (Sep 30, 2026); [Data table accessibility](https://carbondesignsystem.com/components/data-table/accessibility/) (Sep 30, 2026) |
| Selecting one row | "The data table also supports a single-select radio button control, limiting the user to selecting only one row at a time." Carbon doesn't say what clicking a row does. | Data table usage (Sep 30, 2026) |
| Hiding details | "Do not hide important information inside of a disclosure that the user may need in order to complete a task". Carbon has no guidance on showing a selected item's details. | [Disclosures](https://carbondesignsystem.com/building-blocks/core/patterns/disclosures) (Aug 11, 2026) |

**What Carbon supports:** a record shown in a panel *beside* the list, which shrinks the list rather than covering it, for work the user repeats while referring to the list. Carbon publishes no widths, keyboard model or selection model for it. Our own rule on record-page grids (halves and quarters, never thirds; Carbon 2x grid) gives the split proportions: 8 + 8 or 12 + 4 of 16 columns.

### What W3C says (focus, keyboard, selection)

| Topic | Source | What it means here |
|---|---|---|
| The selected row is marked | WAI-ARIA 1.2 `aria-current`: "used when an element within a set of related elements is visually styled to indicate it is the current item in the set"; "Authors SHOULD only mark one element … as current"; not "a substitute for aria-selected in widgets where aria-selected has the same meaning". [W3C Rec, 6 Jun 2023](https://www.w3.org/TR/wai-aria-1.2/#aria-current) | The list is an ordinary table, not a widget, so the row being shown gets `aria-current="true"`, along with its visual highlight. |
| Selecting a row only shows the record | WCAG 2.2 3.2.2 On Input: "Changing the setting of any user interface component does not automatically cause a change of context…"; changes of context include focus, but "A change of content is not always a change of context" (an expanding outline or a tab control). [WCAG 2.2, 12 Dec 2024](https://www.w3.org/TR/WCAG22/) | Opening a row fills the detail pane and **leaves focus on the row**. Moving focus into the pane on every click would be a change of context. Moving the arrow keys or focus alone never changes the record. |
| No listbox or grid | APG listbox: it "does not provide an accessible way to present a list of interactive elements, such as links, buttons, or checkboxes"; "Avoiding very long option names". APG grid: "particularly useful if the tabular information is editable or interactive", with its own arrow-key model. [APG listbox](https://www.w3.org/WAI/ARIA/apg/patterns/listbox/), [APG grid](https://www.w3.org/WAI/ARIA/apg/patterns/grid/) (no page date; © 2026) | Rows have checkboxes and actions, so a listbox is ruled out, and a grid would be a custom widget that Filament doesn't make. Keep Filament's table, where each control is in the tab order (Carbon data table accessibility says the same). |
| Resizable split | APG window splitter: "a moveable separator between two sections, or panes", role `separator`. The page says the pattern is under review. [APG window splitter](https://www.w3.org/WAI/ARIA/apg/patterns/windowsplitter/) | Only if the panes can be resized. Nothing asks for that, so it isn't needed. |
| Narrow screens | WCAG 1.4.10 Reflow: nothing lost at 320 CSS px. (WCAG 2.2) | The two panes stack, or on a narrow screen the row opens the record's own page. |
| Sticky pane and focus | WCAG 2.4.11 Focus Not Obscured (already a rule) | A sticky detail pane never covers the focused row. |
| Order | WCAG 2.4.3 Focus Order | The list comes before the detail pane in the DOM and the tab order. |

### What Filament 5 has (verified in `vendor/filament` v5.10.1)

Filament has two native ways to build it. Both are already covered by `docs/filament-coverage.md` rows: page `content()` schema, `Grid`, `Livewire`/`EmbeddedTable`, `ViewAction`.

1. **A slide-over beside the list:** `ViewAction::make()->slideOver()` with an infolist, opened from the row by `$table->recordAction('view')`. It is native and already the rule for "a task repeated while looking at the information it changes" (`forms-form-container`). But it is a **modal dialog** (`role="dialog" aria-modal="true"`, focus trapped, `support/resources/views/components/modal/index.blade.php:148`), so the list is covered and can't be used. That is not Carbon's side panel, which *resizes* the page. `->modalClickThrough()` makes it non-modal (`aria-modal="false"`, no focus trap, and it **does not restore focus**: `shouldRestoreFocus` is false when click-through, line 155). It still floats over the list, which risks WCAG 2.4.11. Use it for the *edit-while-looking* case; it is not the split workspace.
2. **A true split (Carbon's grid influencer):** a custom page (`Page` + `HasTable` + `HasSchemas`) whose `content()` is `Grid::make(['lg' => 2])` (or 16 columns at 12 + 4). It contains `EmbeddedTable::make()` and a `Section` of entries bound to `$this->selectedRecord`.
   - Selecting: `$table->recordAction('selectRecord')`. When the name isn't a table action, Filament calls the Livewire method `selectRecord('{key}')` (`tables/resources/views/index.blade.php:1343-1347`). Each cell then renders as a `<button type="button">` (`tables/src/Columns/Column.php:163-176`). A `$url` (`recordUrl`) renders cells as `<a>`, and the house script already removes empty cell links from the tab order (`resources/js/house.js:97-104`).
   - Highlight: `$table->recordClasses(fn ($record) => $record->is($this->selectedRecord) ? 'fi-selected' : null)`.
   - Narrow screens: `Grid` stacks below `lg` on its own. A `->recordUrl()` that applies only on narrow screens isn't native, so the narrow behaviour is a decision (see below).
   - The URL keeps the selection: `#[Url] public ?string $record` keeps it on reload and back.

### Gaps (none needs a custom component)

- **`aria-current` on the selected row.** Filament has `recordClasses()` but no API for row *attributes*, so a screen reader can't tell which row is open. The fix is a few lines in `resources/js/house.js`: set `aria-current="true"` on a row with the selected class. It isn't a component.
- **Cell buttons are named by their cell.** With `recordAction`, every cell is a button named by its text ("₱12,000.00"), which is a WCAG 2.4.4/2.4.6 concern. The same applies to `recordUrl` today, and the house script already handles empty cells. An alternative is a single "Open" row action named after the row, which the house script already names "Open SUP-0001". Decide this when the pattern is built.
- **Toggle button named by the record key.** The collapsible-row toggle (only in a `Split`/`Stack` content layout) is labelled with the record key (`filament-tables::table.actions.toggle_record_content.label`, `index.blade.php:1415`). That breaks our rule that controls are named after the row, never the id. Expandable rows also drop column headers (filament-coverage row for `Split`/`Stack`/`Panel`), so they aren't a split workspace. Only use them for Carbon's "supplementary information".

## 2. Approval queue

### What the sources say

- **Carbon has no approval or queue pattern.** It isn't in the pattern index (above), and `docs/rules.md` ("Approvals") already records that "Carbon has no approval pattern". The archived snapshot only uses "approve" as an action label (already cited).
- **USWDS, GOV.UK and APG** have nothing on queues or approvals. MOJ is out (owner, 2026-10-12). NN/g gave no 2022+ article on approval workflows or list-detail in this search. Vendor pages (Salesforce, Dynamics, others) aren't permitted sources.
- So under the policy, **the queue has no rules of its own.** It is a list table of documents in the For approval status, governed by rules we already have:

| Question | Existing basis | Where |
|---|---|---|
| What the queue is | A List page whose tabs are Carbon's content switcher (`getTabs()`: "For approval" first, then e.g. "All"), or a `SelectFilter` on status kept for the session | rules.md tabs rule; `tables-list-table`; Carbon tabs and filtering |
| What a row shows | The document's facts and its status badge (For approval is blue, "workflow progress") | Carbon status indicators; `tables-status-badge` |
| Who sees it and who decides | Approve and Reject only while For approval and only to a user the policy allows; otherwise hidden | Carbon disabled states; `actions-approve-and-reject` |
| How a decision is made | Approve is the primary button and posts the document, in a danger dialog that states the consequence. Reject is gray, needs a reason and returns the document to Draft. A toast names the outcome; the sender is notified of a rejection | Approvals rules; Business standards 2026-10-11 and 2026-10-13 |
| Deciding several at once | Batch actions in the toolbar once rows are ticked. An irreversible bulk change (Approve posts) confirms in a danger dialog that says the consequence; records the user may not decide are skipped and counted; `authorizeIndividualRecords()` | Carbon data table batch actions; NN/g bulk actions (Mar 2025); rules.md bulk rules |
| Deciding while reading the document | The split workspace above, or the View page with previous and next (already covered, 2026-10-18) | Section 1; roadmap "From the accounting app" |
| A count in the menu | **Not allowed:** menu items "carry no record counts" (number badges only for global actions) | rules.md menu rule; Carbon status indicators |
| Telling the approver | Not a UI rule. Whether a document sent for approval notifies the approvers is a business fact (only the rejection notice is decided) | Business standards |

- **No source sets these, so they aren't rules (owner decides if they're business facts):** default sort (oldest first?), showing how long a document has waited, assigning to a named approver or claiming, more than one approval step, and bulk Reject with one reason shared by all.

### What Filament 5 has

Everything is native: `getTabs()` with `Tab::make()->modifyQueryUsing(...)` and `->badge()`. The tab badge is a count *on the page*, not in the menu; whether that is allowed is not settled, since the rules ban counts only in the menu. It also uses `SelectFilter`, the existing `Action`s from `ApprovalStories` as record actions, `BulkAction` with `->requiresConfirmation()` and `->authorizeIndividualRecords()`, and `TextColumn::badge()` for the status. There is no gap and no custom component.

## Decisions for the owner (before a pattern is built)

1. Should the split workspace be its own pattern (a custom page with `EmbeddedTable` and a detail `Section`), or is the existing slide-over enough? Carbon's side panel resizes the page, but Filament's slide-over covers it.
2. Split proportions: 8 + 8 or 12 + 4 of 16. Thirds are out under the existing grid rule.
3. On a narrow screen, should the panes stack, or should the row open the record's page?
4. Queue business facts: sort order, ageing shown or not, a bulk Approve, a bulk Reject with one reason, telling approvers when something arrives.

## Not checked

- Mobbin (it can only confirm) and FilamentExamples (code reference only). Neither can change the basis above.
- The Carbon for IBM Products side-panel guidance, which is IBMers-only.
- The code paths above weren't run in a browser; they are verified by reading the source only.

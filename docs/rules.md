# UI rules and their basis

Every rule the patterns, `storybook-instructions`, `storybook-test` and `storybook-audit` apply is listed here with its evidence. Re-checked against live sources on 2026-10-10.

**Base: the Carbon Design System.** IBM's system for enterprise products, whose guidance covers the data tables, filters, forms, dialogs and notifications these apps are made of. A rule that follows Carbon needs no further evidence.
- **Form wording and errors follow GOV.UK,** whose user research on hints, placeholders and error messages is the strongest available.
- **WCAG 2.2 AA always wins;** it is the accessibility floor.
- **Where Carbon is silent,** a rule needs its own current evidence: W3C WAI, USWDS, NIST SP 800-63B-4 for sign-in, or research from about 2022 on, such as NN/g. Where we depart from Carbon, the rule says why (see "Where we differ from Carbon").
- **Real-app references (Mobbin) and preference never justify a rule.** Formats follow the app's locale, not GOV.UK's UK style guide.
- Carbon's guidance on usage and behaviour is adopted, not its look; apps keep their Filament theme.
- **Business facts come from the owner,** such as the TIN format and decimal places. They don't need research. They're listed under "Business standards" and built into the patterns.

Dates in brackets are the "updated" dates the pages showed when checked.

## Forms

- **Labels are visible and above their fields; no `hiddenLabel()`.** W3C WAI forms tutorial (May 2024); GOV.UK text input. *Audit.*
- **No `placeholder()`; an example goes in `helperText()` as "For example, …".** USWDS text input; GOV.UK text input; GOV.UK style guide ("for example", not "e.g."). *Audit, test.*
- **Helper text is one short line without a full stop, only where the label isn't enough, and has no links.** GOV.UK text input and question pages; Carbon forms (Aug 2026): help text "should not run longer than the input area". *Audit.*
- **Each input is about as wide as its answer.** USWDS text input; GOV.UK text input. *Pattern `forms-money-input`.*
- **A value with a known shape (TIN) is one field.** It accepts spaces, dashes and dots, and stores one format. No input masks: USWDS's own mask failed WCAG 2.1 AA in its tests. GOV.UK National Insurance number pattern; USWDS input mask. *Pattern `forms-tin`.*
- **One choice: radios when there are few options; a searchable select only for a long list.** GOV.UK select (a "last resort", from user research); GOV.UK radios. *Pattern `forms-choice`.*
- **Several choices: a checkbox list for a short known list; a searchable multiple select for a long or related list.** Carbon checkbox and dropdown (Sep 2026).
- **A yes/no saved with the form is a `Checkbox` with a positive label. A `Toggle` is only for something that applies at once (a `ToggleColumn`).** Carbon checkbox and toggle (Sep 2026); NN/g checkboxes (Jun 2024). *Audit, pattern `forms-yes-or-no`.*
- **Defaults only where most people would pick that value anyway; never pre-select a judgement or consent; checkboxes start unchecked.** Carbon forms (Aug 2026); GOV.UK radios; NN/g 2024 (pre-checked opt-ins are deceptive).
- **Dates can always be typed: the native `DatePicker`, never `native(false)`, with `minDate()`/`maxDate()` where there's a range.** Filament's JavaScript picker has a read-only text box. USWDS date picker (Aug 2026); GOV.UK dates pattern. *Audit, pattern `forms-date`.*
- **Uploads state their limits up front:** accepted types, maximum size, and a helper text such as "PDF or JPG, up to 10 MB". USWDS file input (Aug 2026); Carbon file uploader; Filament docs (always set accepted types). *Audit, pattern `forms-file-upload`.*
- **Mark the smaller group:** when most fields are required, label the optional ones "(optional)"; when most are optional, label the required ones "(required)". Carbon forms (Aug 2026); GOV.UK question pages agree for the usual case. *Pattern `forms-record-form`.*
- **Error messages say what to do, using the field's label:** "Enter the TIN", "Select payment terms". No "invalid", "is required", "please" or "sorry", and what the user typed stays. GOV.UK error message (Jan 2024); NN/g error messages (May 2023); WCAG 3.3.1 and 3.3.3. *Audit, house defaults.*
- **Validate on submit or when the user leaves a field, never while they type.** GOV.UK validation; NN/g hostile errors (Oct 2022); Carbon forms (on blur).
- **A short form may disable its primary button until its requirements are met; a long form never does.** Every form disables it while submitting. Hide what the user may not do. When an action is temporarily unavailable, keep it visible with a visible reason; a tooltip alone can't be reached by keyboard. Carbon forms and disabled states (Aug 2026).

### Line items

Pattern `forms-line-items`. Checked against the live sources on 2026-10-10.

- **Line items are a table repeater: one column per field, and every field keeps its label.** On a wide screen the column header is the visible label and the field's own label stays for screen readers; on a narrow screen the rows stack and each label shows above its field. No `hiddenLabel()` on a cell field, because the header disappears when rows stack. Filament removes the cell labels with `display: none` once headers show, which leaves every field unnamed, so the house CSS hides them on screen only. WCAG 1.3.1, 3.3.2 (updated Mar 2026) and 4.1.2; W3C WAI forms tutorial (May 2024): a label may be hidden on screen when the context makes the purpose clear, "though it still needs to be provided within the code". *House CSS; test and audit (axe), audit (`hiddenLabel()`).*
- **Each column header is its field's label, word for word,** so what screen-reader and voice-control users hear matches what is on screen. A field without a column isn't shown at all. WCAG 2.5.3 (Apr 2026). *Audit.*
- **A worked-out value in a row, such as the line total, is read-only text, not a disabled field.** Disabled fields aren't read by screen readers and needn't meet contrast. It is worked out again on the server when saving, never taken from the form. Carbon text input (Sep 2026); see also "Hidden fields" under engineering rules.
- **Numbers in a line are right-aligned, with each column about as wide as its answer.** GOV.UK and USWDS tables; USWDS and GOV.UK text input.
- **Lines that can be reordered also move with buttons, not only by dragging;** if the order doesn't matter, reordering is off. Filament's repeaters reorder by dragging alone by default. WCAG 2.5.7 (Aug 2026). *Audit.*
- **A row with more fields than fit across the form stacks instead:** each line is a small numbered form with its labels on screen ("Line 1"). Carbon data table (Sep 2026): not for "a more complex display of the data or interactions"; Carbon forms (Aug 2026) has no inline-editing guidance yet. No source sets a field count, so there is no number. *Pattern `forms-line-items.longRow`.*

## Feedback and dialogs

- **Confirm only what can't be undone.** The heading names the record or the count, the description says the consequence, and the confirm button repeats the verb. Carbon dialogs (Aug 2026); WCAG 3.3.4 (Sep 2025). *Audit, pattern `actions-delete`.*
- **Danger emphasis matches the action's place:** a delete among other actions is low-emphasis danger (outlined or a link); the button that confirms it is solid danger. Danger buttons always have a text label. Carbon button (Sep 2026). *Audit, house defaults.*
- **Buttons are verbs ("Delete order"), never OK, Yes or Done.** Carbon modal and button (Sep 2026); NN/g confirmation dialogs.
- **Every save says what happened, naming the record.** An undo or other action in a notification keeps it open until dismissed, and so does an error. Carbon notifications (Aug 2026); WCAG 4.1.3 (May 2026); GOV.UK notification banner. *House defaults (errors stay open).*
- **Notification colour follows the outcome:** success for saved, warning for "saved, but follow up", danger for "nothing saved". Carbon notifications; USWDS alert.
- **Two empty states.** With nothing yet, say what goes here and offer the next step. When nothing matches a search or filter, say so and offer to clear it. Carbon empty states (Aug 2026). *Audit, pattern `tables-list-table`.*
- **Sentence case for labels, headings and buttons; "select", not "click".** GOV.UK style guide; Carbon writing style (Sep 2026).
- **Form size picks its container.** A modal for fewer than about five inputs, a slide-over for more, and its own page for long or multi-step forms (wizards). Carbon forms (Aug 2026); USWDS modal.
- **One primary action per screen; the rest are gray or in "…".** Carbon button (Sep 2026); GOV.UK button. *Audit.*

## Tables and finding

- **A readable identifier comes first; the other columns follow in order of importance and are easy to hide.** NN/g data tables (Apr 2022). *Pattern `tables-list-table`.*
- **Numbers and money are right-aligned.** GOV.UK table; USWDS table. *Audit, pattern `tables-money-column`.*
- **One search box, searching the identifier.** Carbon search (Aug 2026); NN/g data tables. *Pattern `tables-list-table`.*
- **With more than one filter category, filters sit above the table, never in a dropdown.** They have an Apply button when results are slow and a way to clear them all. Carbon filtering (Aug 2026). *Audit, pattern `tables-list-table`.*
- **Lists remember their filters, search and sort.** Carbon search (Aug 2026). *Pattern `tables-list-table`.*
- **Key columns are sortable and there's a default sort.** NN/g data tables; USWDS table.
- **Row actions live in an overflow menu ("…"); when there are fewer than three, they stay inline.** The table toolbar holds up to five actions. Carbon data table (Sep 2026). *Audit.*
- **Numbered pages showing the current range and the total, never infinite scroll. Anything that refreshes itself can be paused.** GOV.UK pagination; Carbon pagination (Sep 2026); NN/g infinite scrolling (Sep 2022); WCAG 2.2.2.
- **Bulk changes: irreversible ones confirm with the count; reversible ones offer Undo.** WCAG 3.3.4; NN/g bulk actions (Mar 2025).
- **Working lists are tables, not card grids, unless the records are images.** GOV.UK table; NN/g data tables.
- **Truncated text has its full version one step away, by focus or a link.** WCAG 1.4.12 and 1.4.13.
- **Timestamps show in the user's time zone.** GOV.UK style guide; Carbon date picker.
- **Formats follow the app's locale:** currency symbol, thousands separators and a readable date, never ISO `2026-10-10` in the interface. The minus sign stays even when colour is used. WCAG 1.4.1; the app's locale.

## Layout and pages

- **Lists and record pages use the full width; long forms keep readable line lengths.** Carbon 2x grid (Sep 2026); GOV.UK layout.
- **Record pages: content in three quarters, a summary of facts in one quarter,** stacked below a large screen with the content first. The summary's actions sit on its heading row, and a missing value links to where it's added. The summary is not sticky: Filament has no setting for it, and a sticky column must never hide focus. Carbon 2x grid (Sep 2026: 16 columns, divided by two and by four; it advises against layouts in thirds); GOV.UK summary card and summary list; WCAG 1.4.10 and 2.4.11. *Patterns `records-view-page`, `records-details`.*
- **Related lists sit in the main column, most used first, not in a tab beside the details.** GOV.UK tabs; NN/g tabs (Aug 2024).
- **Tabs are for views of the same thing:** few, with short labels, in one row and never nested. Carbon tabs; NN/g tabs (Aug 2024).
- **Never collapse a section that holds a required field.** NN/g accordions (Jul 2023).
- **Deep pages keep a way back** (breadcrumbs or a back link). GOV.UK breadcrumbs; NN/g breadcrumbs.
- **At most one "needs action" banner per page, before the heading.** GOV.UK notification banner.

### Read-only and locked records

Patterns `records-details`, `records-view-page` and `records-locked`. Checked against the live sources on 2026-10-10.

- **A value the user may read but not change is read-only, never disabled.** Read-only text keeps 4.5:1 contrast, stays reachable by keyboard and is read by screen readers. A disabled control is skipped by screen readers and exempt from contrast, so it never holds something the user needs to read. Carbon read-only states (Aug 2026); WCAG 1.4.3 (Sep 2026: the exemption covers controls "not available for user interaction"); WAI-ARIA 1.2 `aria-readonly` (focusable, and copying is supported).
- **Information that can't be edited here is text, not a field.** A View page is an infolist of entries, never Filament's default disabled form. Carbon read-only states: "If the component does not have an enabled state, do not use a read-only state to display static information." *Audit.*
- **Disabled is only for a control that becomes available once the user does something on the same screen.** When it holds up the primary action, an inline notification says how to enable it. Hide what the user may never do. Carbon disabled states (Aug 2026).
- **Read-only fields look read-only:** no field background and a fainter border, with the text at full contrast. Filament's `readOnly()` changes nothing. Carbon read-only states. *House CSS.*
- **A locked record says why, and what to do instead,** in an inline notification: first in the main column of its View page, and at the top of a form that can still change part of it. Edit never vanishes without a reason. Carbon disabled states (the notification describes how to re-enable); Carbon notifications (inline, informational). *Pattern `records-locked`.*
- **In a partly locked form, the locked fields are read-only.** Choice fields (radios, selects, checkboxes) have no read-only state in Filament, so they show their value as text. Carbon read-only states (Locked; choice controls have a read-only state there). *Pattern `records-locked`.*
- **Which statuses lock a record is a business fact.** Each app sets its own, ideally on its status enum, and uses it in its own `app/Stories`; the shared pattern uses one generic condition on the record.
- **A count badge means waiting work, and is hidden at zero.** NN/g indicators (Jan 2024).
- **Error pages:** no "404" or "500" jargon; say whether work was saved and who to contact. GOV.UK page not found and problem with the service.

## Accessibility floor

- **WCAG 2.1 AA checks on every story and page.** axe-core, the engine behind Storybook's accessibility addon. *Test, audit.*
- **Targets at least 24 by 24 CSS pixels.** WCAG 2.5.8.
- **Nothing lost at 320 CSS pixels wide (400% zoom), or with increased text spacing.** WCAG 1.4.10 and 1.4.12.
- **Colour never alone:** gray not started, warning waiting, success done, danger critical, always with a word. WCAG 1.4.1; Carbon status indicators. *Pattern `tables-status-badge`.*
- **Contrast: 4.5:1 for text; 3:1 for status colours and chart series against the background and each other.** WCAG 1.4.3 and 1.4.11.
- **Focus is always visible and never fully hidden under the sticky top bar or a sticky aside.** WCAG 2.4.7 and 2.4.11.
- **Tooltips only repeat what's visible elsewhere.** WCAG 1.4.13.
- **Every icon-only action keeps a `label()`, which becomes its accessible name.** WCAG 4.1.2. *Test, audit.*

## Sign-in

- **Passwords: at least 15 characters, at least 64 allowed, no composition rules, checked against breached passwords; pasting and revealing are allowed.** NIST SP 800-63B-4 (Aug 2025); WCAG 3.3.8.
- **Multi-factor sign-in with an authenticator app and recovery codes. Never email codes as a factor.** NIST SP 800-63B-4: email "SHALL NOT be used for out-of-band authentication".
- **The owner is told when a sign-in method or recovery is added or changed.** NIST SP 800-63B-4.

## Business standards (from the owner)

Approved by the owner on 2026-10-10.

- **TIN:** the 9-digit TIN plus a 3-digit branch code, stored as `000-000-000-000`, with `000` for the head office. *Pattern `forms-tin`.*
- **Amounts** (prices, totals, payments, balances, VAT and withholding): pesos and centavos, **at most 2 decimal places, checked on the server.** The peso has 100 centavos (PHP; 2 minor units). *Pattern `forms-money-input`.*
- **Unit cost** (weighted-average, landed or derived costs): **up to 4 decimal places,** so costs worked out by division don't drift by centavos. *Pattern `forms-unit-cost`.*
- **Quantity:** **up to 3 decimal places,** for weights, volumes and lengths. *Pattern `forms-quantity`.*
- **Round once, at the end:** a line total is quantity × unit price, rounded to 2 decimals once, as `forms-line-items` shows. VAT is worked out once per invoice for each VAT class (12%, zero-rated, exempt), on that class's total, then rounded to 2 decimals. This matches BIR's requirement to show VAT as a separate item and to break out taxable, zero-rated and exempt sales (RR 7-2024).
- **Rounding is half up, away from zero for negatives:** ₱50.025 becomes ₱50.03, and a credit of −₱50.025 becomes −₱50.03, so a return mirrors its sale. This is how people check figures by hand and how Excel's `ROUND` and PostgreSQL's `round()` work. Not banker's rounding (half to even). *Pattern `forms-line-items`.*
- **One method everywhere:** the invoice, the journal entry and the tax returns use the same amounts and the same rounding, so the books always agree with the invoices.
- **Never floating-point money:** money is stored in decimal columns (for example `decimal(15, 2)`, and `decimal(15, 4)` for unit cost) and computed with decimal arithmetic, never PHP floats.

**Decided by the owner (2026-10-10):**
1. **Half up, away from zero for negatives,** as above. No BIR rule setting the method was found; ask the CPA in the same review as item 2.
2. **VAT is worked out per invoice for each VAT class,** not per line. A CPA review is still worthwhile; if the CPA prefers per line, change it here and in the patterns, in one release.
3. **None of the owner's companies is under BIR's e-invoicing (EIS) mandate at present** (RR 11-2025, RMC 98-2026). If one comes under it, EIS's technical specification sets field precision and overrides this section.

## Engineering rules (Filament facts, not UX)

- `Hidden` fields can be edited by the user; set trusted values on the server. (Filament docs)
- Restrict which records a `ModalTableSelect` offers in `relationship(modifyQueryUsing:)`; the table filter alone isn't a security boundary. (Filament docs)
- Bulk actions check permission per record with `authorizeIndividualRecords()`. (Filament docs)
- No deprecated components: `Select->multiple()`, not `MultiSelect`; `TextEntry`, not `Placeholder`. (Filament 5 source)
- A read-only field is still submitted, and its value can be changed in the browser. Keep locked values out of the save with `saved(false)` on the field or a group around it, and let the policy's `update()` refuse when nothing may change; Filament then hides Edit and refuses the Edit page. (Filament docs) *Audit.*
- A relation manager embedded with `Livewire::make()` skips Filament's `canViewForRecord()` check; keep it with `visible()`, as `records-view-page` shows. (Filament 5 source)
- In Filament, `EditAction::make()` anywhere on a resource page opens the Edit page, or the resource form in a modal when there is no Edit page, and is hidden when the policy refuses `update()`. (Filament 5 source)

## Where we differ from Carbon

- **No placeholder text at all.** Carbon allows it sparingly; GOV.UK and USWDS research shows it disappears, isn't read by some screen readers and is mistaken for a value, and form wording follows GOV.UK.
- **No input masks.** Carbon has no mask component; USWDS's own mask failed WCAG 2.1 AA, and WCAG wins.
- **Error messages say what to do ("Enter name").** Carbon's examples state the problem; form wording follows GOV.UK.

## Dropped

These rules had no current basis, or a current source contradicts them:

- **Numbers no source sets:**
  - 25 rows per page
  - at most 8 (or 6) columns
  - 3 to 5 figures
  - at most 5 tabs
  - 1/2/3 infolist columns
  - 3 type sizes
  - a pixel type scale
  - "RelationGroup from four"
  - "CheckboxList up to 15"
- **Input masks.** USWDS's own mask failed WCAG 2.1 AA.
- **Inline labels on forms,** including slide-overs and settings pages. This contradicts labels above fields.
- **Save button on the right.** GOV.UK aligns the primary button to the left of the form.
- **"—" for a missing value.** GOV.UK links to where it's added instead.
- **Two thirds and one third on record pages** (GOV.UK layout). Carbon's 2x grid advises against layouts in thirds, and GOV.UK covers form wording, not layout. Replaced by three quarters and one quarter.
- **Email codes as a sign-in fallback.** NIST forbids it.
- **Breadcrumbs off everywhere.** GOV.UK and NN/g keep them on deep pages.
- **A grey delete trigger** (GOV.UK). Carbon, our base, makes it low-emphasis danger instead.
- **Never disabling Submit** (GOV.UK). Carbon allows it on short forms.
- **Hiding tabs instead of disabling them** (GOV.UK). Carbon permits disabled tabs.
- **Capitalised glossary terms.** GOV.UK and Carbon both use sentence case.
- **Rules backed only by older research or by preference:**
  - one money formatter
  - always show `.00`
  - negatives never red
  - filters ordered general to specific
  - repeating tasks keep the form open
  - "points" for percentage differences
  - relative dates only as hints
  - the record-page "intents"
  - figures strip counts
  - list pages without a heading
  - a Ctrl+K search shortcut
  - lists with no visible heading
  - an "unsaved changes" indicator beside Save

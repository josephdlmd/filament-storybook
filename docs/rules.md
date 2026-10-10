# UI rules and their basis

Every rule the patterns, `storybook-instructions`, `storybook-test` and `storybook-audit` apply is listed here with its evidence. Re-checked against live sources on 2026-10-10.

**Policy.** A rule is kept only when current, maintained guidance supports it:
- WCAG 2.2 and the W3C WAI tutorials
- the GOV.UK Design System
- the U.S. Web Design System (USWDS v3.14)
- the Carbon Design System
- NIST SP 800-63B-4 for sign-in
- research published or reviewed from 2022 on, such as Nielsen Norman Group (NN/g)

Where those sources disagree, the rule follows the point they share and says so. House preferences, older research and common practice are not a basis. Mobbin (real apps) can confirm a rule or raise a question about it, but never justifies one. GOV.UK's style guide sets UK conventions for dates and numbers, so formats follow the app's locale instead.

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
- **Label optional fields "(optional)".** GOV.UK question pages and USWDS form agree. They disagree on the required-field asterisk, so there's no rule on that. *Pattern `forms-record-form`.*
- **Error messages say what to do, using the field's label:** "Enter the TIN", "Select payment terms". No "invalid", "is required", "please" or "sorry", and what the user typed stays. GOV.UK error message (Jan 2024); NN/g error messages (May 2023); WCAG 3.3.1 and 3.3.3. *Audit, house defaults.*
- **Validate on submit or when the user leaves a field, never while they type.** GOV.UK validation; NN/g hostile errors (Oct 2022); Carbon forms (on blur).
- **Don't disable Submit before it is pressed.** Hide what the user may not do. When an action is temporarily unavailable, keep it visible with a visible reason; a tooltip alone can't be reached by keyboard. GOV.UK button (Jul 2026); USWDS form; Carbon disabled states (Aug 2026).

## Feedback and dialogs

- **Confirm only what can't be undone.** The heading names the record or the count, the description says the consequence, and the confirm button repeats the verb. WCAG 3.3.4 (Sep 2025); Carbon dialogs (Aug 2026); USWDS modal; NN/g confirmation dialogs (reviewed Aug 2026). *Audit, pattern `actions-delete`.*
- **Red is for the confirming button only; the action that opens the confirmation is not red.** GOV.UK button (warning buttons need a confirmation step that uses "another button style for the initial action"). *Audit, house defaults.*
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
- **At most two row actions inline; the rest go in "…".** Carbon data table (Sep 2026); NN/g data tables. *Audit, pattern `tables-list-table`.*
- **Numbered pages showing the current range and the total, never infinite scroll. Anything that refreshes itself can be paused.** GOV.UK pagination; Carbon pagination (Sep 2026); NN/g infinite scrolling (Sep 2022); WCAG 2.2.2.
- **Bulk changes: irreversible ones confirm with the count; reversible ones offer Undo.** WCAG 3.3.4; NN/g bulk actions (Mar 2025).
- **Working lists are tables, not card grids, unless the records are images.** GOV.UK table; NN/g data tables.
- **Truncated text has its full version one step away, by focus or a link.** WCAG 1.4.12 and 1.4.13.
- **Timestamps show in the user's time zone.** GOV.UK style guide; Carbon date picker.
- **Formats follow the app's locale:** currency symbol, thousands separators and a readable date, never ISO `2026-10-10` in the interface. The minus sign stays even when colour is used. WCAG 1.4.1; the app's locale.

## Layout and pages

- **Lists and record pages use the full width; long forms keep readable line lengths.** Carbon 2x grid (Sep 2026); GOV.UK layout.
- **Record pages: content in two thirds, a summary of facts in one third.** The summary's actions sit on its heading row, and a missing value links to where it's added. The summary is sticky only if it fits the screen. GOV.UK layout and summary card; WCAG 2.4.11.
- **Related lists sit in the main column, most used first, not in a tab beside the details.** GOV.UK tabs; NN/g tabs (Aug 2024).
- **Tabs are for views of the same thing:** few, with short labels and in one row. They are never nested, and are hidden rather than disabled. NN/g tabs (Aug 2024); GOV.UK tabs.
- **Never collapse a section that holds a required field.** NN/g accordions (Jul 2023).
- **Deep pages keep a way back** (breadcrumbs or a back link). GOV.UK breadcrumbs; NN/g breadcrumbs.
- **At most one "needs action" banner per page, before the heading.** GOV.UK notification banner.
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

## Engineering rules (Filament facts, not UX)

- `Hidden` fields can be edited by the user; set trusted values on the server. (Filament docs)
- Restrict which records a `ModalTableSelect` offers in `relationship(modifyQueryUsing:)`; the table filter alone isn't a security boundary. (Filament docs)
- Bulk actions check permission per record with `authorizeIndividualRecords()`. (Filament docs)
- No deprecated components: `Select->multiple()`, not `MultiSelect`; `TextEntry`, not `Placeholder`. (Filament 5 source)

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
- **Email codes as a sign-in fallback.** NIST forbids it.
- **Breadcrumbs off everywhere.** GOV.UK and NN/g keep them on deep pages.
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

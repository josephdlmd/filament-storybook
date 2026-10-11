# UI rules and their basis

Every rule the patterns, `storybook-instructions`, `storybook-test` and `storybook-audit` apply is listed here with its evidence. Every rule was re-checked against the live sources on 2026-10-11.

**Base: the Carbon Design System.** IBM's system for enterprise products, whose guidance covers the data tables, filters, forms, dialogs and notifications these apps are made of. A rule that follows Carbon needs no further evidence.
- **Form wording and errors follow GOV.UK,** whose user research on hints, placeholders and error messages is the strongest available.
- **WCAG 2.2 AA always wins;** it is the accessibility floor.
- **Where Carbon is silent,** a rule needs its own current evidence: W3C WAI, USWDS, NIST SP 800-63B-4 for sign-in, or research from about 2022 on, such as NN/g. Where we depart from Carbon, the rule says why (see "Where we differ from Carbon").
- **Real-app references (Mobbin) and preference never justify a rule.** Formats follow the app's locale, not GOV.UK's UK style guide.
- Carbon's guidance on usage and behaviour is adopted, not its look; apps keep their Filament theme.
- **Business facts come from the owner,** such as the TIN format and decimal places. They don't need research. They're listed under "Business standards" and built into the patterns.

Dates in brackets are the "updated" dates the pages showed when checked. On 2026-09-30 Carbon moved to a new site; its content guidelines (writing style and action labels) were taken down and now point to IBM Style, which needs a sign-in. Rules cite the live component and pattern pages, which carry their own content rules, and cite the archived `carbon-website` snapshot (2026-09-30) only where no live page covers a rule.

## Forms

- **Labels are visible and above their fields; no `hiddenLabel()`.** In a line-item table the column header is the visible label (see Line items). Carbon forms (Aug 2026): "Top-aligned labels are Carbon's default" and "Every text input should have a descriptive and visible label". *Audit.*
- **No `placeholder()`; an example goes in `helperText()` as "For example, …".** USWDS text input (Aug 2026: "Avoid placeholder text"); GOV.UK text input (Jun 2026: no placeholder "for hints or examples"); hints worded "For example, …" in GOV.UK's dates and National Insurance number patterns and USWDS input mask. *Audit, test.*
- **Helper text is one short line without a full stop, only where the label isn't enough, and has no links.** Carbon forms (Aug 2026): "Only use helper text when truly necessary" and it "should not run longer than the input area"; GOV.UK text input (Jun 2026): "a single short sentence, without any full stops", and no links in hint text. *Audit.*
- **Each input is about as wide as its answer.** Carbon forms (Aug 2026): "The field widths should reflect the intended length of the content"; USWDS text input (Aug 2026). *Pattern `forms-money-input`.*
- **A value with a known shape (TIN) is one field, and accepts spaces, dashes and dots.** It is stored in the owner's format (see Business standards). USWDS text input (Aug 2026): "Avoid breaking numbers with distinct sections … into separate input fields"; W3C WAI forms tutorial: "Be forgiving of different input formats". **No input masks:** the one mask a qualifying source has tested, USWDS's, failed WCAG 2.1 AA (Apr 2025: recovering from an error is difficult), and WCAG wins. USWDS itself still offers masks for fixed formats. *Pattern `forms-tin`.*
- **One choice from up to five options: radios. More than five: a searchable select.** Carbon forms (Aug 2026): "When you have more than five options to present to the user, use a select list"; USWDS select (Aug 2026: a select is the "UI of last resort"). *Pattern `forms-choice`.*
- **Several choices from up to five options: a checkbox list. More than five: a multiple select, searchable when the list is long.** Carbon forms (Aug 2026: more than five options, "use a select list (combo box or multiselect)"); Carbon dropdown (Sep 2026: filtering for a long list).
- **A yes/no saved with the form is a `Checkbox` with a positive label. A `Toggle` is only for something that applies at once (a `ToggleColumn`).** Carbon checkbox and toggle (Sep 2026); NN/g checkboxes (Jun 2024). *Audit, pattern `forms-yes-or-no`.*
- **Defaults only where most people would pick that value anyway and a missed change causes no error. Checkboxes start unchecked; consent, legal and opt-in boxes are never pre-checked.** Carbon forms (Aug 2026) and checkbox (Sep 2026); NN/g checkboxes (Jun 2024: pre-checked opt-ins are "a deceptive pattern").
- **Dates can always be typed, never through a picker whose text box is read-only; set min and max dates where the range is limited.** In Filament that means the native `DatePicker`, never `native(false)`, whose JavaScript picker has a read-only text box. Carbon date picker (Sep 2026): "There should always be a simple way to enter dates in a text field" and "Use min and max dates"; USWDS date picker (Aug 2026): "Always allow a user to type in the date manually". *Audit, pattern `forms-date`.*
- **Uploads state their limits up front:** accepted types, maximum size, and a helper text such as "PDF or JPG, up to 10 MB". USWDS file input (Aug 2026); Carbon file uploader; Filament docs (always set accepted types). *Audit, pattern `forms-file-upload`.*
- **Mark the smaller group:** when most fields are required, label the optional ones "(optional)"; when most are optional, label the required ones "(required)". Carbon forms (Aug 2026). *Pattern `forms-record-form`.*
- **Error messages say what to do, using the field's label:** "Enter the TIN", "Select payment terms". No "invalid", "is required", "please" or "sorry", and what the user typed stays. GOV.UK error message (Jan 2024); NN/g error messages (May 2023); WCAG 3.3.1 and 3.3.3. *Audit, house defaults.*
- **Validate on submit, or when the user leaves a field they have changed; never while they type, and never flag an untouched empty field.** Carbon forms (Aug 2026: as soon as the field loses focus); USWDS text input (Aug 2026: only after the user "has interacted with a particular field"); NN/g hostile errors (Oct 2022); NN/g error messages (May 2023).
- **A short form may disable its primary button until its requirements are met; a long form never does.** Every form disables it while submitting. Hide what the user may not do. A temporarily unavailable action stays visible, with text saying why and how to make it available; a tooltip on a disabled button alone can't be reached by keyboard. Carbon forms and disabled states (Aug 2026); USWDS button (Oct 2024): "Explain why an action is unavailable and how to make it available".

### Line items

Pattern `forms-line-items`. Checked against the live sources on 2026-10-10.

- **Line items are a table repeater: one column per field, and every field keeps its label.** On a wide screen the column header is the visible label and the field's own label stays for screen readers; on a narrow screen the rows stack and each label shows above its field. No `hiddenLabel()` on a cell field, because the header disappears when rows stack. Filament removes the cell labels with `display: none` once headers show, which leaves every field unnamed, so the house CSS hides them on screen only. WCAG 1.3.1, 3.3.2 (updated Mar 2026) and 4.1.2; W3C WAI forms tutorial (May 2024): a label may be hidden on screen when the context makes the purpose clear, "though it still needs to be provided within the code". *House CSS; test and audit (axe), audit (`hiddenLabel()`).*
- **Each column header is its field's label, word for word,** so what screen-reader and voice-control users hear matches what is on screen. A field without a column isn't shown at all. WCAG 2.5.3 (Apr 2026). *Audit.*
- **A worked-out value in a row, such as the line total, is read-only text, not a disabled field.** Disabled fields aren't read by screen readers and needn't meet contrast. It is worked out again on the server when saving, never taken from the form. Carbon text input (Sep 2026); see also "Hidden fields" under engineering rules.
- **Numbers in a line are right-aligned, with each column about as wide as its answer.** USWDS table (Aug 2026); Carbon forms and USWDS text input (Aug 2026).
- **Lines that can be reordered also move with buttons, not only by dragging;** if the order doesn't matter, reordering is off. Filament's repeaters reorder by dragging alone by default. WCAG 2.5.7 (Aug 2026). *Audit.*
- **A line with more fields than fit without truncation is not a table row:** each line becomes a small form with its labels on screen. Carbon data table (Sep 2026): not for "a more complex display of the data or interactions", and not where information "can feel cramped or needs truncation". No source sets a field count or numbering. *Pattern `forms-line-items.longRow`.*

## Feedback and dialogs

- **Confirm destructive or irreversible actions in a danger dialog, and don't confirm routine ones.** The title repeats the label of the action that opened it, the description says the consequence (never Filament's "Are you sure you would like to do this?"): what else goes and that it can't be undone, or, for a soft delete that can be restored, how to get it back. The confirm button uses the same verb. Carbon dialogs (Aug 2026) and modal (Sep 2026: "use the button label for the modal title"); Carbon common actions (Aug 2026: low-impact deletes need no confirmation); WCAG 3.3.4 (changes to data are reversible, checked or confirmed). *Audit, pattern `actions-delete`.*
- **Danger emphasis matches the action's place:** a delete among other actions is low-emphasis danger (tertiary, which Filament calls outlined, or ghost, a link); the button that confirms it is solid danger. Danger buttons always have a text label. Carbon button (Sep 2026). *Audit, house defaults.*
- **Buttons say what they do with a verb, and its object where it helps ("Delete order", "New supplier"); never OK, Yes, Done, Submit or Confirm,** except a message that only needs acknowledging. A common action (Add, Edit, Delete, Cancel) may stand alone; a noun alone is never a label. A modal's title and confirm button repeat the label of the button that opened it. Carbon button (Sep 2026: "use the {verb} + {noun} content formula on buttons except in the case of common actions"; "Do not use only a noun as a button label"); Carbon modal (Sep 2026: "Avoid vague or passive words, such as Done or OK"; "if the icon name is New user, use New user as the modal title, not Create user"); Carbon forms (Aug 2026: "Abstract terms like "Submit" give the user the impression that the form is generic"); USWDS button ("Lead with a verb"). *Audit, test, house defaults (the confirm button repeats the action's label, or is "Save" for an "Edit …" form; the New modal is titled "New …").*
- **Two empty states.** With nothing yet, say what goes here and offer the next step. When nothing matches a search or filter, say so and suggest adjusting them. The heading is short and, where it can be, positive ("No suppliers yet", "Start by adding suppliers"); the description says the next step. Carbon empty states (Aug 2026). *Audit, pattern `tables-list-table`.*
- **A modal with buttons (a form or a confirmation) closes only by its buttons, its × or Esc, never by a click outside it,** so a stray click loses nothing. A modal with nothing to submit, such as View, may also close by a click outside. Carbon modal (Sep 2026: transactional modals "are persistent until dismissed" by the primary action, Cancel, × or Esc; for a passive modal, "Clicking outside the passive modal area will automatically close the modal"). *House defaults.*
- **A form's size and use pick its container:** a short, infrequent task with fewer than five inputs, a modal; more than five inputs, or a task repeated while looking at the information it changes, a slide-over; a long, complex or multi-step task, its own page. Exactly five is left open. No tabs or collapsed sections in a modal or slide-over; a modal that would scroll a long way becomes a slide-over or a page. A modal form focuses its first field and stays open with the errors marked when validation fails; Filament does both. Carbon forms (Aug 2026: "Use a dialog form when dealing with less than five inputs", side panels "for repeated requests for user input which require the user needs to reference the affected information"); Carbon modal (Sep 2026: "short and non-frequent tasks"; focus; validation; max heights); Carbon dialogs (Aug 2026: "Don't recreate a full app or page in a dialog"; avoid accordions and tabs); USWDS modal (no complex forms in modals). *Pattern `forms-form-container`.*
- **A wizard is only for a linear task of three or more steps,** not for steps done in any order or whose number depends on earlier answers. Each step is named in one or two words saying what it does ("Set terms"), is checked before the next, and Back returns to review; the last button names the final action ("Create"). Filament's wizard does all but the names. Carbon progress indicator (Sep 2026: "three or more steps"; "When the process may be completed in any order" is a when-not-to-use; labels "in one or two words"); Carbon forms (Aug 2026: multistep forms let users "return to a previous step to review"); Carbon modal (Sep 2026: "the Next button label should change to reflect the final action"). *Pattern `forms-form-container.wizard`.*
- **One primary action per screen; the rest are gray or in "…".** Carbon button (Sep 2026). *Audit.*

### Approvals

Pattern `actions-approve-and-reject`. Checked against the live sources on 2026-10-11; Carbon has no approval pattern, so these rules apply its button, dialog and disabled-state guidance.

- **Approve and Reject show only while the record waits for a decision, and only to users who may decide** (a policy method); otherwise they are hidden, not disabled. Carbon disabled states (Aug 2026: hidden when the user "does not have permission"). *Pattern.*
- **Approve is the page's one primary button; Reject is secondary (gray), the negative action of the pair, never danger,** because nothing is destroyed. Carbon button (Sep 2026: secondary is "the negative action of the set"; danger is "for actions that could have destructive effects"); Carbon action labels (archived: Approve "typically initiates the next step", Reject "typically blocks the process from proceeding"). *Pattern.*
- **Approve acts at once; each decision ends with a toast naming the outcome** ("Order 1042 approved"). Only where approving can't be undone does it confirm, under "Confirm destructive or irreversible actions". See Notifications. *Pattern.*
- **Rejecting always asks for a reason,** and the statuses are For approval (blue), Approved (green) and Rejected (red). Carbon sets neither; both are business standards (see Business standards). *Pattern, `tables-status-badge`.*

### Notifications

Pattern `feedback-notifications`. Checked against the live sources on 2026-10-11: Carbon notification usage and accessibility (Sep 2026), notifications pattern (Aug 2026).

- **The kind follows what the message is about.** A toast says how something the user just did went (saved, deleted, sent); it slides in at the top right and needs no reply. An inline notification is about part of the page: it sits at the top of that part, never covers content, and stays until the issue is resolved; in Filament it is a `Callout`. A `Callout` isn't announced to screen readers, so it is only for a message that is there when the page loads; feedback on something the user just did is a toast, or a `Callout` with `role="status"`. Carbon notification component docs (React, Oct 2026: a callout "will not be announced to screenreader users" and is not for "notifications responding to user input"); WCAG 4.1.3. Something the user must decide before going on is a dialog (see "Confirm destructive …"). Carbon notification usage (variants: inline, toast, actionable, callout) and notifications pattern (modal notifications are for "critical information that needs their attention or action"). *Pattern.* Save feedback stays a toast; see "Not adopted from Carbon".
- **Colour follows the outcome:** success for a task completed as expected, warning for something done that may have unexpected results, danger for an error or failure, info for something not tied to the current task. Carbon notification usage and notifications pattern (status table). *Pattern.*
- **The title is short, says the most important thing, and has no full stop; after a failure it says what stopped or wasn't done** ("Order 1042 not saved"). **The body is one or two short sentences that don't repeat the title; after a failure it says how to fix it.** Plain words, no technical jargon. Filament's own titles ("Saved", "Created", "Deleted") meet this; naming the record is allowed, not required. Carbon notification usage (Content: "Don't use a period to end a title"; "User actions are mandatory for error messages"); notifications pattern ("Do not use technical jargon"); Carbon inline loading (Sep 2026: "Saved"). *Test (full stop), pattern.*
- **At most one action in a notification, of one or two words, and optional:** it never blocks the user, and usually takes them where they can resolve the message. What it leads to can also be reached another way, since a toast can vanish. Carbon notification usage ("Only one action is allowed for each notification"; "Limit action labels to one or two words"; "ensure there are alternative routes"); notifications pattern ("should not block the user"). *Pattern.*
- **A toast without an action may close itself after a few seconds,** and always has a close button. **A toast with an action, and an error, stays until the user dismisses it.** What a timed toast said can be found again afterwards, on the record or the list. Filament's 6 seconds conforms: Carbon's toast "can timeout and be coded to dismiss automatically after five seconds", and no qualifying source sets a number. Carbon notification usage ("the notification should remain on screen until the user dismisses it", for an actionable toast; "users should be able to access them elsewhere"); notifications pattern ("Don't use notifications that dismiss on a timer for critical or emergency messages"; error means "an error or critical failure"). *House defaults.*
- **Screen readers announce a notification without it taking focus:** `role="status"`, and `role="alert"` for an error. Filament's toasts already do. WCAG 4.1.3 (May 2026); Carbon notification accessibility ("Use the roles alert, log, or status for notifications that do not require user action").

### Action labels

Carbon common actions (Aug 2026) is the live source; where it is silent, Carbon's archived action-label list (`carbon-website` snapshot, 2026-09-30) fills in, marked *(archived)*. Filament's defaults are listed with each, and corrected app-wide where they differ.

| Label | Use it for | Not for | Filament |
|---|---|---|---|
| **New** *object* | The button that starts a record from scratch: "New supplier" *(archived: "use new to initiate the action and create to apply the user-supplied details")*. Its modal is titled the same (Carbon modal). | Putting an existing record somewhere (Add) | `CreateAction` is "New …"; house default titles its modal "New …", not "Create …" |
| **Create** | The button that saves the new record *(archived)* | Starting the form (New) | Conforms |
| **Add** | Putting an existing record into a list or relation: "Add inserts an existing object to a list, set, or system" | A new record (New) | House default: Attach and Associate are "Add" |
| **Edit** | Opening a record to change it: "Edit allows data or values to be changed" | | Conforms |
| **Save**, **Save changes** | Saving changes to a record (Carbon modal: "Add, Delete, and Save") | | Conforms |
| **Delete** | Destroying a record: "Delete destroys an object. Delete actions cannot be easily undone." Confirm it unless the impact is low. | Taking it out of a list (Remove) | Conforms. *Audit:* a delete action labelled otherwise |
| **Remove** | Taking a record out of a list or relation; "a removed item is not destroyed". Never emphasised as the primary action. | Destroying it (Delete) | House default: Detach and Dissociate are "Remove". *Audit:* a remove action labelled "Delete" |
| **Cancel** | Stopping the current task and closing, without saving, as a secondary button or link; say what is lost if that matters | Dismissing something with nothing to save (Close) | Conforms |
| **Close** | Dismissing something with nothing to save, such as a notification or a View modal: the × icon. "Do not use close in a button." | Stopping a task (Cancel) | House default: the View modal's footer Close button is removed; its × remains |
| **Clear** | Emptying a field or selections ("Clear filters") | Values back to the last save (Reset) | Conforms |
| **Reset** | Putting values back to their last saved state, usually as a link | | Conforms |
| **Copy** | Making a new identical record: "Copy creates a new identical instance of the selected object(s)" | | House default: Replicate is "Copy" |
| **Restore** | Bringing back a deleted record *(archived)* | | Conforms |
| **Next**, **Back** | Moving through the steps of a wizard; the last step's button names the final action (Carbon modal) | | Conforms |
| **Apply** | Applying settings or filters without closing *(archived)* | | Conforms |
| **Undo** | Reverting the latest change *(archived)* | | No default action |
| **Export**, **Import** | Taking data out of, or into, the app as a file *(archived)* | | Conforms |
| **OK**, **Done** | Only to acknowledge a message that needs no decision (Carbon modal, dialogs) | Any action with a result | Filament's generic "Confirm" and "Submit" confirm buttons become the action's own label (house default) |

## Writing

Labels, headings, buttons, helper text, empty states, notifications and errors. Error messages and helper text follow GOV.UK (see Forms); the rest follows Carbon. Marketing and long-form guidance doesn't apply.

- **Sentence case for labels, headings, buttons, column titles, tabs, badges and notification titles:** a capital only for the first word, names, product names and acronyms (TIN, VAT). Carbon content (Sep 2026: "Use sentence-style capitalization for headings, titles, labels, banners, and similar elements"); Carbon forms (Aug 2026: "except for product names and proper nouns"); Carbon data table (Sep 2026: "Column titles should use sentence-case capitalization"); Carbon button (Sep 2026: "sentence case for all button labels"). Filament writes its own labels in sentence case, but title-cases the model in some modal headings ("Delete selected Purchase Orders"), which the house defaults correct, and in every resource's Create page title ("Create Supplier"), List page title, navigation and breadcrumbs, which a plugin can't reach. A resource fixes them with `use Josephdlmd\FilamentStorybook\Concerns\HasSentenceCaseLabels;` and a `$recordTitleAttribute`; one whose records have no name sets its List page's `$title` and its `$breadcrumb` instead. *Audit, test, house defaults (modal headings).*
- **A label is one to three words that say what to enter, with no colon; a column title is one or two words.** Carbon forms (Aug 2026: "Use one to three words only"; "Do not use colons after label names"); Carbon text input (Sep 2026); Carbon data table (Sep 2026). *Audit (colons).*
- **Plain words the user would use: no technical jargon and no internal codes.** Carbon notifications pattern (Aug 2026: "Do not use technical jargon or language unfamiliar to the user"); Carbon empty states (Aug 2026: "plain language (no codes)").
- **An icon-only button's name is one or two words saying what it does.** Carbon tooltip (Sep 2026: "a concise one- or two-word description of the button's function"). See also the accessibility floor.

## Tables and finding

- **A readable identifier comes first; the other columns follow in order of importance and are easy to hide.** NN/g data tables (Apr 2022). *Pattern `tables-list-table`.*
- **Amounts and other numbers that can be totalled are right-aligned; phone numbers, postcodes, dates and IDs are not.** USWDS table (Aug 2026: "Right-align numerical data"). Carbon is silent. *Audit, pattern `tables-money-column`.*
- **One search field in the table's toolbar, whose placeholder says what it searches** ("Search by code or name"). Carbon data table (Sep 2026) and search (Aug 2026: placeholder text "hinting at what the user can search for"). A search field's placeholder is not a form field's (see Forms). *Pattern `tables-list-table`.*
- **With more than one filter category, filters sit above the table or on its left, never in a menu or dropdown.** They have an Apply button when results are slow and a way to clear them all. Carbon filtering (Aug 2026). *Audit, pattern `tables-list-table`.*
- **Applied filters stay applied for the rest of the session, until the user clears them.** Carbon search (Aug 2026): a selected filter "should persist during future searches until the user either discards the filter or begins a new session". *Pattern `tables-list-table`.*
- **Import and export sit in the List page's header, gray beside the primary New button.** Carbon data table (Sep 2026: the toolbar holds "global table actions such as … exporting"); Carbon button (Sep 2026: a ghost button "if there is another button on the page that requires primary styling"). *Pattern `tables-import-and-export`.*
- **An import says what it accepts before the upload** (a CSV file, its columns, with an example file to download, and the most rows) **and checks each row with the form's own rules and error wording, on the server.** Rows that fail are skipped, and the notification when the import finishes says how many and offers the errors to download, fix and import again. USWDS file input (Aug 2026: state limits up front); Carbon notification usage (Sep 2026: "User actions are mandatory for error messages"); error wording as in Forms. *Pattern, house defaults (the row limit is stated; wording).*
- **Key columns are sortable.** NN/g data tables (Apr 2022); USWDS table (Aug 2026).
- **Row actions live in an overflow menu ("…"); when there are fewer than three, they stay inline.** The table toolbar holds up to five actions. Carbon data table (Sep 2026). *Audit.*
- **Working lists use numbered pages showing the current range and the total, not infinite scroll. Content that updates itself next to other content can be paused, stopped or hidden.** Carbon pagination (Sep 2026); NN/g infinite scrolling (Sep 2022: not for finding something specific or comparing items); WCAG 2.2.2 (Aug 2026).
- **Bulk changes give feedback and offer Undo where they can; irreversible ones confirm in a danger dialog that says the consequence.** NN/g bulk actions video (Mar 2025); Carbon dialogs (Aug 2026); WCAG 3.3.4.
- **Records with the same fields are shown in a table, never as cards.** NN/g data tables (Apr 2022: cards make comparison "cognitively effortful and slow"); USWDS card ("Don't use a card as a substitute for a table row").
- **Truncated text has its full version one step away, on focus, on activation or on a linked page,** never on hover alone. WCAG 1.4.12 Understanding (Oct 2025); WCAG 1.4.13 for how a pop-up behaves. See "Where we differ from Carbon".
- **A specific time always shows its time zone.** Carbon date picker (Sep 2026): "Specific times should specify a timezone."
- **Dates and times use the app's locale format. A negative number keeps its minus sign even when colour is used.** Carbon date picker (Sep 2026: design for internationalization); WCAG 1.4.1 (Sep 2025).

## Dashboards

Pattern `dashboards-key-figures-and-charts`. Checked against the live sources on 2026-10-11. Carbon's data-visualisation pages show no update date; the dates are their sitemap entries (Sep 2026), and several are marked a work in progress.

- **Most important first:** key figures at the top, then charts, then the rest, and only what helps the user decide where to look. Carbon dashboards ("Place the most important at the top of the page"; "Limit the number of metrics"). *Pattern.*
- **A key figure is a number with a short label,** in the app's locale and with its unit (₱ for money). A change is said in words ("3 more than last week"), never by colour alone. Carbon chart anatomy ("a number with a single word description"); WCAG 1.4.1. Carbon sets no rule for up and down arrows or their colours, so there is none. *Pattern.*
- **A chart's type follows its purpose:** a line for a trend over time, bars to compare values, stacked bars for parts of a whole. Carbon simple charts. Carbon gives no guidance on pie and donut charts, so there is no rule on them; if used, each slice is labelled with its value or meets 3:1 against its neighbours (WCAG 1.4.11). *Pattern.*
- **A chart's heading says what it shows and its description the main insight; one series needs no legend, and several are labelled on the chart where they fit, otherwise with a legend above or below; an axis says what it counts; a bar or area chart's value axis starts at zero; gaps in the data are shown, never joined up; each data set keeps one colour on every chart of the dashboard.** Carbon chart anatomy, legends, axes and labels, dashboards ("Always start numerical axes at zero" for bar and area charts; "Never interpolate between periods when data is unavailable"; "Always use consistent colors for each data set"). *Pattern.*
- **Every chart gives its values as text.** In Filament, `getChartAssistiveContent()`, built from the chart's own data so the two never disagree. WCAG 1.1.1; W3C WAI complex images (Apr 2026: a long description, "a textual representation of the essential information"). *Pattern.*
- **Widgets don't refresh by themselves.** Filament's stats and chart widgets reload every 5 seconds unless `$pollingInterval` is `null`, and the user can't stop them. WCAG 2.2.2 (content that updates itself beside other content can be paused, stopped or hidden). *Audit, pattern.*
- **An empty widget says why there is no data and what would make some appear,** in text rather than an illustration when several are empty at once. Carbon empty states (Aug 2026).

## Layout and pages

- **Lists and record pages use the full width.** Carbon 2x grid (Sep 2026): the high-density model for complex product interfaces "uses the full width of the browser".
- **Record pages divide the 16-column grid by two and by four, never in thirds, and stack on a narrow page.** A record's facts are a list of labels and values. Carbon 2x grid (Sep 2026: "divide by two"; it advises against layouts in thirds); Carbon structured list (Sep 2026: "terms and definitions"); WCAG 1.4.10. Anything sticky must never hide focus (WCAG 2.4.11). *Patterns `records-view-page`, `records-details`.*
- **A related list's actions follow what the related records are.** Records that belong only to this one (a supplier's contacts) are made with New and destroyed with Delete. Records that exist on their own (the users in a team) are put in with Add and taken out with Remove, and the Remove dialog says the record stays. The list's button is gray when the page has a primary action. Carbon common actions (Aug 2026: Remove "is distinct from delete, as a removed item is not destroyed"; "Inform the user if the result is permanent"); Carbon button (Sep 2026: a ghost button for table actions "if there is another button on the page that requires primary styling"). *Pattern `records-related-records`, house defaults (Add and Remove labels), audit (labels and confirmations).*
- **Frequently used or critical content, and content users check against the details, is shown on the page, not in tabs.** On a record page that means its related lists. Carbon tabs (Sep 2026): "Do not use when tabs contain frequently used or critical information"; NN/g tabs (Aug 2024). *Audit.*
- **Tabs group different but related content in the same context:** as few as possible, with one- or two-word labels, in one row. Switching formats or filters of the same content is a content switcher, not tabs. Carbon tabs (Sep 2026); NN/g tabs (Aug 2024: "Use Only One Row of Tabs").
- **No crucial information or required input in a collapsed section; if users will read or fill all of it, don't collapse it.** NN/g accordions (Jul 2023): "Essential information should be presented outside of accordions"; Carbon accordion (Sep 2026).
- **Pages in a hierarchy deeper than two levels show breadcrumbs; single-level navigation has none.** Carbon breadcrumb (Sep 2026).
- **A message that needs action goes at the top of the content area it relates to;** a system-wide message goes directly below the header. Messages are never stacked: combine them into one, with a list of links. Carbon notifications (Aug 2026: "refrain from stacking them together"); USWDS site alert (Jan 2025: "Avoid stacking multiple site alerts").

### Read-only and locked records

Patterns `records-details`, `records-view-page` and `records-locked`. Checked against the live sources on 2026-10-10.

- **A value the user may read but not change is read-only, never disabled.** Read-only text keeps 4.5:1 contrast, stays reachable by keyboard and is read by screen readers. A disabled control is skipped by screen readers and exempt from contrast, so it never holds something the user needs to read. Carbon read-only states (Aug 2026); WCAG 1.4.3 (Sep 2026: the exemption covers controls "not available for user interaction"); WAI-ARIA 1.2 `aria-readonly` (focusable, and copying is supported).
- **Information that can't be edited here is text, not a field.** A View page is an infolist of entries, never Filament's default disabled form. Carbon read-only states: "If the component does not have an enabled state, do not use a read-only state to display static information." *Audit.*
- **Disabled is only for a control that becomes available once the user does something on the same screen.** When it holds up the primary action, an inline notification says how to enable it. Hide what the user may never do. Carbon disabled states (Aug 2026).
- **Read-only fields look read-only:** no field background and a fainter border, with the text at full contrast. Filament's `readOnly()` changes nothing. Carbon read-only states. *House CSS.*
- **When the user may not change a record, its values are read-only and its Edit action is hidden.** Carbon read-only states (Aug 2026, Permissions: "A user's credentials allow them to view the component but not modify it"); Carbon disabled states (Aug 2026: hidden when the user "does not have permission to … take action"). *Pattern `records-locked`.*
- **In a partly locked form, the locked fields are read-only.** Choice fields (radios, selects, checkboxes) have no read-only state in Filament, so they show their value as text. Carbon read-only states (Permissions; choice controls have a read-only state there). *Pattern `records-locked`.*
- **When editing is unavailable only for now** (a process running, or another user editing), Edit stays visible but disabled; when that holds up the primary action, an inline notification says how it becomes available. Carbon read-only states (Application process, Locked) and disabled states ("should never fully disappear"); USWDS button (Oct 2024).
- **Which statuses lock a record is a business fact.** Each app sets its own, ideally on its status enum, and uses it in its own `app/Stories`; the shared pattern uses one generic condition on the record.
- **A numbered badge marks new or updated items and clears once they're acknowledged,** with up to three digits. Carbon uses it only on the header's global actions. Carbon status indicators (Aug 2026).
- **Error pages use plain language and keep any error code small; they say what happened and give a way out** (home, contact or search). Carbon empty states (Aug 2026: "plain language (no codes)"); USWDS 404 page (Mar 2023); NN/g error messages (May 2023).

## Documents

Patterns `forms-document-totals`, `records-document-lifecycle`, `actions-void`, `actions-record-payment`, `forms-record-picker` and `actions-download-pdf`. Checked against the live sources on 2026-10-11; Carbon has no document pattern, so these rules apply its modal, button, status and dropdown guidance. The business facts behind them are under Business standards.

- **A document's totals are read-only text under its lines, right-aligned:** the VATable sales, the VAT, the zero-rated and VAT-exempt sales, and the total amount due. They are worked out again on the server when saving, with the same code the form uses, and stored; never taken from the form. Carbon text input (Sep 2026: read-only, not disabled, for a value to read); USWDS table (Aug 2026: "Right-align numerical data"); Carbon forms (Aug 2026: labels above). *Pattern `forms-document-totals`.*
- **Posting confirms in a danger dialog, because it can't be undone;** the dialog says the document gets its number and can no longer be changed, and how to correct it (void and reissue). A sales invoice's dialog asks for its form's serial, and the error for one already recorded says what to do. Post is the draft's one primary button and shows only on a draft. Carbon modal (Sep 2026: "If the action is destructive or irreversible, then use a transactional danger modal"; "Both the title and the button should reflect the action"); Carbon action labels (archived: Post "adds status to a log or record"). *Pattern `records-document-lifecycle`.*
- **A posted document has no Edit or Delete;** the policy refuses both, so Filament hides them and the document opens on its View page. A draft may be deleted, confirmed as any delete is. Carbon disabled states (Aug 2026: hide what the user may not do); see "Read-only and locked records". *Pattern `records-document-lifecycle`.*
- **Voiding is low-emphasis danger among the page's actions, confirmed in a danger dialog that asks for the reason,** says the document keeps its number and that this can't be undone, and is shown only to users who may void. While the document has payments, Void stays visible (it becomes available once they are voided, on the same page) and its dialog says what to do first, with nothing to confirm (Carbon modal: a passive modal "presents information the user needs to be aware of"). The verb is Void, not Cancel, because Cancel is the dialog's own button for stopping and closing. Carbon modal and button (Sep 2026); Carbon common actions (Aug 2026: Cancel "stops the current action and closes the component or item"); Carbon disabled states (Aug 2026). *Pattern `actions-void`.*
- **Document statuses:** Draft gray, Posted green, Void red, each with its word. Carbon status indicators (Aug 2026: gray "indicates drafts", green "represents normal or success"); red for Void is the owner's business standard, since Carbon sets no colour for it. **Payment statuses:** Unpaid gray, Partly paid blue, Paid green, Overdue red. Carbon status indicators (Aug 2026: gray for "jobs that haven't been started", blue for "workflow progress", red for "danger or error"). *Patterns.*
- **A payment is recorded in a modal (fewer than five inputs), with the balance due in the amount's helper text, and what it settles is checked against the balance on the server;** the error says the most it can be ("… must be ₱2,363.71 or less, the balance due"). The save locks the document's row and checks again, since two users may pay at once. Carbon forms (Aug 2026: a dialog form for fewer than five inputs); GOV.UK error message (Jan 2024: say the limit, as in "must be 35 characters or less"). *Pattern `actions-record-payment`.*
- **A record of its own (a customer, supplier or item) is picked in a searchable select,** on the relationship, searching code and name and showing both ("C-0001 Abra Medical Supply"), opening with the first options in code order. Carbon dropdown (Sep 2026: "Use a combo box when the user needs to select one option, but the list of options may be very long or not predefined"; "The menu opens by clicking anywhere in the field, allowing users to type and sort through the list of options"; options "in alphabetical order"; option text "brief, accurate"). *Audit (a relationship select that can't be searched), pattern `forms-record-picker`.*
- **A posted document prints as a PDF in the one shared layout** (`filament-storybook::documents.print`), from a gray "Download PDF" button on its View page. The PDF is tagged, so its headings and table headers reach screen readers, carries the document's type and number as its title and the app's language, and is A4; text is near-black on white, and amounts are right-aligned. W3C WCAG 2.2 PDF techniques (PDF6 table headers, PDF9 headings, PDF16 language, PDF18 title, all on a tagged PDF); WCAG 1.4.3; USWDS table (Aug 2026); Carbon action labels (archived: Download "transfers a file from a remote to local"); Carbon button (Sep 2026: one primary action per screen). *Pattern `actions-download-pdf`; test (tagged, A4).*

## Accessibility floor

- **WCAG 2.1 AA checks on every story and page.** axe-core, the engine behind Storybook's accessibility addon. *Test, audit.*
- **Targets at least 24 by 24 CSS pixels.** WCAG 2.5.8.
- **Nothing lost at 320 CSS pixels wide (400% zoom), or with increased text spacing.** WCAG 1.4.10 and 1.4.12.
- **Colour never alone:** gray for a draft or not started, blue for in progress, yellow for a warning, green for done, red for danger or error, always with a word. WCAG 1.4.1; Carbon status indicators (Aug 2026). *Pattern `tables-status-badge`.*
- **Contrast: 4.5:1 for text; 3:1 for status colours against the background and each other, and for chart series against the background and against each other where they touch,** unless labels or values carry the same information. WCAG 1.4.3 and 1.4.11; Carbon status indicators (Aug 2026).
- **Focus is always visible and never fully hidden under the sticky top bar or a sticky aside.** WCAG 2.4.7 and 2.4.11.
- **Tooltips hold only supplementary information; anything needed for the task is visible.** Carbon tooltip (Sep 2026): "do not include pertinent information for the user to complete their task". WCAG 1.4.13 for how they behave.
- **Every icon-only action keeps a `label()`, which becomes its accessible name.** WCAG 4.1.2. *Test, audit.*

## Sign-in

- **Passwords: at least 15 characters when the password is the only factor (8 when multi-factor sign-in is required); at least 64 allowed; no composition rules; checked against a blocklist of common and breached passwords; pasting and showing the password allowed.** NIST SP 800-63B-4 (Aug 2025; 64, paste and show are SHOULDs); WCAG 3.3.8.
- **Multi-factor sign-in offers at least one phishing-resistant option (such as a passkey) and issues recovery codes. Email is never an out-of-band factor;** email recovery codes are allowed. NIST SP 800-63B-4: email "SHALL NOT be used for out-of-band authentication"; at AAL2, "SHALL offer at least one phishing-resistant authentication option"; recovery codes are a SHOULD.
- **The account owner is notified when a sign-in method is added or account recovery is used,** and should be when one is removed. NIST SP 800-63B-4.

## Business standards (from the owner)

Approved by the owner on 2026-10-10.

- **TIN:** the 9-digit TIN plus a 3-digit branch code, stored as `000-000-000-000`, with `000` for the head office. *Pattern `forms-tin`.*
- **Amounts** (prices, totals, payments, balances, VAT and withholding): pesos and centavos, **at most 2 decimal places, checked on the server.** The peso has 100 centavos (PHP; 2 minor units). *Pattern `forms-money-input`.*
- **Unit cost** (weighted-average, landed or derived costs): **up to 4 decimal places,** so costs worked out by division don't drift by centavos. *Pattern `forms-unit-cost`.*
- **Quantity:** **up to 3 decimal places,** for weights, volumes and lengths. *Pattern `forms-quantity`.*
- **Round once, at the end:** a line total is quantity × unit price, rounded to 2 decimals once, as `forms-line-items` shows. VAT is worked out once per invoice for each VAT class (12%, zero-rated, exempt), on that class's total, then rounded to 2 decimals. This matches BIR's requirement to show VAT as a separate item and to break out taxable, zero-rated and exempt sales (RR 7-2024).
- **Rounding is half up, away from zero for negatives:** ₱50.025 becomes ₱50.03, and a credit of −₱50.025 becomes −₱50.03, so a return mirrors its sale. This is how people check figures by hand and how Excel's `ROUND` and PostgreSQL's `round()` work. Not banker's rounding (half to even). *Pattern `forms-line-items`.*
- **Approval statuses:** For approval is info (blue), Approved is success (green), Rejected is danger (red), each with its word. **Rejecting requires a reason,** which the person who raised the record sees. Decided 2026-10-11: the owner asked for best practice. Basis: Carbon's status palette (red for "danger or error", yellow for a warning that something may go wrong) and Carbon notifications pattern ("Do not leave users without next steps"); confirmed by Atlassian (red for "declined") and SAP Fiori (red for a "negative status or consequence"). *Patterns `actions-approve-and-reject`, `tables-status-badge`.*
- **One method everywhere:** the invoice, the journal entry and the tax returns use the same amounts and the same rounding, so the books always agree with the invoices.
- **Never floating-point money:** money is stored in decimal columns (for example `decimal(15, 2)`, and `decimal(15, 4)` for unit cost) and computed with decimal arithmetic, never PHP floats. *Test (`round()` in a story).*

**Documents (approved by the owner on 2026-10-11):**
- **One company,** at one location: no company or branch on any table.
- **Documents:** sales invoices, purchase orders, receiving reports, and payments both ways (collections from customers, payments to suppliers).
- **Statuses:** Draft, then Posted, then Void. A posted document is never edited and never deleted; to correct one, void it and issue a new one (or a credit or debit note). *Pattern `records-document-lifecycle`.*
- **Voiding** requires a reason, keeps the document's number, and keeps the document visible, marked Void (red). Only users with a void permission (a manager or accountant role) may void; posting doesn't grant it. Who voided it and when are kept. **A document with payments that aren't void can't be voided until they are;** a payment is voided the same way, which gives its amount back to the balance due. *Pattern `actions-void`.*
- **Sales invoices are written by hand on BIR pre-printed forms,** so a sales invoice's number is the serial printed on its form: the user types it when posting, and the app refuses a serial already recorded. One number per invoice, so the books, the form and BIR's records agree. *Pattern `records-document-lifecycle`.*
- **Numbering for the rest:** a prefix for each document type (PO purchase order, RR receiving report, CR collection receipt, PV payment voucher) and a 6-digit series that never resets, such as PO-000042. The number is given when the document is posted, in the same transaction, with no gaps. *Example `DocumentNumber`.*
- **Prices include VAT.** VAT is 12/112 of each document's VATable total, worked out once and rounded once, half up; the VATable sales are that total less the VAT. *Pattern `forms-document-totals`.*
- **Withholding tax (EWT) applies both ways and is taken on payment,** never on the document, so the total amount due is before withholding. From a customer, the user types the amount withheld from the customer's BIR Form 2307 (which may arrive after the cash). To a supplier, the app works it out: 1% for goods or 2% for services, on the VAT-exclusive amount. *Pattern `actions-record-payment`.*
- **Payments may be partial;** a payment never settles more than the balance due.

**Good business practice, recommended at the owner's request (2026-10-11); the owner chose item 5:**
1. **Withholding on a partial supplier payment** is the rate on the VAT-exclusive share of the amount settled (the amount × (total − VAT) ÷ total), and the payment that settles the bill takes what is left. A bill's payments together then withhold exactly the rate on its total less VAT, which is what its BIR Form 2307 shows. *Example `DocumentTotals::withholding()`.* Ask the CPA in the same review as the rounding method and VAT per document (below).
2. **Payment prefixes:** CR for a collection receipt, PV for a payment voucher. A collection receipt is a supplementary document under RR 7-2024 and is printed with "This document is not valid for claim of input tax".
3. **When less is received than ordered,** the purchase order stays open, marked Partly received, until the rest arrives or someone who may close orders closes the rest with a reason. For the stock-movement patterns.
4. **Voiding a document with payments:** void the payments first (above), so no payment points at a void document and the 2307s and balances stay right.
5. **Printed copies:** every document can be printed from the app as a PDF. A sales invoice printout is a copy, titled "Sales invoice (copy)", showing the form's serial and "This document is not valid for claim of input tax", since the official invoice is the hand-written BIR form.

**Decided by the owner (2026-10-10):**
1. **Half up, away from zero for negatives,** as above. No BIR rule setting the method was found; ask the CPA in the same review as item 2.
2. **VAT is worked out per invoice for each VAT class,** not per line. A CPA review is still worthwhile; if the CPA prefers per line, change it here and in the patterns, in one release.
3. **None of the owner's companies is under BIR's e-invoicing (EIS) mandate at present** (RR 11-2025, RMC 98-2026). If one comes under it, EIS's technical specification sets field precision and overrides this section.

## Engineering rules (Filament facts, not UX)

- `Hidden` fields can be edited by the user; set trusted values on the server. (Filament docs)
- A table calls its row actions' closures without a record while it has no rows, so type the record nullable: `fn (?Order $record)`. A closure typed `Order $record` stops the empty list from rendering. (Filament source; three eval agents hit it) *Test.*
- Imports and exports run as queued jobs without policy checks: authorize the actions, and decide in the importer's `resolveRecord()` which records an import may change. (Filament source)
- An imported number stays text and is validated (`numeric`, `decimal:0,2`); `ImportColumn::numeric()` rounds extra decimals away instead of rejecting the row. (Filament source)
- Restrict which records a `ModalTableSelect` offers in `relationship(modifyQueryUsing:)`; the table filter alone isn't a security boundary. (Filament docs)
- Bulk actions check permission per record with `authorizeIndividualRecords()`. (Filament docs)
- No deprecated components: `Select->multiple()`, not `MultiSelect`; `TextEntry`, not `Placeholder`. (Filament 5 source)
- A read-only field is still submitted, and its value can be changed in the browser. Keep locked values out of the save with `saved(false)` on the field or a group around it, and let the policy's `update()` refuse when nothing may change; Filament then hides Edit and refuses the Edit page. (Filament docs) *Audit.*
- A relation manager embedded with `Livewire::make()` skips Filament's `canViewForRecord()` check; keep it with `visible()`, as `records-view-page` shows. (Filament 5 source)
- In Filament, `EditAction::make()` anywhere on a resource page opens the Edit page, or the resource form in a modal when there is no Edit page, and is hidden when the policy refuses `update()`. (Filament 5 source)

## Where we differ from Carbon

- **No placeholder text at all.** Carbon allows it sparingly; GOV.UK and USWDS research shows it disappears, isn't read by some screen readers and is mistaken for a value, and form wording follows GOV.UK.
- **No input masks.** Carbon has no mask component; USWDS's own mask failed WCAG 2.1 AA, and WCAG wins.
- **Truncated text is revealed on focus, on activation or on a linked page, never on hover alone.** Carbon's data table shows it "in a tooltip on hover"; WCAG 1.4.12 Understanding (Oct 2025) counts truncation as passing only with such a mechanism, and WCAG wins.
- **Error messages say what to do ("Enter name").** Carbon's examples state the problem; form wording follows GOV.UK.
- **Helper text has no full stop.** Carbon's text input asks for "full sentences with punctuation"; form wording follows GOV.UK, which asks for "a single short sentence, without any full stops".

## Not adopted from Carbon

Carbon guidance that is no longer published, or that Filament can't follow app-wide (checked 2026-10-11):

- **Carbon's taken-down writing guidance:** second person, present tense, active voice, no "please", contractions, exclamation marks, and spelling out abbreviations. The page was last changed in 2023 and removed on 2026-09-30, and IBM Style, which replaces it, can't be read without a sign-in. Re-check if IBM Style becomes readable.
- **Carbon guidance Filament can't follow app-wide:**
  - feedback on a save shown inline, "in the region of the page the user is working in" (notifications pattern): Filament leaves that region when it saves (Create goes to another page, a modal closes), so it confirms a save in a toast, which is announced as a status message (WCAG 4.1.3)
  - an actionable toast taking focus (Filament has no setting; its toasts stay reachable and are announced)
  - "Move to trash" for a delete that can be restored (Filament's soft delete), keeping "Delete" for the permanent one (Filament's "Force delete"): Filament's delete, restore, force delete and trashed filter would all have to change together
  - "Log in" and "Log out" (the archived list only), in place of Filament's "Sign in" and "Sign out"
  - "View details" (the archived list only), in place of Filament's "View"
  - Apply rather than "Save changes" (the archived list, for a dialog that stays open; Filament's closes)

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
- **Save button on the right.** No qualifying source requires it.
- **How a missing value shows,** whether "—" or a link to where it's added. GOV.UK's summary list links it, but GOV.UK covers form wording, and no qualifying source sets either. An empty section follows Carbon's empty states.
- **Two thirds and one third on record pages** (GOV.UK layout). Carbon's 2x grid advises against layouts in thirds, and GOV.UK covers form wording, not layout.
- **Rules backed only by GOV.UK outside form wording, or by its style guide** (re-checked 2026-10-11):
  - a summary card's actions on its heading row (Carbon has no card pattern)
  - "select", not "click"
  - long forms keep readable line lengths
  - timestamps converted to the user's time zone (Carbon asks only that a time shows its zone)
  - never ISO dates (Carbon shows the international year-month-day format as valid), and a rule on currency symbols and separators
  - error pages say whether work was saved
- **Parts of rules no source sets** (re-checked 2026-10-11):
  - a default sort on every list
  - lists remembering their search and sort (Carbon covers filters, for the session)
  - related lists "most used first"
  - card grids allowed when the records are images
  - "never pre-select a judgement"
  - "about" five inputs (Carbon says five)
  - a count badge meaning waiting work, hidden at zero (the NN/g article cited doesn't say it)
  - a locked record must say why it is locked: Carbon hides an action the user may not take, and asks for a notification only when it is unavailable for now
  - search limited to the identifier (no source limits it)
  - a confirmation heading naming the record or count, and a save message naming the record
  - "offer to clear" filters (Carbon: suggest adjusting them)
  - tabs "never nested", and tabs for "views of the same thing" (Carbon uses a content switcher for that)
  - "warning" meaning waiting (Carbon: yellow is a warning, blue is in progress)
  - tooltips only repeating what's visible (WCAG 1.4.13 doesn't say it; Carbon's tooltip rule replaces it)
  - an authenticator app as the required second factor (NIST asks for a phishing-resistant option)
  - numbered line items, and line-item fields counted to decide a stacked layout
- **Email codes as a sign-in fallback.** NIST forbids it.
- **Breadcrumbs off everywhere.** Carbon keeps them in hierarchies deeper than two levels.
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

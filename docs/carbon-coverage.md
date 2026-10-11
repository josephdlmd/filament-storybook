# Carbon coverage

Every Carbon page was checked against `docs/rules.md` and `StorybookPlugin::compact()` on 2026-10-11. Each page gets one row below, and each gap gets one finding.
To keep this up to date, re-check a page when its "updated" date changes. A finding's status changes when it is done, adopted or rejected.
Status of a page: **Covered** (no open findings), **Gaps** (open findings), **Not applicable** (no Filament equivalent, or not relevant).
Marks: **[WCAG]** an accessibility failure; **[Owner]** needs an owner decision.

## Pages

Dates are the pages' "Last updated" lines. Every form-input page shows 2026-09-30, the day the site moved, so that date doesn't say when the content last changed. The data-visualisation pages have no date line, so their dates come from the sitemap's `lastmod`.

| Page | Updated | Filament equivalent | Status | Findings |
|---|---|---|---|---|
| **Components** | | | | |
| Button | 2026-09-30 | `Action` (button, `outlined()`, `link()`, `iconButton()`) | Gaps | BTN-1, BTN-2, BTN-9, BTN-14, BTN-15, BTN-17, CMP-1 |
| Menu buttons | 2026-09-28 | `ActionGroup` (`->button()`; "…" overflow) | Gaps | BTN-9, BTN-10, BTN-11, CMP-3 |
| Menu | 2026-09-30 | `x-filament::dropdown` (`.fi-dropdown-list`) | Gaps | BTN-12, BTN-13, CMP-1, CMP-3, CMP-4 |
| Link | 2026-09-28 | `.fi-link` (`Action::link()`, columns and entries with `url()`) | Gaps | A11Y-3, TBL-19, CMP-1 |
| Popover | 2026-09-30 | `.fi-dropdown-panel` (filters, column manager) | Gaps | BTN-21, BTN-22, CMP-5 |
| Toggletip | 2026-09-30 | None (nearest: `hintIcon(tooltip:)`) | Gaps | A11Y-1 |
| Tooltip | 2026-09-30 | `tooltip()` (tippy.js) | Gaps | BTN-15, BTN-16, A11Y-1, CMP-6 |
| Content switcher | 2026-09-11 | List page `getTabs()` | Gaps | NAV-9, NAV-10 |
| Tabs | 2026-09-30 | Schema `Tabs`, `x-filament::tabs`, relation-manager tabs | Gaps | NAV-9, NAV-11, BTN-15, CMP-1 |
| Accordion | 2026-09-09 | `Section::collapsible()` | Gaps | NAV-12, CMP-1 |
| Text input | 2026-09-30 | `TextInput`, `Textarea`, `password()->revealable()` | Gaps | FRM-3, FRM-4, FRM-5 |
| Number input | 2026-09-30 | `TextInput::numeric()` | Gaps | FRM-1, FRM-2, FRM-3 |
| Select | 2026-09-30 | `Select` (native) | Gaps | FRM-6 |
| Dropdown | 2026-09-30 | `Select::searchable()` / `multiple()`, `SelectFilter` | Gaps | FRM-7, TBL-18 |
| Checkbox | 2026-09-30 | `Checkbox`, `CheckboxList` | Gaps | FRM-7, FRM-9 |
| Radio button | 2026-09-30 | `Radio` | Gaps | FRM-7, FRM-8, FRM-11 |
| Toggle | 2026-09-30 | `Toggle`, `ToggleColumn` | Gaps | FRM-12, FRM-13 |
| Slider | 2026-09-30 | `Slider` (unused) | Gaps | FRM-14 |
| Date picker | 2026-09-30 | `DatePicker` (native), `DateTimePicker` | Gaps | FRM-15, FRM-16 |
| File uploader | 2026-09-30 | `FileUpload` (FilePond) | Gaps | FRM-17, FRM-18 |
| Search | 2026-09-30 | Table search, global search | Gaps | A11Y-4, TBL-7 |
| Form | 2026-09-30 | Schema `Form`; resource and action forms | Gaps | FRM-10, FRM-11, A11Y-1 |
| Data table | 2026-09-30 | `Table` | Gaps | TBL-1, TBL-2, TBL-3, TBL-4, TBL-5, CMP-8 |
| Pagination | 2026-09-30 | `fi-pagination` | Gaps | TBL-6, CMP-7 |
| Structured list | 2026-09-30 | Infolist entries with inline labels | Covered | |
| List | 2026-09-30 | `listWithLineBreaks()->bulleted()` | Covered | |
| Contained list | 2026-09-30 | None | Not applicable | |
| Tree view | 2026-09-30 | None | Not applicable | |
| Tag | 2026-09-30 | `->badge()`, filter indicators | Gaps | TBL-14, TBL-15 |
| Tile | 2026-09-30 | Stats overview stat | Gaps | DSH-15, CMP-9 |
| Code snippet | 2026-09-09 | `copyable()`, `CodeEntry` | Gaps | A11Y-2 |
| Progress bar | 2026-09-30 | None | Not applicable | |
| Progress indicator | 2026-09-30 | `Wizard` header | Gaps | FRM-26 |
| Loading | 2026-09-28 | `fi-loading-indicator`, `fi-loading-section` | Gaps | TBL-2 |
| Inline loading | 2026-09-30 | Button spinner while the action runs | Covered | |
| UI shell header | 2026-10-02 | Top bar (`.fi-topbar`) | Gaps | NAV-1, NAV-2, CMP-2, CMP-15 |
| UI shell left panel | 2026-10-02 | Sidebar (`.fi-sidebar`) | Gaps | NAV-3, NAV-4, CMP-11, CMP-12, RUL-3 |
| UI shell right panel | 2026-10-02 | Nearest: database notifications slide-over | Covered | |
| Modal | 2026-09-30 | Action modals, confirmations, slide-overs | Gaps | BTN-1, BTN-2, BTN-3, BTN-4, BTN-6, CMP-2 |
| Notification | 2026-09-30 | `Notification` toasts, `Callout` | Gaps | FDB-1, FDB-2, FDB-3, CMP-2, CMP-13 |
| Breadcrumb | 2026-09-30 | `.fi-breadcrumbs` | Gaps | NAV-5, CMP-14 |
| **Patterns** | | | | |
| Common actions | 2026-08-11 | Create, Edit, Delete, Cancel, Close and the other actions | Gaps | BTN-19, BTN-20, FRM-23, FRM-24, DSH-16 |
| Dialogs | 2026-08-11 | Action modals, confirmations, slide-overs | Gaps | BTN-1, BTN-4, BTN-5, BTN-7, BTN-8 |
| Disabled states | 2026-08-11 | `disabled()`, `hidden()`, `readOnly()` | Gaps | CMP-17 |
| Disclosures | 2026-08-11 | `ActionGroup`, user menu, filter dropdowns | Gaps | BTN-21, BTN-23 |
| Empty states | 2026-08-11 | Table empty state, `fi-empty-state` | Gaps | BTN-17, BTN-18, TBL-20, NAV-15 |
| Filtering | 2026-08-11 | Table filters and indicators | Gaps | TBL-8 |
| Fluid styles | 2026-08-11 | None | Not applicable | |
| Forms | 2026-08-11 | Resource, modal and slide-over forms; wizard | Gaps | BTN-1, FRM-8, FRM-9, FRM-10, FRM-19, FRM-20, FRM-21, FRM-22 |
| Global header | 2026-08-11 | Top bar, user menu, global search, sidebar | Gaps | FRM-24, TBL-9, NAV-6, NAV-7 |
| Loading | 2026-08-11 | `wire:loading` spinners, table overlay, queued jobs | Gaps | FDB-5 |
| Login | 2026-08-12 | `Filament\Auth\Pages\Login` | Gaps | FRM-27, FRM-28, RUL-2 |
| Notifications | 2026-08-12 | Toasts, `Callout`, database notifications | Gaps | FDB-1, FDB-2, FDB-3, FDB-4 |
| Overflow content | 2026-08-12 | `limit()`, `wrap()`, `limitList()` | Gaps | TBL-12, TBL-13, CMP-4 |
| Read-only states | 2026-08-12 | `readOnly()`, infolists | Gaps | FRM-25 |
| Search | 2026-08-12 | Table search, global search | Gaps | TBL-10, TBL-11 |
| Status indicators | 2026-08-12 | `badge()`, navigation badges, stat descriptions | Gaps | DSH-4, TBL-16, TBL-17, NAV-8 |
| Text toolbar | 2026-08-12 | `RichEditor`, `MarkdownEditor` (no pattern uses them) | Not applicable | |
| **Foundations** | | | | |
| 2x Grid | 2026-09-25 | `Grid`/`columns()`, dashboard and stats columns | Gaps | DSH-2, RUL-4 |
| Spacing | 2026-09-30 | Tailwind spacing, Compact theme tokens | Gaps | CMP-16 |
| Typography (overview) | 2026-09-29 | Theme sizes and weights | Gaps | CMP-9, CMP-10, CMP-18 |
| Typography (style strategies) | 2026-09-29 | None (a choice of styles) | Covered | |
| Typography (type sets) | 2026-09-29 | Tailwind sizes and weights on Filament classes | Covered | |
| Color | 2026-09-25 | Theme colours, dark mode, brand logos | Gaps | DSH-1, NAV-13, NAV-14 |
| Icons | 2026-08-12 | Heroicons (`.fi-icon`) | Gaps | CMP-2, RUL-5 |
| Motion (overview) | 2026-08-12 | Filament transitions, reduced-motion reset | Covered | |
| Motion (choreography) | 2026-08-12 | None | Not applicable | |
| Accessibility (overview) | 2026-09-25 | None (principles) | Covered | |
| Accessibility (keyboard) | 2026-09-25 | Landmarks, skip link | Gaps | BTN-1 |
| Accessibility (color) | 2026-09-25 | None | Covered | |
| Content | 2026-09-30 | None (links to IBM Style) | Gaps | RUL-6 |
| **Data visualisation** | | | | |
| Chart anatomy | 2026-09-17 | `ChartWidget`, `Stat` | Gaps | RUL-1 |
| Axes and labels | 2026-09-17 | Chart.js `scales` | Gaps | DSH-5, DSH-6, DSH-7, DSH-8 |
| Legends | 2026-09-17 | Chart.js legend | Gaps | DSH-3, DSH-9, A11Y-5 |
| Simple charts | 2026-09-17 | `ChartWidget::getType()`, `Stat::chart()` | Gaps | DSH-10 |
| Dashboards | 2026-09-17 | `Filament\Pages\Dashboard`, page filters | Gaps | DSH-11, DSH-12, DSH-13 |
| Color palettes | 2026-09-17 | `ChartWidget::$color`, dataset colours | Gaps | DSH-1, DSH-14 |
| Flow charts | 2026-10-06 | None | Not applicable | |
| Gantt charts | 2026-09-17 | None | Not applicable | |
| Spatial charts | 2026-09-17 | None | Not applicable | |

## Findings

Each finding gives what to do, its basis, its kind and its status. All are **Proposed**.

### Buttons, menus and dialogs

- **BTN-1** — In a modal or slide-over, Cancel sits on the left and the primary button on the right, using the house default `modalFooterActionsAlignment(Alignment::Center)`. A full-page form keeps its primary first, on the left. — Carbon modal: "the secondary button is on the left and the primary button is placed on the right"; Carbon dialogs: "Cancel is always the outmost left button option"; Carbon button and forms: "right-aligned … within wizards and dialog windows"; Accessibility (keyboard): focus order "logical and predictable". — house default + rule; corrects the Dropped entry "Save button on the right" (RUL). — Done 2026-10-11 (house default, rule; checked in a browser).
  - *The batches disagree on the fix.* Batch e proposed `Alignment::End`. Filament's `CanOpenModal::getModalFooterActions()` reverses the actions only for `Center`. `End` keeps them in their order and reverses them only on screen (CSS `flex-row-reverse`), so focus would run right to left, which fails WCAG 2.4.3. Use `Center`. It also gives Carbon's equal-width buttons.
  - Batch e also says Carbon's tab order is "primary then secondary". Batches a and d don't support that. Check a three-button footer (Create & create another) and wide modals in a browser.
- **BTN-2** — A danger confirmation focuses Cancel, which Filament already does. Any other confirmation focuses its primary button (`autofocus` on `modalSubmitAction`). — Carbon modal: "For dialogs which prompt for confirmation or user decision, the primary button takes focus"; "For destructive interactions, the 'cancel' button takes focus". — rule + house default; not verified. — Open: Filament's focus in a confirmation still needs checking in a browser before a house default.
- **BTN-3** — Modal headers and footers stay fixed while the body scrolls: house default `stickyModalHeader()->stickyModalFooter()`. — Carbon modal: "the body section should scroll vertically with the header and footer remaining fixed in place". — house default. — Done 2026-10-11 (house default for form modals, rule).
- **BTN-4** — No modal opens on page load (`$defaultAction`, `mountAction()` in `mount()`); use a callout or toast. — Carbon modal: "Modals are triggered as a result of a user's action and are not system generated"; Carbon dialogs: "Don't use when the user hasn't triggered the dialog." — rule + audit check. — Done 2026-10-11 (rule).
- **BTN-5** — A modal never opens another modal. That rules out `createOptionForm()`/`editOptionForm()`, `ModalTableSelect`, and confirming actions inside a modal form. — Carbon dialogs: "One modal should never trigger another modal." — rule + audit check + test check. — Done 2026-10-11 (rule).
- **BTN-6** — Modal content never scrolls sideways; a wide table or line items go in a wider modal, a slide-over or a page. — Carbon modal: "Modal content should never scroll horizontally; instead, use a larger size modal." — rule. — Done 2026-10-11 (rule).
- **BTN-7** — A modal form has no links that leave it (`url()` actions, hint links). — Carbon dialogs: "Avoid components like links that will take the user away from the current context." — rule + test check. — Done 2026-10-11 (rule).
- **BTN-8** — A table inside a modal is only for selecting; it has no bulk or row actions. Low priority. — Carbon dialogs: "Avoid preforming table functions like batch editing or batch actions inside the modal itself." — rule. — Done 2026-10-11 (rule).
- **BTN-9** — A page header shows at most three buttons; the rest go in a labelled menu button (`ActionGroup->button()`). The icon-only "…" is for rows and cards. — Carbon button: "Any more than three actions should be grouped meaningfully using menu buttons"; Carbon menu buttons: "such as table rows or cards, use an overflow menu". — rule + audit check. — Done 2026-10-11 (rule, audit check).
- **BTN-10** — A labelled menu button shows a chevron after its label: house default `ChevronDown`, `iconPosition(After)` when `isButton()` and no icon is set. — Carbon menu buttons: "The icon used within the menu button should always be a caret". — house default (or a rule and story). — Done 2026-10-11 (house default, rule).
- **BTN-11** — Menu items don't repeat the menu button's verb ("New" → "Supplier", not "New supplier"). — Carbon menu buttons: "The shared action should not be repeated in the menu list." — rule; audit possible, low value. — Done 2026-10-11 (rule).
- **BTN-12** — An overflow menu has at most 12 items; a labelled menu button has fewer than 5. — Carbon menu: "no more than 12 items … With menu buttons, keep the menu list smaller with under 5 items." — rule + audit check. — Done 2026-10-11 (rule).
- **BTN-13** — In a menu, Delete (and Void, Remove) comes last, below a divider. — Carbon menu: "Divider lines … distinguishing actions that might have a significant impact on the user's data, such as deleting." — rule + audit check. — Done 2026-10-11 (rule).
- **BTN-14** — Buttons in a group are all the same size, and either all have icons or none do. Low value. — Carbon button: "We do not recommend mixing different button sizes in button groups"; "showing an icon for each button … or showing no icons". — rule + audit check. — Done 2026-10-11 (rule).
- **BTN-15** — Every icon-only button shows its label as a tooltip on hover and focus. This is a house default on `Action` and `ActionGroup` when `isIconButton()`, and a `tooltip()` set by the app still wins. It covers icon-only tabs too. — Carbon button: "a tooltip is always required with text explaining what the icon button would do"; Carbon tooltip: "Every icon-only button needs a tooltip". — house default + rule + test check + audit check. — Done 2026-10-11 (house default for the "…" menu; test and audit checks for icon-only actions, since a tooltip on every action would turn off Filament's fast rendering of row actions).
- **BTN-16** — Tooltips that define or instruct are full sentences with punctuation. Low value. — Carbon tooltip: "write the text as complete sentences with punctuation." — rule. — Done 2026-10-11 (rule).
- **BTN-17** — An empty state's button is low-emphasis (`outlined()` or gray) when the page header already has the primary. Fix `tables-list-table`, which shows two primaries. The same applies to empty related lists. — Carbon button: "pages that already have the primary action defined, a tertiary button is the ideal solution"; Carbon empty states: "using a tertiary button for the call to action". — story fix + rule + audit check. — Done 2026-10-11 (rule, audit check, `tables-list-table` fixed).
- **BTN-18** — An empty state offers one action. — Carbon empty states: "Don't cover multiple options in one empty state … pick the most important." — rule + audit check. — Done 2026-10-11 (rule, audit check).
- **BTN-19** — A delete that also destroys other records asks the user to type the record's name (a `TextInput` with an `in:` rule). Add an `actions-delete` variant. — Carbon common actions: "have the user type the name of the resource they are deleting". — rule + story fix. — Rule done 2026-10-11; the `actions-delete` variant that asks for the name is still to do.
- **BTN-20** — A failed delete says so in a notification. — Carbon common actions: "If the deletion fails, raise a notification to tell the user that deletion failed." — rule (a clause). — Done 2026-10-11 (rule).
- **BTN-21** — No dropdown opens inside another; a nested `ActionGroup` uses `dropdown(false)`. — Carbon disclosures: "Do not nest one disclosure within another disclosure"; Carbon popover: "Avoid nesting popovers". — rule + audit check + test check. — Done 2026-10-11 (rule, test check; nested menus also fail axe).
- **BTN-22** — A dropdown holding fields is at most 352px wide; anything wider is a modal. Low value. — Carbon popover: "If your popover exceeds four columns in width, use a modal instead". — rule; audit possible (`getFiltersFormWidth()`). — Done 2026-10-11 (rule).
- **BTN-23** — No critical information and no required input in a dropdown; add this to the tooltip and collapsed-section rules. Low value. — Carbon disclosures: "Don't use to present critical information or request required input." — rule. — Done 2026-10-11 (rule).

### Forms and inputs

- **FRM-1** — Turn off the browser's own validation: add `novalidate` to the schema `Form` and to action modals, so our GOV.UK-worded errors show instead of browser popups. Check in a browser first. — GOV.UK validation: "Turn off HTML5 validation … add 'novalidate' to your form tags"; Carbon number input (a11y): "simply prevents the typing … without explanation". — house default. — Proposed.
- **FRM-2** — Amounts, quantities and costs are text inputs: use `->numeric()->type('text')` in the money, quantity, unit cost, line items, record form, payments and document totals stories. — Carbon number input: "avoid number input for continuous variables such as prices, distances, lengths"; GOV.UK: "Do not use `<input type="number">`". — story fix + rule + test check + audit check. — Proposed.
- **FRM-3** — State limits before the user types: a field's range or decimal places, and a text field's maximum length ("Up to 500 characters"), go in its helper text. Fix `forms-quantity` and `forms-unit-cost`. — Carbon number input (a11y): "Notify users of allowable input ranges through helper text"; "warn the user in advance". — rule + story fix + test check + audit check. — Proposed.
- **FRM-4** — A new password's helper text states its minimum length (15 characters, under our NIST rule). — Carbon text input: "provide detailed helper text listing any requirements related to the data format". — rule; check by hand. — Proposed.
- **FRM-5** — A text area resizes only vertically (`resize: vertical`). Low value. — Carbon text input: "it only effects the height." — house CSS. — Proposed.
- **FRM-6** — Options are in alphabetical or increasing order; otherwise the most-used come first. Add this to the `forms-choice` description. — Carbon select: "the list should be in alphabetical order or in increasing order relative to the content". — rule. — Proposed.
- **FRM-7** — Option labels (select, radio, checkbox) and checkbox group labels are three words or fewer, and the detail goes in `descriptions()`. Record names are exempt, and options have no icons. Story fixes: "Send invoice by email" → "Email the invoice"; `customerWithholding` options and its label "Withholding"; "WC158" in `supplierInvoice`. — Carbon dropdown: "Dropdown menu option text should not exceed three words"; Carbon checkbox: "should not exceed three words". — rule + story fix + audit check (partly). — Proposed.
- **FRM-8** — An optional radio group has a "None" option, or is required. Fix `forms-choice.fewOptions` and `forms-record-form` `payment_terms`. — Carbon radio button: "consider adding alternatives such as an 'other' or 'none' option"; Carbon forms: "provide a radio button with the label 'None'." — rule + story fix + test check + audit check. — Proposed.
- **FRM-9** — Radio and checkbox groups stack vertically: no `inline()`, and no multi-column layout unless the options are short and few. — Carbon checkbox and forms: "arrange the checkbox and radio button groups vertically for easier reading." — rule + test check + audit check. — Proposed.
- **FRM-10** **[Owner]** — Pick one required/optional convention per app. Filament's "*" plus our "(optional)" marks both groups. The options:
  - (a) a house default `markAsRequired(false)`
  - (b) patterns mark "(optional)"
  - (c) long forms start with "All fields are required unless marked as optional".

  Also fix `forms-record-form`: two optional fields aren't marked, and with only 2 of 5 fields required, its required fields should say "(required)". — Carbon form: "The pattern used should be consistent throughout your product"; "All fields are required unless marked as optional". — owner decision + story fix + audit check. — Decided by the owner 2026-10-11: mark optional fields "(optional)" and drop the asterisk (with FRM-11). To do in the forms batch.
- **FRM-11** — If FRM-10 removes the asterisk, required `Radio` and `CheckboxList` groups get `aria-required` (Filament renders neither `required` nor `aria-required` on them). This is an accessibility fix that depends on FRM-10. — Carbon radio button (a11y): "Required radio button groups must be identified programmatically, either via the label or with aria-required." — house default + test check. — Proposed.
- **FRM-12** — A `ToggleColumn` shows a tick when on (`onIcon(Heroicon::Check)`), so on and off don't differ by colour alone. — Carbon toggle (a11y): "Carbon adds a tickmark to the toggle's 'on' state" so state is clear "without relying on use of color". — house default + test check. — Proposed.
- **FRM-13** — A toggle's label names the setting and never changes with its state. Low value. — Carbon toggle: "The label's text should not change based on its state". — rule. — Proposed.
- **FRM-14** — No `Slider` for amounts, dates or wide ranges; where a slider is used, it is paired with a typed field. Low priority. — Carbon slider: "Do not use for extremely large ranges"; (a11y) "Users can also directly enter a value". — rule + audit check. — Proposed.
- **FRM-15** — A date range has two labelled fields ("From" and "To"). Add a variant to `forms-date` or `tables-list-table`. — Carbon date picker: "Range inputs should be properly labeled with a start and end label." — rule + story fix + audit check (partly). — Proposed.
- **FRM-16** — A 12-hour time shows AM or PM in capitals, without full stops. Low value. — Carbon date picker: "Use uppercase letters and no periods for … AM and PM." — rule. — Proposed.
- **FRM-17** — An upload of several files (`multiple()`) goes in a slide-over or on a page, never in a modal. — Carbon file uploader: "Do not use upload in a modal when multiple files are uploaded". — rule + audit check. — Proposed.
- **FRM-18** — Upload errors say what to do ("Select a PDF or JPG", "Select a file smaller than 10 MB") instead of "File of invalid type". Whether FilePond's own labels can be overridden needs checking. — Carbon file uploader: "Error messages should provide clear guidance to help the user resolve the error." — house default + audit check. — Proposed.
- **FRM-19** — Forms have one column, with fields side by side only when they belong together. Filament forces `columns(2)` on modal forms opened from List, Edit and View pages. Cite Carbon in `forms-record-form`. — Carbon forms: "Carbon generally recommends single-column forms". — rule + house default (`columns(1)`) or pattern note + audit check. — Proposed.
- **FRM-20** — Save and Cancel stay at the bottom of a page form, never in the header. — Carbon forms: "Do not top align primary and secondary action buttons". — rule + audit check. — Proposed.
- **FRM-21** — The first field on a Create page gets `autofocus()`. Low value. — Carbon forms: "The first required input field in a form should receive focus on presentation." — story fix (pattern note). — Proposed.
- **FRM-22** — A long form with many optional fields groups them in their own section. Low value. — Carbon forms: "devoting an entire section to optional fields". — story fix (pattern note). — Proposed.
- **FRM-23** — An error message is at most two lines. — Carbon common actions: "For form errors, keep error messages no longer than two lines." — rule + test check (length heuristic). — Proposed.
- **FRM-24** — Warn before unsaved work is lost: house default `$panel->unsavedChangesAlerts()`, unless the app has set it. — Carbon global header: "If state or progress will be lost, inform users of this consequence"; Carbon common actions (Cancel). — house default + test check. — Proposed.
- **FRM-25** — A read-only input shows the arrow cursor, and its calendar indicator is dimmed. Low value. — Carbon read-only states: "The arrow cursor reinforces that a component is in a read-only state." — house CSS. — Proposed.
- **FRM-26** — A wizard step label is at most 16 characters, and a step's description only says that the step is optional. — Carbon progress indicator: "Labels should be one to two words only, with a limit of 16 characters total per label." — rule + test check. — Proposed.
- **FRM-27** — A failed sign-in says "Incorrect email or password. Try again." (override `auth/pages/login.messages.failed`). — Carbon login suggests: "Incorrect IBMid or password. Try again." — house default + test check. — Proposed.
- **FRM-28** — After a failed sign-in, clear the password field, and make clear what "Remember me" remembers. Low; a note only. — Carbon login: "the password field cleared"; "Clarify what is being remembered." — rule (note). — Proposed.

### Tables, lists and search

- **TBL-1** — Every table row highlights on hover, using the theme's own gray. Filament highlights only clickable rows. Check in a browser. — Carbon data table: "The data table's row hover state should always be enabled … even if the row is not interactive." — house CSS + rule. — Proposed.
- **TBL-2** — Tables show a skeleton while they reload: house default `Table::configureUsing(fn ($t) => $t->loadingSkeleton())`. — Carbon data table: "use skeleton states instead of spinners"; Carbon loading: "For full-screen loads, skeleton states are preferred over indicators". — house default + rule + test check. — Proposed.
- **TBL-3** — Audit the existing rule of at most five toolbar actions (page header + table header + toolbar, with an `ActionGroup` counted as one). — Carbon data table: "Include up to five actions within the table toolbar". — audit check. — Proposed.
- **TBL-4** — Audit a record `ActionGroup` holding fewer than three actions; those actions should be inline. — Carbon data table: "When the overflow menu contains fewer than three options, keep the actions inline". — audit check. — Proposed.
- **TBL-5** **[Owner]** — A table has three or more visible columns; with fewer, show a list of labels and values instead. Two-column related lists would warn, so the owner sees this first. — Carbon data table (specs): "Tables require three or more columns." — owner decision, then rule + audit check. — Decided by the owner 2026-10-11: adopt, as an audit warning. To do in the tables batch.
- **TBL-6** — Pagination shows the range and total ("Showing 1 to 10 of 50") at every width; Filament hides it below an 896px container. Check at 320px. — Carbon pagination: "the total number of items, items being displayed, and previous and next buttons remain." — house CSS. — Proposed.
- **TBL-7** — No table search on a list that stays short (a record's lines, a fixed lookup). Low value. — Carbon search: "Do not use when there is small or limited amounts of data." — rule. — Proposed.
- **TBL-8** — A filter's default is none or all of its options, never a subset. — Carbon filtering: "Filters within each category should start either as all unselected or all selected." — rule + audit check. — Proposed.
- **TBL-9** — Lists remember their search and sort for the session (`persistSearchInSession()`, `persistSortInSession()` in `tables-list-table`). — Carbon global header: "Maintaining these states and filters will bring the user back to where they were." — story fix + rule; corrects the Dropped entry "lists remembering their search and sort". — Proposed.
- **TBL-10** — The no-results empty state names the search ("No suppliers match 'abra'"). Low value. — Carbon search: "Always include the number of search results, including for searches with no results." — story fix (pattern note). — Proposed.
- **TBL-11** — Global search's placeholder says what it searches, as the table search's does; Filament's default is "Search". — Carbon search pattern (no separate quote; extends the existing table search rule). — rule + audit check. — Proposed.
- **TBL-12** — Only values (descriptions, long names, URLs) may be truncated; titles, labels, column headers, errors and notifications never are. — Carbon overflow content: "Truncation should not be used on page headers, titles, labels, error messages, validation messages, or notifications." — rule + test check. — Proposed.
- **TBL-13** — A long list in a cell uses `limitList()->expandableLimitedList()`, and long text gets "Show more" rather than a scroll box. — Carbon overflow content: "A 'Show more' button is used in place of scrolling, gradients, or fades." — rule. — Proposed.
- **TBL-14** — A badge's text is a few words, under 20 characters, because a truncated badge can't take focus to reveal it. — Carbon tag: "Tag titles should describe the tag in a few words or be under 20 characters when possible." — rule + test check. — Proposed.
- **TBL-15** — A badge isn't a link to another page. Low value. — Carbon tag: "Do not use tags as links that direct you to an entirely different page." — rule + audit check (optional). — Proposed.
- **TBL-16** — Show a badge only for a status the user acts on or scans for; other attributes are plain text. — Carbon status indicators: "Avoid using status indicators when no user action is required". — rule + audit check (heuristic: more than one badge column). — Proposed.
- **TBL-17** — Where statuses are combined (a summary badge), use the colour of the one that needs the most attention. Low value. — Carbon status indicators: "When multiple statuses are consolidated, use the highest-attention color." — rule. — Proposed.
- **TBL-18** — A multiple-choice filter has no select-all (`bulkToggleable()`). Low value. — Carbon dropdown: "not to use the parent checkbox in scenarios like filters when choosing all and choosing none means the same". — rule + audit check (possible). — Proposed.
- **TBL-19** — Row actions ("Edit", "View") name their record for screen readers, for example `aria-label` "Edit C-0001", starting with the visible label (WCAG 2.5.3). Medium effort; verify on a real table. — Carbon link: "Avoid using the same link text for different destinations on the same page". — house default + test check. — Proposed.
- **TBL-20** — Empty states are left-aligned as a block, text and buttons alike (Filament centres them). — Carbon empty states: "Empty state elements should be left-aligned as a block". — house CSS (or compact) + test check. — Proposed.

### Navigation, tabs and layout

- **NAV-1** — Menu items name places, not actions: no leading Create, New, Add, Edit, Manage, View, Import or Export. — Carbon UI shell header: "Avoid using verbs as header links". — rule + audit check. — Proposed.
- **NAV-2** — The brand name is the app's short name. Low value. — Carbon UI shell header: "The header name should be the name of the parent domain and be as brief as possible". — rule (note). — Proposed.
- **NAV-3** — A third level of navigation is tabs at the top of the page (`SubNavigationPosition::Top`), not Filament's side list. — Carbon UI shell left panel: "If you have additional content to display beneath a sub-menu, use tabs within the page." — rule + audit check. — Proposed.
- **NAV-4** — Optional note: the left panel suits more than five items; with fewer, a top bar is enough. All apps use the sidebar and break no rule. Low value. — Carbon UI shell left panel: "Use the left panel if there are more than five secondary navigation items". — rule (note). — Proposed.
- **NAV-5** — Breadcrumbs leave out the current page, and hide when only one item would remain (`house.css`). Caveat: on a View page, the record crumb links to the page itself. — Carbon breadcrumb: "By default, the current page is not listed in the breadcrumb trail." — house CSS + test check + audit check. — Proposed.
- **NAV-6** — Menu items are fixed pages, never generated from records. — Carbon global header: "Do not place content that has no upper limit (such as content created by users) within the shell's side navigation." — rule + audit check. — Proposed.
- **NAV-7** — Menu groups follow the users' tasks. — Carbon global header: "The navigation should reflect an appropriate domain model rather than an org chart." — rule (a clause). — Proposed.
- **NAV-8** — No record counts on menu items (`getNavigationBadge()`); a number badge is only for new items that clear once seen. — Carbon status indicators: "The badge with a number can only be used with the large icon button (48px) as global actions". — rule + audit check. — Proposed.
- **NAV-9** — List page and relation-manager tabs start on the left, not centred (`.fi-tabs:not(.fi-contained, .fi-vertical) { margin-inline: 0 }`). — Carbon content switcher: "should never be centered in the content space"; Carbon tabs: "The first label … align with the grid and the text below." — house CSS + test check. — Proposed.
- **NAV-10** — Reword the switcher rule so that a List page's `getTabs()` *is* the content switcher (never schema `Tabs`). Its labels are nouns of up to three words, one tab is always selected, and the default tab is the one most used. — Carbon content switcher: "Label text should be nouns or noun phrases … two to three words"; "there should always be one selected". — rule + audit check. — Proposed.
- **NAV-11** — Widen the tabs rule: no tabs for content users compare. — Carbon tabs: "Tabs should not be used if the user needs to compare information in different groups." — rule. — Proposed.
- **NAV-12** — A collapsible section starts collapsed; a section that should start open isn't collapsible. — Carbon accordion: "Accordions begin by default in the collapsed state with all content panels closed." — rule + audit check + test check. — Proposed.
- **NAV-13** — Keep Filament's light and dark modes and its theme switcher. Low to medium value. — Carbon color: changing "between a light or dark mode … is highly encouraged." — rule + audit check (`hasDarkMode()`). — Proposed.
- **NAV-14** — A brand logo with a background gets a dark-mode version (`darkModeBrandLogo()`) or a transparent background. Low value. — Carbon color: "Don't use images with colored backgrounds when switching between modes because they will appear broken". — rule + audit check. — Proposed.
- **NAV-15** — The 403 page says who to ask for access. — Carbon empty states: "Permissions issue … Suggest steps or process to request access." — rule (extends the error-page rule). — Proposed.

### Feedback and notifications

- **FDB-1** — A `Callout` that loads with the page is info or warning only; success and failure are toasts. — Carbon notification: "The callout variant only includes informational and warning statuses"; Carbon notifications: "they do not include success or error statuses". — rule + test check + audit check. — Done 2026-10-11 (rule, test check): never success or danger, as Carbon says.
  - *The batches disagree:* batch d would allow a success or danger `Callout` that is announced (`role="status"`/`alert`); batch e says never.
- **FDB-2** — A callout sits next to what it concerns, above the button or input it affects, and a form-level error sits just above the form's buttons. Use callouts sparingly and drop a redundant title. Fix the `NotificationStories::inline` description. — Carbon notification: "place the callout above the relevant button or input"; "Place inline notifications at the bottom of forms, just above the submission and cancel buttons". — rule + story fix. — Done 2026-10-11 (rule, `feedback-notifications` description).
- **FDB-3** — A toast fits in two lines at 288px; a longer message gets one "View more" action. — Carbon notification: "limit toast notifications to two lines of text"; Carbon notifications pattern: "messages should not exceed three lines". — rule + test check (length heuristic). — Done 2026-10-11 (rule; two lines, from the component page).
  - *Carbon's own pages differ:* two lines on the component page, three on the pattern page.
- **FDB-4** — Don't send the same database notification again if the user hasn't acted on it. Low value. — Carbon notifications: "Don't send the same notification multiple times if users don't interact with it." — rule. — Done 2026-10-11 (rule).
- **FDB-5** — Any action that takes more than a few seconds runs queued and ends with a notification, as imports do. Low value. — Carbon loading pattern: "If the process is going to take more than a few minutes, consider including a notification." — rule. — Done 2026-10-11 (rule).

### Dashboards and charts

- **DSH-1** — Give each series in a multi-series chart its own colour from Filament's palette, meeting 3:1 in light and dark mode. Filament paints every dataset in the widget's one colour. Add a two-series story. — Carbon color palettes: "The sequence is carefully curated to maximize contrast between neighboring colors"; Carbon color: hard-coded values don't switch with mode. — story fix + audit check. — Proposed.
- **DSH-2** — Key figures sit in 2 or 4 columns, never thirds. `OrderStats` (3 stats) shows thirds, and a custom `Dashboard::getColumns()` mustn't be 3. — Carbon 2x grid: "encourages designers to divide by two"; "Choose a column count by starting with one, then divide by two as needed." — rule + story fix + audit check. — Proposed.
- **DSH-3** — A one-series chart hides its legend; Chart.js shows one by default. — Carbon legends: "Remove legends to simplify the chart when only one data category is needed or only one color is used." — audit check. — Proposed.
- **DSH-4** — A change in a key figure carries a +/− sign or an arrow (`descriptionIcon()`), optionally coloured green or red by sign, and always with words. Fix `OrderStats` ("3 more than last week"). — Carbon status indicators: differential indicators "must have either a '+' or '-' sign, a caret, or an arrow icon". — rule + story fix + test check; corrects the Dashboards rule ("Carbon sets no rule for up and down arrows"). — Proposed.
- **DSH-5** — A time axis lists every period in the range: 0 where nothing happened, `null` where the value is unknown. Fix the `OrdersPerMonthChart` comment. — Carbon axes and labels: "Never change axis ticks increments to accommodate data availability." — rule + story fix. — Proposed.
- **DSH-6** — A gap in the data names its start and end in the chart's description or text values. — Carbon axes and labels: "Always label both the start and end point during which data is not available." — rule. — Proposed.
- **DSH-7** — When an axis crosses into a new year, that label carries the year. Low value. — Carbon axes and labels: "Whenever data crosses into a new time cycle, such as a new day, month, or year, semibold the label". — rule. — Proposed.
- **DSH-8** — Build the story's month labels with `translatedFormat('F')` instead of hard-coding them in English. — Carbon axes and labels: "use localized date and time format". — story fix. — Proposed.
- **DSH-9** — No acronyms in chart titles, legends or axis labels. — Carbon legends: "avoid acronyms in legends. This also applies to titles and axis labels." — rule. — Proposed.
- **DSH-10** — A key figure's sparkline is decoration (Filament sets `aria-hidden`), so its trend is also in the description, in words. — Carbon simple charts (lists the sparkline; no usage quote). — rule + audit check (`getChart()` with no description). — Proposed.
- **DSH-11** — A filter that applies to several charts is a dashboard filter that every widget reads (`HasFiltersForm` + `InteractsWithPageFilters`), not a dropdown on each chart. — Carbon dashboards: "Mirror chart modifications like filter and zoom." — rule + story fix (pattern description). — Proposed.
- **DSH-12** — The most important widget comes first and also takes the widest span. — Carbon dashboards: "The most important data should have the highest contrast and occupy the largest area." — rule. — Proposed.
- **DSH-13** — On one dashboard, every chart puts its legend in the same place and uses one unit per measure. — Carbon dashboards: "have legends in the same position"; "Do not switch measurement systems". — rule; audit possible, low value. — Proposed.
- **DSH-14** — No gradient fills in charts. — Carbon color palettes: "Never use a gradient in place of a sequential palette." — rule; test possible, low value. — Proposed.
- **DSH-15** — The stats in one widget are either all linked or all unlinked. — Carbon tile: "Do not mix different variants of tiles in groups." — rule + audit check. — Proposed.
- **DSH-16** — Offer a Refresh button rather than polling (optional note on `dashboards-key-figures-and-charts`). — Carbon common actions: "Refresh … reloads the view … when the displayed view has become unsynchronized." — story fix (pattern note). — Proposed.

### Type, icons and spacing (compact)

- **CMP-1** — 16px icons in buttons, links, icon buttons, menu items, tabs and the section chevron; Filament's `md` is 20px. Check the Compact theme's negative margins on `.fi-icon-btn`. — Carbon button: "Use a 16px icon within buttons."; same in the link, menu, tabs and accordion specs. — compact CSS + test check. — Proposed.
- **CMP-2** — 20px icons where Filament uses 24px (top bar, toasts, callouts), and a 16px modal close icon. Add a sentence to the compact rule. — Carbon icons: "Be sure icon size is consistent throughout your product"; Carbon notification: "16px and 20px icons are optimized … paired with 14px". — compact CSS + rule + test check. — Proposed.
- **CMP-3** — Menus are sized to their content, between 160px and 288px wide, instead of a fixed 224px. — Carbon menu buttons (spec: minimum 160px, maximum 288px). — compact CSS. — Proposed.
- **CMP-4** — Menu items and sidebar labels wrap instead of truncating. Filament truncates them with no way to reveal the text. Look at it in a browser. — Carbon overflow content: "Truncation should not be used on … labels"; Carbon menu (title tooltip on hover, which our truncation rule doesn't accept). — compact CSS + test check. — Proposed.
- **CMP-5** — Dropdowns sit 4px from their trigger (`ActionGroup` `dropdownOffset(4)`); filter dropdown padding is 16px if a selector is easy. — Carbon popover (spec: 4px gap, 16px padding). — compact CSS. — Proposed.
- **CMP-6** — Tooltips are at most 288px wide with 2px block padding, and sit 4px from their trigger if that can be set globally (not verified). — Carbon tooltip (spec: 288px multi-line, 2px block padding, 4px gap). — compact CSS. — Proposed.
- **CMP-7** — Pagination text and page numbers are 400; the current page is 600. — Carbon pagination (spec: 14px regular; selected page 600). — compact CSS + test check. — Proposed.
- **CMP-8** — Bring the wide pagination strip down from about 36px to 32px. Check in a browser. — Carbon data table (spec: small 32px). — compact CSS. — Proposed.
- **CMP-9** — The stat label is weight 400. — Carbon tile (spec: title 14px regular); Carbon typography (label-01, 12px regular). — compact CSS + test check. — Proposed.
  - *The batches disagree on size:* batch c keeps 14px (tile title); batch f adopts 12px (label-01). Both agree on 400.
- **CMP-10** **[Owner]** — Sweep the remaining `font-medium` (500) selectors to 400 or 600, by role. Whether the page title's `font-bold` (700) goes is the owner's call. — Carbon typography: "We suggest using IBM Plex Light, Regular, and SemiBold for digital experiences." — compact CSS + test check + owner decision. — Decided by the owner 2026-10-11: the page title becomes semibold (600). To do in the compact batch.
- **CMP-11** — The current menu item is semibold. — Carbon UI shell left panel (a11y): "The link becomes bold to show it is the current page." — compact CSS + test check. — Proposed.
- **CMP-12** — Menu items sit 16px from the panel edge, with no gap between groups. — Carbon UI shell left panel (spec: 16px link padding; sub-menus stacked with no gap). — compact CSS. — Proposed.
- **CMP-13** — Toasts stack 8px apart. — Carbon notification: "stack with $spacing-03". — compact CSS. — Proposed.
- **CMP-14** — Breadcrumb items are 4px apart. — Carbon breadcrumb (spec: `$spacing-02`, 4px). — compact CSS. — Proposed.
- **CMP-15** **[Owner]** — A text brand name is 14px semibold; an image logo keeps `brandLogoHeight()`. Someone could read this as branding. — Carbon UI shell header (spec: product name `$heading-compact-01`, 14px semibold). — compact CSS + owner decision. — Decided by the owner 2026-10-11: a text brand name is 14px semibold. To do in the compact batch.
- **CMP-16** — Off-scale 6px gaps become 8px: stat label to value, stat icon to label, and infolist entry label to value. — Carbon spacing: "deviating from the spacing scales should be avoided whenever possible". — compact CSS. — Proposed.
- **CMP-17** — Disabled buttons show `cursor: not-allowed`. Low value. — Carbon disabled states (spec: `cursor: not-allowed`). — compact CSS. — Proposed.
- **CMP-18** — Italic is only for titles of works and technical terms, never a whole value or column. Low value. — Carbon typography: italic "should only be used when you need to emphasize certain words in a sentence". — rule; test possible. — Proposed.

### Accessibility bugs

- **A11Y-1** **[WCAG 2.1.1]** — No `hintIcon(tooltip:)` and no `tooltip()` on a plain column or entry: Filament shows these on hover only, to sighted mouse users. Put the text in `helperText()`, the column's description, or behind a `hintAction()` button. Add it to `storybook-instructions`. — Carbon form (a11y): "The toggletip is a button, which is in the tab order."; Carbon tooltip (a11y): "Users can navigate to the UI trigger and display the tooltip by pressing Tab." — rule + test check + audit check. — Done 2026-10-11 (rule, test and audit checks).
- **A11Y-2** **[WCAG 2.1.1]** — `copyable()` is a click on a `<span>`: it can't be reached by keyboard, and "Copied" isn't announced. Give `records-paper-receipt` labelled copy buttons (or add a house script with `tabindex`, `role="button"` and Enter/Space), and say "Copied to clipboard". — Carbon code snippet (a11y): "By default, each inline code snippet is reachable by Tab and copied with Space or Enter". — story fix + rule + test check + audit check. — Done 2026-10-11 (house script: copyable values are buttons; copying is announced).
- **A11Y-3** **[WCAG 1.4.1, if contrast is under 3:1]** — Underline links inside running text (modal descriptions, toast bodies, callouts, schema text), such as the import example link. — Carbon link: "Inline: Used within a sentence or paragraph and is styled with an underline." — house CSS + rule + test check + audit check (axe `link-in-text-block`). — Done 2026-10-11 (house CSS).
- **A11Y-4** **[WCAG 4.1.3]** — Announce a table's result count after search and filter changes in a polite live region; only the empty state is announced today. — Carbon search (a11y): "Search outcomes and status (e.g., '25 results found', 'no results found')". — house default + test check. — Not a gap: Filament already announces the result count in a hidden `role="status"` region (tables `index.blade.php`).
  - *The batches disagree:* batch c says Filament already announces result counts in a live region; batch b says `.fi-pagination-overview` isn't one. Check with a screen reader first.
- **A11Y-5** **[WCAG 2.1.1] [Owner]** — The Chart.js legend toggles a series on click, works by mouse only, and hides that series rather than isolating it. Either make the legend not clickable (`'onClick' => null`, and verify in a browser) and filter with a control, or accept the toggle as a non-essential extra. — Carbon legends: "Clicking on the legend of one category isolates the information, hiding all other categories." — owner decision, then rule + story fix. — Done 2026-10-11: owner chose a legend that is not clickable (rule and audit check).

### Rulebook corrections

These are text changes to `docs/rules.md`. BTN-1 (the Dropped entry "Save button on the right"), TBL-9 (the Dropped entry on search and sort) and DSH-4 (no Carbon rule for arrows) also correct it.

- **RUL-1** — Replace "Carbon gives no guidance on pie and donut charts": each slice's label gives its percentage ("Paid 62%"), and the text values list every slice, including ones too small to draw. — Carbon chart anatomy: "In a circular chart, labels offer the percentage value of the whole for an individual category." — rule. — Done 2026-10-11.
- **RUL-2** **[Owner]** — "Log in" is on a live page, so the Not-adopted reason "archived list only" is out of date. Either adopt it (a translation override for the heading and button, with "Log out" in the user menu) or give the real reason. — Carbon login: "The title should include the words 'Log in' rather than 'Sign in' or another variant." — owner decision; test check if adopted. — Done 2026-10-11: owner adopted "Log in" and "Log out" (house default translations), since GOV.UK has no rule on the label.
- **RUL-3** — List "the sidebar never shrinks to icons only" under "Where we differ from Carbon", citing NN/g menu design (Jun 2024). — Carbon UI shell left panel offers a "Side rail" that expands on hover or focus. — rule. — Done 2026-10-11.
- **RUL-4** — Name Carbon's "MUST" in the existing entry on form spacing, which isn't adopted. — Carbon 2x grid: "Fixed components with labels such as input fields and dropdowns MUST use the wide gutter mode". — rule. — Done 2026-10-11.
- **RUL-5** — Add these items from "Not adopted" below to the rules' "Not adopted from Carbon":
  - 44px icon targets
  - icon contrast and colour
  - the modal heading's regular weight
  - inline notification for a server failure in a modal
  - arrow keys in tabs
  - the accordion toggle's name
  - FilePond's Remove button
  - breadcrumbs that never wrap
  - row actions in batch mode
  - Cancel while saving

  — Carbon icons, modal, tabs, accordion, file uploader, breadcrumb, data table, inline loading. — rule. — Done 2026-10-11.
- **RUL-6** — Check whether IBM Accessibility's public "Content design" pages (errors, labels, context) can be cited without a sign-in, to source writing rules that lost theirs when Carbon's content guidelines came down. This is a lead only, not fetched. — Carbon content (links to IBM Style and IBM Accessibility "Content design"). — rule (research). — Checked 2026-10-11: the IBM Accessibility content pages are public (ibm.com/able/toolkit/design/content). Accepted by the owner 2026-10-11 as the source for writing guidance Carbon took down; see RUL-7.
- **RUL-7** — Read IBM Accessibility's content design pages (errors, labels, context) and source the writing rules that lost theirs when Carbon's content guidelines came down (second person, active voice, no "please", abbreviations). — IBM Accessibility content design (ibm.com/able/toolkit/design/content). — rule. — Proposed.

## Not adopted

One line each, with the reason.

**Buttons, menus and dialogs**
- Button padding 16px/64px, label-to-icon gap of at least 16px, 16px between buttons (Button): bigger than Filament.
- Label left-aligned with the icon on its right; equal-width group buttons (Button): look.
- Fluid, hanging, grid-span, expressive, XL and 2XL buttons; 64px full-bleed modal buttons (Button, Modal, Dialogs, Forms): look, or bigger (already in the rules).
- "Do not use tertiary and danger tertiary buttons together" (Button): Carbon's combination table on the same page contradicts it.
- Combo (split) button (Menu buttons, Disclosures): Filament has none.
- Menu option padding of 16px (Menu): bigger.
- Context menus, shortcut display, menu items that can be selected (Menu): Filament's action menus don't offer them.
- 12px tooltip text for labels (Tooltip): Filament can't tell a label tooltip from a body one; revisit after BTN-15.
- 16px inline tooltip padding (Tooltip): bigger.
- Toggletip component (Toggletip): needs new JavaScript.
- Arrow keys between tabs, `aria-controls` (Tabs): needs JavaScript in Filament; not a WCAG failure, since each tab is reachable. Possibly report upstream.
- Tab label padding of 16px, equal widths, contained and vertical tabs (Tabs, Content switcher): bigger, or look.
- Accordion header as one button named by its title (Accordion): needs a view override; Filament's toggle is named only "Collapse"/"Expand".
- Accordion 32px header, 400 title, 25% right margin (Accordion): a section is a card, not an accordion row; 400 conflicts with the type-set rule; the margin is look.
- Inline notification for a server failure in a modal (Modal): Filament has no inline slot, and its toast is announced (WCAG 4.1.3).

**Forms and inputs**
- Field padding (text 16px, text area 11px, select 16/48px, number 16px, dropdown 16/48/72px) (Text input, Select, Number input, Dropdown): bigger.
- Fluid inputs and forms (all inputs, Fluid styles): expressive, bigger, and the label sits inside the field.
- Error text replacing the helper text (Text input): GOV.UK keeps the hint beside the error.
- 20px radio (Radio button): bigger, and the label is part of the target.
- 48×24 toggle and small 32×16 toggle (Toggle): look; the small one is below WCAG 2.5.8's 24px.
- On/Off text beside a toggle (Toggle): Filament has none; FRM-12's tick is Carbon's alternative.
- Arrow-key stepping, lost with `type('text')` (Number input): acceptable, since Carbon steers prices away from number input.
- Count tag, and selected options moving to the top (Dropdown): Filament's component internals.
- Form spacing 32/48px, 32px two-column gutter, 28px form heading (Form, Forms, 2x Grid): owner decision 2026-10-11; bigger.
- Date format in helper text (Date picker): the native input shows its own format.
- File name in FilePond's Remove button (File uploader): FilePond internals.
- Form-level error summary for server-side validation (Forms): Filament can't add it app-wide (a danger `Callout` via `onValidationError()` on long page forms is possible, low value).
- Disabled opacity of 50% and 25% (Disabled states): look.
- Email-first login, SSO, split-screen layout (Login): no SSO; marketing.

**Tables and lists**
- Column padding 16px, sort-icon gap 8px, 20px selection box, 8px between tags (Data table, Tag): bigger.
- Table title 20px regular (Data table): bigger; dialog and section headings keep the theme's sizes.
- Row actions disabled while rows are selected (Data table): Filament has no setting, and CSS alone leaves them working from the keyboard.
- Sort arrows only on hover (Data table): look; Carbon admits "there is no persistent visual indicator".
- Search on the left, stretching to the actions (Data table): a layout change of little value.
- Column titles wrapping to two lines (Data table): Filament scrolls the table instead.
- Structured list 60/36px rows and 500px minimum width (Structured list): bigger; the minimum width conflicts with WCAG 1.4.10.
- 32px search field (Search): we keep 28px inside the 32px toolbar strip (compact tables decision).
- Disabling Cancel while saving; "Saving…" button label (Inline loading): Filament has no setting.
- Mid-line truncation (Overflow content): needs JavaScript.
- Breadcrumbs that never wrap, with an overflow menu (Breadcrumb): Filament has no overflow menu, and our truncation rule needs a reveal.
- "/" breadcrumb separator (Breadcrumb): look.

**Shell and foundations**
- 48×48 header buttons; no gap between header icons (UI shell header): bigger; with Filament's 32–36px buttons, no gap would leave small targets touching.
- 256px right panel (UI shell right panel): notifications would wrap badly.
- 32px sub-item indent; 4px selected border (UI shell left panel): bigger; look.
- Modal heading 20px regular (Modal): the size isn't adopted, and regular at 16px loses the heading's emphasis.
- Modal content-to-footer 48px, 20% body margin, 48px close button, no gap between footer buttons (Modal): bigger, or tied to the full-bleed buttons.
- Toast bottom padding 24px, 48px close button, callout margins and icon gap (Notification): bigger.
- 44px icon touch targets (Icons): Carbon's own small components are 32px and 24px; we keep WCAG 2.5.8's 24px.
- Icon contrast of 4.5:1, and icons matching the text colour (Icons): icon colour is the theme's; the text beside the icon carries the meaning.
- Motion durations and easing (Motion): look; Filament's values are within Carbon's range.
- Stat value size (Typography): the rules keep the theme's size for key figures.
- Letter spacing of .16/.32px (Type sets): tuned for IBM Plex.
- 12px captions for chart and stat descriptions (Type sets): not proposed.
- Carbon breakpoints and page margins (2x Grid): Filament is built on Tailwind's; no clean mapping.
- Condensed 1px widget gutter (2x Grid): look.

**Charts**
- Annotations, zoom, axis breaks (Dashboards, Axes and labels, Chart anatomy): Chart.js needs plugins.
- Legend hover dimming to 30% (Legends): needs `RawJs`; only if A11Y-5 keeps the legend interactive.
- IBM palette colour values (Color palettes): look.
- No drop shadow on tiles (Tile): look.
- Scatter and bubble purposes (Simple charts): add only if a pattern uses them.
- Vertical progress indicator (Progress indicator): Filament's wizard has no vertical header.
- Orange and purple statuses (Status indicators): no use yet.

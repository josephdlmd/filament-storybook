# Writing Filament stories

A story is one approved way to configure a Filament component. Agents copy it, so it must be correct and complete.

## Before you write
- Call `storybook-list`. If a pattern already covers the need, reuse it; do not add a near-duplicate.
- Check the method signatures in `storybook-show`'s API section. Never guess an argument.

## Choosing the component
- Pick the component by the job, with the rules: up to five choices are radios and more are a searchable select, a yes or no saved with the form is a checkbox, a state the user acts on is a badge with its word.
- Find each component you use in the coverage table below ("Filament coverage") and follow what its row names. **Pattern:** copy that pattern's configuration. **Rule:** apply the rule. **General:** only the general rules named there apply; say so to the user. **Not applicable:** don't use it.
- When no row covers what you need, or a rule would be a guess, say so instead of inventing one. A UI rule needs a current source; a business fact comes from the owner.

## Fit the other patterns
A new story is one more screen in the same app, so it agrees with the patterns already there:
- The same field has the same name, label and width as in every other pattern (a customer picker is `forms-record-picker`; an amount is `forms-money-input`).
- Sample data uses the same records and formats: the example customers and suppliers, PO-000042 for a numbered document and 001234 for a form's serial, ₱ amounts.
- One status field per kind of state: a document's status (Draft, For approval, Posted, Void) and its payment status are separate fields and columns, each with the colours `tables-status-badge` gives.
- Toasts name the record ("Invoice 001234 voided"), and a modal's title and confirm button repeat the label of the button that opened it.
- A screen built from several components keeps the theme's spacing between them; never add margins to make them fit.

## The class
- Extend `Josephdlmd\FilamentStorybook\Stories` in `app/Stories/<Name>Stories.php`.
- `title()` is `Group/Name`, for example `Forms/Money input` or `Tables/Status badge`.
- `description()` says when to use the pattern and when not to, in one or two sentences. Wrap code in backticks.
- Declare the return type of every story: a schema component (field, entry, layout), a table column, or an action.
- A whole screen is a story too: `public function default(Table $table): Table` or `public function default(Schema $schema): Schema`, returning exactly what the resource's `configure()` would. Fill it with the field patterns, so the two never disagree.

## The stories
- One public method per state the pattern really has: `default`, then variants such as `required`, `disabled`, `withHelperText`, `manyOptions`. Name them by scenario, in camelCase.
- Put a one-line docblock on every variant saying what is different and why.
- Columns and entries need sample data. Return rows from `records()`, keyed by record id, covering the edge cases: a negative amount, an empty value, a long label.
- The canvas gives every story an unsaved record made from the first sample row, so a condition on `$record` (`fn (?Model $record): bool => …`) works as it does in a resource.
- In a table story, type the record in a row action's closures nullable, `fn (?Order $record)`: a table calls them without a record while it has no rows.
- A pattern that needs a class besides its stories (an importer, a widget) lists it in `examples()`; `storybook-show` prints its source to copy.
- Keep stories pure. Do not query the database, read the session or call external services; sample data goes in `records()`. A field on a relationship, such as a customer select, needs the record's real model: return its class from `model()`, as `forms-record-picker` does.
- Use realistic content from a real task ("Order PO-000042", "Abra Medical Supply"), never lorem ipsum or "Option 1", "Section 1": placeholder wording can make a pattern look clear when it isn't. One component per story, so the snippet copies cleanly; put variants in their own stories.
- Use the field names and labels a real screen would use, in sentence case, so the snippet pastes into a resource unchanged. A button's label is a verb ("Approve order"), never OK, Done or Submit.

## Type, spacing and colour
- Never set a size, weight, colour, padding or margin yourself, by class or style in `extra*Attributes()`: the theme and `compact()` give each kind of text and each gap its size (the type table in the rules).
- Emphasise a value with `->weight(FontWeight::SemiBold)`, such as a total; tone down secondary text with `->color('gray')`. Weight comes before size.
- A heading comes from its component (`Section::make('Details')`), never from styled text.

## House rules
The full rules, each with its source, follow these instructions. `storybook-test` and `storybook-audit` warn when one is broken. A rule is kept only when current, maintained guidance supports it.

## Verify
- Run `storybook-test` with the pattern's id. Fix every failure, warning and accessibility issue, then run it again. Repeat until it reports none.
- `storybook-test` checks components, not how they sit together. If you can view pages, open the preview link from `storybook-show` and look at every part of the screen, not only your component: each strip of a table (filters, toolbar, header, rows, pagination), the gaps between fields, and anything that sits alone in a row, overlaps or touches a border. Open every modal and slide-over the story has.
- Then give the user the preview link.

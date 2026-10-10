# Writing Filament stories

A story is one approved way to configure a Filament component. Agents copy it, so it must be correct and complete.

## Before you write
- Call `storybook-list`. If a pattern already covers the need, reuse it; do not add a near-duplicate.
- Check the method signatures in `storybook-show`'s API section. Never guess an argument.

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
- Keep stories pure. Do not query the database, read the session or call external services; sample data goes in `records()`.
- Use the field names and labels a real screen would use, so the snippet pastes into a resource unchanged.

## House rules
`storybook-test` and `storybook-audit` warn when one is broken. Every rule rests on current, maintained guidance; nothing is here because it is merely common or preferred.
- Labels stay visible, above their fields; no `hiddenLabel()`. (W3C WAI forms tutorial 2024, GOV.UK text input)
- No `placeholder()`: an example or format goes in `helperText()`, worded "For example, …". (USWDS text input, GOV.UK text input)
- A value with a known shape, like a TIN, is one field that accepts spaces, dashes and dots and stores one format. No input mask: USWDS's own mask failed WCAG 2.1 AA. (GOV.UK National Insurance number pattern, USWDS input mask)
- Each input is about as wide as its answer. (USWDS text input, GOV.UK text input)
- Few options are radios; only a long list goes in a searchable select. (GOV.UK select and radios)
- Colour always comes with a word or an icon: gray not started, warning waiting, success done, danger critical. (WCAG 2.2 1.4.1, Carbon status indicators)
- Tables put a readable identifier first and order the other columns by importance. Numbers and money are right-aligned. (NN/g data tables 2022, GOV.UK table, USWDS table)

## Verify
- Run `storybook-test` with the pattern's id. Fix every failure, warning and accessibility issue, then run it again. Repeat until it reports none.
- Then give the user the preview link from `storybook-show`.

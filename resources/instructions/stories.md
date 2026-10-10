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
- The canvas gives every story an unsaved record made from the first sample row, so a condition on `$record` (`fn (?Model $record): bool => …`) works as it does in a resource.
- In a table story, type the record in a row action's closures nullable, `fn (?Order $record)`: a table calls them without a record while it has no rows.
- A pattern that needs a class besides its stories (an importer, a widget) lists it in `examples()`; `storybook-show` prints its source to copy.
- Keep stories pure. Do not query the database, read the session or call external services; sample data goes in `records()`.
- Use realistic content from a real task ("Order 1042", "Abra Medical Supply"), never lorem ipsum or "Option 1", "Section 1": placeholder wording can make a pattern look clear when it isn't. One component per story, so the snippet copies cleanly; put variants in their own stories.
- Use the field names and labels a real screen would use, in sentence case, so the snippet pastes into a resource unchanged. A button's label is a verb ("Approve order"), never OK, Done or Submit.

## House rules
The full rules, each with its source, follow these instructions. `storybook-test` and `storybook-audit` warn when one is broken. A rule is kept only when current, maintained guidance supports it.

## Verify
- Run `storybook-test` with the pattern's id. Fix every failure, warning and accessibility issue, then run it again. Repeat until it reports none.
- Then give the user the preview link from `storybook-show`.

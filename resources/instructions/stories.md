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

## The stories
- One public method per state the pattern really has: `default`, then variants such as `required`, `disabled`, `withHelperText`, `manyOptions`. Name them by scenario, in camelCase.
- Put a one-line docblock on every variant saying what is different and why.
- Columns and entries need sample data. Return rows from `records()`, keyed by record id, covering the edge cases: a negative amount, an empty value, a long label.
- Keep stories pure. Do not query the database, read the session or call external services; sample data goes in `records()`.
- Use the field names and labels a real screen would use, so the snippet pastes into a resource unchanged.

## House rules
`storybook-test` warns when a story breaks one of these.
- Format examples never go in `placeholder()`. A value with a fixed shape gets `mask()`; otherwise put one example in a one-line `helperText()`.
- Every field keeps a visible label; no `hiddenLabel()`. Colour always comes with a word or an icon.
- Money is `money('PHP')`, right-aligned, with decimals kept; negatives by sign, never by colour.

## Verify
- Run `storybook-test` with the pattern's id. Fix every failure, warning and accessibility issue, then run it again. Repeat until it reports none.
- Then give the user the preview link from `storybook-show`.

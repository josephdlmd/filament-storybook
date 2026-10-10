## Filament Storybook (josephdlmd/filament-storybook)

This app's approved UI patterns are PHP stories: classes extending `Josephdlmd\FilamentStorybook\Stories` in `app/Stories` (app patterns) and in the package (shared patterns). Each public method returns one configured Filament component.

- **Never guess Filament UI:** before writing any resource table or form, field, column, infolist entry, action or notification, call `storybook-list`, then `storybook-show` for each matching pattern. Screen patterns (those that take a `Table` or `Schema`, such as `Tables/List table`, `Forms/Record form` and `Records/View page`) set the layout; field patterns fill it. Copy its snippet and imports, change only field names and labels, and pass only the arguments its API section shows. Do not read vendor source to answer this.
- **No pattern fits:** say so, build it, and add a story for it in `app/Stories` in the same change. Call `storybook-instructions` first.
- **Changing a shared standard** (a shared pattern or a rule in `docs/rules.md`): change the josephdlmd/filament-storybook repo and release it, never `vendor/`. The owner can add standards; UI rules need a current source, business facts come from the owner.
- **Self-check:** after building or changing a resource screen, run `storybook-audit` with that resource; after adding or changing a story, run `storybook-test`. Fix every failure, warning and accessibility issue, and run it again until none remain.

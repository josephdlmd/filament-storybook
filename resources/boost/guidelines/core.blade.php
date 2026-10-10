## Filament Storybook (josephdlmd/filament-storybook)

This app's approved UI patterns are PHP stories: classes extending `Josephdlmd\FilamentStorybook\Stories` in `app/Stories` (app patterns) and in the package (shared patterns). Each public method returns one configured Filament component.

- **Never guess Filament UI:** before writing any resource table or form, field, column, infolist entry or action, call `storybook-list`, then `storybook-show` for each matching pattern. Screen patterns (`Tables/…`, `Forms/…` that take a `Table` or `Schema`) set the layout; field patterns fill it. Copy its snippet and imports, change only field names and labels, and pass only the arguments its API section shows. Do not read vendor source to answer this.
- **No pattern fits:** say so, build it, and add a story for it in `app/Stories` in the same change. Call `storybook-instructions` first.
- **Self-check:** after adding or changing a story, or a component or enum a story uses, run `storybook-test`. Fix every failure and run it again until all pass, then give the user the preview link from `storybook-show`.

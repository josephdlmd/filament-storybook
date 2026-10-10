## Filament Storybook (josephdlmd/filament-storybook)

This app's approved UI patterns are PHP stories: classes extending `Josephdlmd\FilamentStorybook\Stories` in `app/Stories` (app patterns) and in the package (DL Group patterns). Each public method returns one configured Filament component.

- **Check before building:** before writing any Filament form field, table column, infolist entry or action, call `storybook-list`, then `storybook-show` for each matching pattern. Copy its snippet and imports, and only change the field name and labels.
- **Never guess:** use only methods a story shows. If no story covers what you need, say so, build it, and add a story for it in `app/Stories` in the same change, so the next screen reuses it.
- **Writing a story:** declare the return type (a schema component, table column or action); put when-to-use in `description()` and per-variant notes in the method's docblock; give column and entry stories sample data in `records()`, keyed by record id. Story names are camelCase verbs or variants (`default`, `required`, `fewOptions`).
- **Verify:** run the story render test (`php artisan test --filter=stories`) after adding or changing a story, and open the Storybook page in the panel to look at it.

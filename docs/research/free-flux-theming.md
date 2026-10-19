# Can free Flux take the locked tokens and Carbon compact density?

Research for issue #4 (map #3). Date: 2026-10-19.

Versions read:
- **livewire/flux v2.20.1** (latest on Packagist, released 2026-09-28; source `github.com/livewire/flux` at `ee9ad7f`). File paths below are inside that package.
- **filament/filament and filament/support v5.10.1** (this repo's `vendor/`).
- fluxui.dev pages fetched 2026-10-19: [theming](https://fluxui.dev/docs/theming), [customization](https://fluxui.dev/docs/customization), [components](https://fluxui.dev/components), [pricing](https://fluxui.dev/pricing).
- Filament docs: [styling overview](https://filamentphp.com/docs/5.x/styling/overview), [colors](https://filamentphp.com/docs/5.x/styling/colors).

## Verdict

**Yes for font, colours and radius. Partly for compact density.** Flux and Filament 5 both build on Tailwind CSS v4, so one shared CSS file of `@theme` variables, imported by the Filament panel theme and by the Flux app stylesheet, sets the font, the neutral and accent colours and the radius scale in both. Compact density is different: neither library has a density token. Flux sizes are fixed Tailwind utilities with a `size` prop (40px default, 32px `sm`, 24px `xs`). The package's `compact()` is a list of selector overrides on Filament's `fi-*` classes. A Flux equivalent would be a second override file on Flux's `data-flux-*` attributes, written from the same Carbon numbers, not shared with the Filament one.

## Theming: what each library exposes

| Token | Filament 5 | Free Flux 2.20 | Shareable? |
|---|---|---|---|
| Font | `->font('Inter')` on the panel; emitted as `--font-family` on `:root` (`filament/resources/views/components/layout/base.blade.php` l.85) | Tailwind's `font-sans` (`--font-sans`); README recommends Inter but sets nothing | Yes: set `--font-sans` in shared `@theme`, or point it at `--font-family`. Both default to Inter already. |
| Neutral | `gray` defaults to `Color::Zinc` (`support/src/Colors/ColorManager.php` l.18); emitted as `--gray-50…950` | Zinc is "hard-coded throughout Flux's source code"; swap by redefining `--color-zinc-50…950` in `@theme` ([theming](https://fluxui.dev/docs/theming)) | Yes. Same default (zinc). For another neutral, redefine `--color-zinc-*` in the Flux build to `var(--gray-*)`. |
| Primary / accent | `primary` palette, emitted as `--primary-50…950` | `--color-accent`, `--color-accent-content`, `--color-accent-foreground`, plus `.dark` overrides (`dist/flux.css` l.1–14; [theming](https://fluxui.dev/docs/theming)) | Yes: `--color-accent: var(--primary-600)` etc. Flux uses accent for primary buttons, links, tabs and nav items. |
| Danger / success / warning / info | Named palettes `--danger-*`, `--success-*`, `--warning-*`, `--info-*` (default Info = Blue) | No semantic tokens. Components hard-code Tailwind palettes: callout maps `success→green`, `danger→red`, `warning→yellow` (`callout/index.blade.php` l.21–25); toast icons use `lime`, `amber`, `rose` (`toast/index.blade.php` l.15–30); danger button uses `red` | Possible but messy: redefine `--color-red-*`, `--color-rose-*`, `--color-green-*`, `--color-lime-*`, `--color-yellow-*`, `--color-amber-*` (and `blue`/`sky` for info) to the Filament palettes, the documented zinc technique applied to more palettes. Side effect: every `red-*` utility in the app changes too. **Not tested in a build.** |
| Radius | Component CSS uses Tailwind `rounded-lg` / `rounded-md` via `@apply` (e.g. `support/resources/css/components/button.css` l.3, `input/wrapper.css` l.2) | Same Tailwind utilities in the Blade (`rounded-lg` default, `rounded-md` for `sm`/`xs`; `input/index.blade.php` l.96–98) | Yes: Tailwind v4 reads `--radius-md`, `--radius-lg` from `@theme`, so one value changes both. Registry scale `none/sm/md/lg` matches Tailwind's names. |
| Dark mode | `.dark` class on `html` | `.dark` class, via `@fluxAppearance` and `@custom-variant dark (&:where(.dark, .dark *))` (README) | Same mechanism. |

**How the values reach a Flux page.** `@filamentStyles` works in any Blade layout and outputs a CSS variable `--{name}-{shade}` for every colour registered with `FilamentColor` (`support/src/Assets/AssetManager.php` `renderStyles()`, l.279–307). A panel's `->colors()` applies only on that panel's own pages. To share colours with `/app`, register them app-wide with `FilamentColor::register()`, or hard-code the values in the shared CSS file. Font and radius need only the shared `@theme` file.

Two builds stay separate: the panel's `resources/css/filament/{panel}/theme.css` (from `make:filament-theme`) and the app's `resources/css/app.css` that imports `vendor/livewire/flux/dist/flux.css`. Each would `@import` the one tokens file.

## Compact density

Carbon's numbers can be matched in free Flux without publishing components:

| Carbon (from `resources/css/compact*.css` and `docs/rules.md`) | Free Flux | Gap |
|---|---|---|
| Small field / button, 32px (toolbars, line items) | `size="sm"`: `h-8` on input, select, button (`input/index.blade.php` l.97, `select/variants/default.blade.php` l.18, `button/index.blade.php` l.83) | None. A prop, not CSS. |
| Medium field, 40px | Default size `h-10` ("height of the input 40px", `input/index.blade.php` l.96) | None. **Note:** Filament's own inputs and buttons are 36px (`py-1.5`/`py-2` + 24/20px line), and `compact()` leaves panel fields at 36px. So "same values" can't mean identical heights unless one side is changed. |
| Menu item 32px | `sidebar/item.blade.php` l.36: `h-8` on desktop, `h-10` on mobile | Matches on desktop already. |
| Data-table row 40px (header matches) | `table/cell.blade.php` and `column.blade.php`: `py-3` + 20px text = 44px | Override needed: `[data-flux-cell]`/`[data-flux-column]` padding 10px. |
| Field label 12px regular, 8px above input; helper 4px below | `label.blade.php` l.15: `text-sm font-medium` (14px, 500) | Override on `[data-flux-label]`, `[data-flux-description]`, field gaps. |
| Weights 400/600 only (Carbon type) | `font-medium` (500) on labels, headings, buttons, table headers | Override per `data-flux-*` element, as `compact.css` does per `fi-*` class. |
| Tag (badge) 18px | Badge `sm`: `text-xs py-1` (24px) | Override. |
| Toast 288px wide | Not checked | **Unverified.** |

Hooks: Flux renders a `data-flux-*` attribute on each part (e.g. `data-flux-input`, `data-flux-label`, `data-flux-modal`), the counterpart of Filament's `fi-*` hook classes. Flux doesn't merge classes, and its docs recommend Tailwind's `!` or `php artisan flux:publish` for conflicts. Publishing copies the Blade into the app, which then stops getting updates ([customization](https://fluxui.dev/docs/customization)). A `flux-compact.css` on `data-flux-*` selectors avoids publishing, but it can break when Flux renames markup. It would need the same rename-guard test `compact.css` has (`tests/Feature/StorybookTest.php`).

## Coverage of the factory registry's 22 components

Free = in `livewire/flux` (the free package's `stubs/resources/views/flux` holds exactly the free set). Pro = listed on [pricing](https://fluxui.dev/pricing), absent from the free stubs.

| Registry component | Free Flux | Notes |
|---|---|---|
| page-header | `flux:heading` + `flux:subheading` + `flux:breadcrumbs` + buttons | Composition; `flux:header` is the app top bar, not a page header. |
| breadcrumbs | `flux:breadcrumbs` | Direct. |
| sidebar | `flux:sidebar` (+ `navlist`) | Direct. |
| form-section | `flux:fieldset` + `flux:legend`, or `flux:card` | Direct. |
| text-input | `flux:input` / `flux:field` | Direct. |
| select-input | `flux:select` (native variant only) | Searchable select, listbox, combobox are **Pro**. Free covers short option lists only. |
| date-input | `flux:input type="date"` (native browser picker) | Date picker and calendar are **Pro**. |
| amount-input | `flux:input` + `flux:input.group` prefix/suffix, `mask` prop | Free. Whether `mask` needs the Alpine mask plugin added separately: **unverified**. |
| data-table | `flux:table` (columns, rows, cells, sortable column) + `flux:pagination` | Free. No built-in filters, bulk selection or column picker: compose them. |
| filter-bar | none | Composition of `flux:input`, `flux:select`, `flux:button`, `flux:dropdown`. |
| status-indicator | `flux:badge` | Direct (colour via hard-coded palette names; see theming). |
| metric-card | `flux:card` + heading/text | Composition. |
| detail-panel | `flux:card`, or `flux:modal variant="flyout"` for a side panel | Direct. |
| approval-actions | `flux:button` group + `flux:modal` confirmation | Composition. |
| activity-feed | none | Timeline is **Pro** (listed on components page; not in free stubs). Compose from text and separators. |
| empty-state | none | Composition: icon, heading, text, button. |
| error-state | `flux:callout variant="danger"`, `flux:error` for fields | Page-level state is composition. |
| loading-state | `flux:skeleton` (+ group, line), button `loading` prop, `flux:progress` | Direct. |
| notification | `flux:toast` (+ `toast.group`), `flux:callout` for inline | Direct. |
| modal | `flux:modal` | Direct. |
| chart | none | Chart is **Pro**. |
| attachment-field | `flux:input type="file"` (`input/file.blade.php`) | File upload (drop zone, previews) is **Pro**. Free is a styled native file input. |

Summary: 13 have a direct free component, 7 are compositions of free parts, and 1 has only a basic free fallback (attachment-field: native file input). **Only `chart` has no free equivalent at all.** Pro-only upgrades that the free set lacks: searchable select, date picker, rich file upload, timeline, and charts.

## Not verified

- No build was run. The palette remapping (`--color-red-*` → `var(--danger-*)`) and the shared `@theme` file are reasoned from the Tailwind v4 variable model and Flux's documented zinc technique, not tested.
- The pricing page claims "23+ Pro components" but names 16. The free/Pro split here comes from the free package's contents, not from a complete Pro list.
- Flux toast width, modal padding and tooltip sizes weren't measured against Carbon.
- The free table's keyboard and screen-reader behaviour weren't checked with `storybook-test`.

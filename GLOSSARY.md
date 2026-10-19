# Filament Storybook

The company design system: approved UI patterns, the rules behind them and the checks that hold apps to them, for Filament panels and Livewire + Flux screens.

## Language

**Look**:
The visual identity every app shares: colours, font, corner radius and icons. An owner's business decision, not a UI rule; an app sets only its name, logo and favicon.
_Avoid_: Theme (that is Filament's CSS mechanism), brand, skin

**Compact**:
Carbon's small sizes, spacing and text weights, the only density an app has. It is the data-dense principle and its limit: nothing denser than Carbon specifies.
_Avoid_: Compact mode, dense mode, data-dense theme

**House defaults**:
Filament defaults the package corrects app-wide because they break a rule.
_Avoid_: Overrides, tweaks

**App screen**:
A Livewire + Flux screen outside a panel, under `/app`. The same rules apply as in a panel; only the components differ.
_Avoid_: Front end, Flux page, public page

**Rule**:
A UI requirement with a current source under the rules policy.
_Avoid_: Guideline, preference, standard (a standard may also be a business fact)

**Business standard**:
A fact the owner states about the business (a format, a term, a convention) that patterns build in without a source.
_Avoid_: House rule

**PH add-on**:
The separate package of Philippine accounting patterns (BIR forms, withholding, VAT, TIN). Core holds UI patterns and accounting patterns any country could use.
_Avoid_: PH module, localisation, plugin

**Pattern**:
An approved way to build one piece of UI or one screen, with the rules behind it. A pattern is shown by its stories.
_Avoid_: Component (that is Filament's or Flux's building block), template, recipe

**Story**:
One rendering of a pattern, or of an app's own screen, in the gallery. Shared stories come with the package; an app's stories stay in that app and only combine approved parts.
_Avoid_: Example, demo

**Snippet**:
The code of a story that an agent copies into a screen and configures. It sets up native components and copies no component code, so a screen built from it still uses the design system by version.
_Avoid_: Template, boilerplate

**Extension proposal**:
A request to add something the design system lacks (a component, a pattern, a change to a rule). The owner approves it, and a release ships it before any app uses it.
_Avoid_: Feature request, exception

**Allowlist**:
What one release of the design system approves: its patterns and stories, the frontend and states each story shows, and the native components apps may use. It names no factory ids.
_Avoid_: Registry, catalogue, manifest

**Design registry**:
The factory's list of layout and component ids that a screen spec may name, with the story each id stands for on each frontend. The factory owns it; the allowlist is what it is checked against.
_Avoid_: Allowlist, component list

**Screenshot capture**:
Pictures of every story in each state it shows, light and dark, at desktop and phone width, with a list of what each picture is. Storybook takes them; it never compares them.
_Avoid_: Snapshot, baseline

**Baseline**:
An approved screenshot the factory keeps and compares a new capture against. Only a person approves a change to one.
_Avoid_: Snapshot, golden image

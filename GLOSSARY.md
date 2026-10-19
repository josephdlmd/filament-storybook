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

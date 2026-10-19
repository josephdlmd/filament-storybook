# Filament Storybook

The company design system: approved UI patterns, the rules behind them and the checks that hold apps to them, for Filament apps. Every UI is a Filament panel: no Flux, no TallStackUI.

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

**Panel**:
One area of an app at its own address, with its own sign-in, users and menu. An app has one panel for its staff, divided into departments by menu groups and roles, and a separate panel only for outside users or a really different way of working. Every panel has the same Look and the same rules.
_Avoid_: Portal, admin, backend, External UI

**Outside user**:
A person outside the company who signs in to a separate panel on behalf of exactly one customer or supplier, and sees only that record's documents. Staff invite them; nobody signs themselves up. One customer or supplier can have several.
_Avoid_: Portal user, client user, guest

**Rule**:
A UI requirement with a current source under the rules policy.
_Avoid_: Guideline, preference, standard (a standard may also be a business fact)

**Business standard**:
A fact the owner states about the business (a format, a term, a convention) that patterns build in without a source.
_Avoid_: House rule

**PH add-on**:
The separate package of Philippine accounting patterns (BIR forms, withholding, VAT, TIN), installed only by apps that keep Philippine books. Its patterns add to core's patterns rather than copying them. Core holds UI patterns and accounting patterns any country could use.
_Avoid_: PH module, localisation, plugin

**Pattern**:
An approved way to build one piece of UI or one screen, with the rules behind it. A pattern is shown by its stories.
_Avoid_: Component (that is Filament's building block), template, recipe

**Story**:
One rendering of a pattern, or of an app's own screen, in the gallery. Shared stories come with the package; an app's stories stay in that app and only combine approved parts.
_Avoid_: Example, demo

**Screen**:
A pattern for a whole page (a list, record view, form, report, dashboard or settings page). A screen story owes every state, except that a record screen (one record's page or form) has no empty state, and a guest page (log in and the pages around it) owes only populated and error, since it loads no data and forbids no one.
_Avoid_: Page, view, layout

**Wizard**:
A form split into named steps, done in order, for a linear task of three or more steps. It is the long form of the create or edit form, not a screen of its own.
_Avoid_: Stepper, multi-step form

**Section**:
A part of a screen that holds its own data (history, notes, related records). It shows only the states it has.
_Avoid_: Panel (that is Filament's app area), widget, block

**State**:
One of the five conditions a screen is shown in: loading, empty, populated, error, forbidden. "No results" after a search or filter is part of empty, and "can view but not change" is populated with actions hidden.
_Avoid_: Variant, mode, interaction state

**Snippet**:
The code of a story that an agent copies into a screen and configures. It sets up native components and copies no component code, so a screen built from it still uses the design system by version.
_Avoid_: Template, boilerplate

**Extension proposal**:
A request to add something the design system lacks (a component, a pattern, a change to a rule). The owner approves it, and a release ships it before any app uses it.
_Avoid_: Feature request, exception


**Check**:
One rule the test or audit verifies automatically. Every check either passes or fails; none is advisory.
_Avoid_: Warning, lint, suggestion

**Settled exception**:
A place where an app breaks a check because the owner accepted it, recorded with the reason and the owner's approval. It is listed but does not fail. Accessibility checks have none, and an exception that no longer matches anything fails.
_Avoid_: Override, waiver, ignore

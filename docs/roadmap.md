# Roadmap

What the package covers at v0.85.0 (2026-10-19), and what comes next.

## Covered

Accessibility: every story and audited page is checked against WCAG 2.2 AA with axe-core (v0.27.0).

Density (v0.29.0 to v0.50.0): the compact CSS sets Carbon's sizes and spacing where Filament is bigger, on every panel since the look was locked (2026-10-19); full-width pages, one-column forms, and the GOV.UK error messages app-wide.

The menu (v0.28.0): two levels at most, groups in sentence case, words that stay on screen; checked by `storybook-audit`. No source sets the order of groups or their icons.

Generic screens: list tables (search, filters, money, status, import and export, bulk actions), record forms (every field pattern, contact details and percentages, line items, modal, slide-over or wizard), record pages (details, related lists, locked records), delete, approve and reject, notifications, dashboards and the settings page. Account and security: Filament's profile and two-factor screens, reworded and checked against NIST by the house defaults (no passkeys in Filament yet). Inviting users by email, with a 7-day link they set their password from. The notifications panel. Hiding table columns, with Reset (no drag-only reordering). A date-range filter (no presets: no source). Archiving used records instead of deleting them, with Restore. Emailing documents as PDFs, the payment acknowledgement and the customer statement of account (open items).

Error pages (v0.86.0): 403, 404, 419, 429, 500 and 503 in Filament's simple layout, published into the app.

Reports (v0.54.0): sales, collections, receivables ageing, and purchases and payables, on screen with a totals row, as a PDF and as CSV or Excel.

Transactional documents (v0.25.0): document totals with VAT per class, the lifecycle from draft to posted to void with gapless numbering, voiding, recording payments against the balance (withholding both ways), and picking a customer, supplier or item. Printing documents as PDFs in one shared layout (spatie/laravel-pdf, chosen by the owner), and a copy-from view for the hand-written collection receipts. The owner's answers and the practices recommended at their request are under "Business standards" in `rules.md`.

From the accounting app (2026-10-18): adding a record from its picker, previous and next on a View page, key figures on a page of one task, the ageing as one stacked bar on a dashboard, report rows that open what they sum up with reports opened filtered by a link, every table keeping its filters, search and sort, and `storybook-audit` for each panel and a resource's custom page of one record.

## Next

In this order (a proposal; the owner confirms). Generic flows come first: every internal tool needs them, and they need no business facts. The list comes from Mobbin (2026-10-11), which confirms that real apps have a screen but never justifies a rule; each pattern still needs its basis under the rules policy.

All the generic flows are covered (2026-10-12).

The document work is covered too (2026-10-12): emailing documents, the statement of account, and the four reports. Turning one document into the next (v0.55.0), stock (v0.56.0), supplier statements and credit-limit holds (v0.57.0), and withholding (v0.60.0: VAT-invoice checks, quarterly 2307 details, QAP and SAWT) are covered too. The planned list is done; what follows is built when an app needs it.

## Later: everyday business screens

After the documents. Shared patterns stay generic; anything that depends on one business's rules goes in that app's `app/Stories`, with the rules recorded as business standards. Built as an app needs them:

- **Records:** returns and credit notes; attachments on a record. Shared.
- **Generic flows used sometimes** (Mobbin, 2026-10-11): saved views; undo after an action (not in a toast that closes by itself, WCAG 2.2.1); a progress bar for a long background job (queued work already ends with a notification, rule and `actions-email-document`; Filament has no progress bar, so this would be a custom component, built when an app has a job long enough to need one); the list of active sessions; API tokens; notification preferences; a side panel that previews a record beside the list; keyboard shortcut help (single-key shortcuts can be turned off, WCAG 2.1.4); a setup checklist. Shared.
- **Stock, when an app needs it:** more than one warehouse and transfers between them; lot and expiry tracking, and which lot is used first (business standards; the owner has neither for now).
- **Money:** price lists and customer prices; collections and deposits (including post-dated cheques, if used). Shared screens, business rules.
- **Accounting and tax:** chart of accounts; journal entries (debits equal credits); closing a period; tax reports and certificates. App-specific.
- **Accounting-app features** (Mobbin shows them in Xero and QuickBooks; not shared patterns): bank reconciliation, recurring invoices, payment reminders, receipt capture, payment links, multi-currency. App-specific; build them when an app asks.
- **Other screens an app may need:** a customer portal; delivery confirmation on a phone (photo or signature); a delivery calendar; recurring documents; a first-run company setup. Build them when an app asks.
- **Administration:** roles and permissions; faster data entry ("save and create another", keyboard use on long forms). Shared.

## Ask the owner first

These are business facts, so the owner is the source. Record the answers under "Business standards" in `rules.md`:

- When the app becomes BIR-registered to issue its own invoices (CAS or e-invoicing), the sales invoice printout stops being a copy; its required fields and serials then follow BIR's permit. No change planned for now (owner, 2026-10-12).
- From the CPA: whether payments for rent, professional fees or contractor services will go through the app (and if so their ATCs and rates), and whether RR 24-2025 changes WC158 and WC160 (the accounting app reads it as adding WC840 to WC860 beside them); whether a government office withholds its 5% VAT on the VATable sales alone, as `DocumentTotals` now assumes (exempt and zero-rated sales carry no VAT); and whether VAT a customer withholds goes on the SAWT under its own ATC (WV010), one row per tax, where the package now leaves the ATC to the income tax and a government purchase of ₱10,000 or less, with no income tax withheld, has a row with no ATC

- What the accounting app built that the package has no basis or means to share yet (2026-10-18):
  - **The copy beside a posting form** (`AttachedCopy`): the rule exists, but drawing a PDF whole needs pdf.js, a route that serves the file only to a user who may view the record, and the media library. Sharing it means the package ships that viewer and route. The app also splits the page two thirds and one third, where the rule says three quarters and one (owner, 2026-10-13). The rule stays (2026-10-18): Carbon's 2x grid advises against thirds, so the app's split stays its own exception.
  - **Lines by count** in place of Add line (the app's E22): no source sets it, and its count field sits beside its label, which the rules forbid on a field the user types in. It stays the app's unless a source is found.
  - **The form sheet** (the app's E21) and the app's theme choices (blue underlined links, square corners, flat buttons, the paper-gray page): the theme's look, which the policy leaves to each app. Carbon's button page (Oct 2026) sets no shadow either way.
  - **Inline radios** (the app's E10 and E20): three to five one-word options side by side, where the rule allows two (GOV.UK radios, USWDS); Carbon allows a horizontal group "depending on the use case". The rule stays at two (owner, 2026-10-18), so the app changes its posting forms.

## How

Follow `AGENTS.md`:

- Give each rule a basis from the live Carbon pages, using the archived snapshot only where they are silent.
- Each pattern passes `storybook-test` with 0 failures, 0 warnings and 0 accessibility issues, and is looked at in the browser.
- Add checks only where they are reliable, with the Gadgets fixture breaking each one.
- Run `eval/run.sh` with a prompt such as "Add sales invoices with customers, line items, VAT, posting, cancelling and payments".
- Release, then update `filament-starter` in a PR that waits for the owner.

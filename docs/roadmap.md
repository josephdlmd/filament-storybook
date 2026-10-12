# Roadmap

What the package covers at v0.52.0 (2026-10-12), and what comes next.

## Covered

Accessibility: every story and audited page is checked against WCAG 2.2 AA with axe-core (v0.27.0).

Density (v0.29.0 to v0.50.0): `compact()` sets Carbon's sizes and spacing where Filament is bigger; full-width pages, one-column forms, and the GOV.UK error messages app-wide.

The menu (v0.28.0): two levels at most, groups in sentence case, words that stay on screen; checked by `storybook-audit`. No source sets the order of groups or their icons.

Generic screens: list tables (search, filters, money, status, import and export, bulk actions), record forms (every field pattern, contact details and percentages, line items, modal, slide-over or wizard), record pages (details, related lists, locked records), delete, approve and reject, notifications, dashboards and the settings page.

Transactional documents (v0.25.0): document totals with VAT per class, the lifecycle from draft to posted to void with gapless numbering, voiding, recording payments against the balance (withholding both ways), and picking a customer, supplier or item. Printing documents as PDFs in one shared layout (spatie/laravel-pdf, chosen by the owner), and a copy-from view for the hand-written collection receipts. The owner's answers and the practices recommended at their request are under "Business standards" in `rules.md`.

## Next

In this order (a proposal; the owner confirms). Generic flows come first: every internal tool needs them, and they need no business facts. The list comes from Mobbin (2026-10-11), which confirms that real apps have a screen but never justifies a rule; each pattern still needs its basis under the rules policy.

1. Account and security: changing the password or email, and two-factor setup with recovery codes. Filament ships these pages; the rules (NIST, under "Sign-in") exist, the pattern doesn't. The QR code needs a text key beside it, and code fields allow pasting (WCAG 1.1.1, 3.3.8).
2. Inviting a user: email and role, a Pending badge, and revoking an invite. Goes with roles and permissions.
3. The notifications inbox: Filament's bell with a count, unread and all, mark all as read
4. Showing and hiding table columns, with a reset to the default. Reordering needs a way that isn't dragging (WCAG 2.5.7).
5. A date-range filter on a list, with presets such as "Last 7 days"
6. Archiving instead of deleting, with an archived list and restore (needs a source for when to archive)

Then the document work, which waits for the owner's business facts:

1. Sending documents by email (the PDF as an attachment), and the statement of account customers get instead of invoice copies
2. Reports (date range, a totals row, grouping such as ageing)

## Later: everyday business screens

After the documents, in this order. Shared patterns stay generic; anything that depends on one business's rules goes in that app's `app/Stories`, with the rules recorded as business standards.

1. **Turning one document into the next** (quotation, order, delivery, invoice), copying lines and tracking partly fulfilled orders. Shared.
2. **Stock movements:** receipts against a purchase order (including partial receipts), issues, transfers, and adjustments with a reason; stock on hand per item and location; low-stock warnings. Shared. **Lot and expiry tracking**, and which lot is used first, is a business standard.
3. **Statements of account and ageing**, for customers and suppliers. Shared screens; the ageing buckets are a business standard.
4. **Credit-limit warnings or holds** when an order would go over a customer's limit. Shared; warning or hold is a business standard.

Then, as an app needs them:

- **Records:** returns and credit notes; approval history; a change history on each record; notes and attachments on a record; global search. Shared.
- **Generic flows used sometimes** (Mobbin, 2026-10-11): saved views; undo after an action (not in a toast that closes by itself, WCAG 2.2.1); progress of a long background job; the list of active sessions; API tokens; notification preferences; a side panel that previews a record beside the list; keyboard shortcut help (single-key shortcuts can be turned off, WCAG 2.1.4); a setup checklist. Shared.
- **Money:** price lists and customer prices; collections and deposits (including post-dated cheques, if used). Shared screens, business rules.
- **Accounting and tax:** chart of accounts; journal entries (debits equal credits); closing a period; tax reports and certificates. App-specific.
- **Accounting-app features** (Mobbin shows them in Xero and QuickBooks; not shared patterns): bank reconciliation, recurring invoices, payment reminders, receipt capture, payment links, multi-currency. App-specific; build them when an app asks.
- **Other screens an app may need:** a customer portal; delivery confirmation on a phone (photo or signature); a delivery calendar; recurring documents; a first-run company setup. Build them when an app asks.
- **Administration:** roles and permissions; error pages (the rule exists, no pattern); faster data entry ("save and create another", keyboard use on long forms). Shared.

## Ask the owner first

These are business facts, so the owner is the source. Record the answers under "Business standards" in `rules.md`:

- Sending: which documents are emailed, to whom (the customer's billing contact?), and from which address
- When the app becomes BIR-registered to issue its own invoices (CAS or e-invoicing), the sales invoice printout stops being a copy; its required fields and serials then follow BIR's permit
- Reports: which reports, and whether amounts are VAT-inclusive (ageing buckets decided 2026-10-11; see Business standards)
- The CPA review in `rules.md`: rounding, VAT per document and withholding on partial supplier payments

## How

Follow `AGENTS.md`:

- Give each rule a basis from the live Carbon pages, using the archived snapshot only where they are silent.
- Each pattern passes `storybook-test` with 0 failures, 0 warnings and 0 accessibility issues, and is looked at in the browser.
- Add checks only where they are reliable, with the Gadgets fixture breaking each one.
- Run `eval/run.sh` with a prompt such as "Add sales invoices with customers, line items, VAT, posting, cancelling and payments".
- Release, then update `filament-starter` in a PR that waits for the owner.

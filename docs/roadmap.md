# Roadmap

What the package covers at v0.24.1 (2026-10-11), and what comes next.

## Covered

Generic screens: list tables (search, filters, money, status, import and export), record forms (every field pattern, line items, modal, slide-over or wizard), record pages (details, related lists, locked records), delete, approve and reject, notifications and dashboards.

## Next: transactional documents

An invoicing or purchasing app runs on documents, and none of their flow has a pattern yet. These are where agents would improvise money or audit-sensitive logic, so they come first, in this order:

1. **Document totals:** subtotal, VAT per class (12%, zero-rated, exempt), withholding and total due, on the existing rounding and VAT standards (round once, half up, decimal arithmetic, never floats).
2. **Document lifecycle:** draft, posted or issued, cancelled or void. A posted document is never deleted.
3. **Payments and balance due:** partial payments, the amount checked against the balance.
4. **Picking a customer, supplier or item in a form:** a searchable relationship select showing code and name.

Then, in this order:

5. Printing and sending documents (PDF; needs a package choice by the owner)
6. Reports (date range, a totals row, grouping such as ageing)
7. Bulk actions
8. Contact fields (email, Philippine phone number, address)
9. Percentages (discounts, tax rates)
10. A settings page

## Later: everyday business screens

After the documents, in this order. Shared patterns stay generic; anything that depends on one business's rules goes in that app's `app/Stories`, with the rules recorded as business standards.

1. **Turning one document into the next** (quotation, order, delivery, invoice), copying lines and tracking partly fulfilled orders. Shared.
2. **Stock movements:** receipts against a purchase order (including partial receipts), issues, transfers, and adjustments with a reason; stock on hand per item and location; low-stock warnings. Shared. **Lot and expiry tracking**, and which lot is used first, is a business standard.
3. **Statements of account and ageing**, for customers and suppliers. Shared screens; the ageing buckets are a business standard.
4. **Credit-limit warnings or holds** when an order would go over a customer's limit. Shared; warning or hold is a business standard.

Then, as an app needs them:

- **Records:** returns and credit notes; approval history; a change history on each record; notes and attachments on a record; global search. Shared.
- **Money:** price lists and customer prices; collections and deposits (including post-dated cheques, if used). Shared screens, business rules.
- **Accounting and tax:** chart of accounts; journal entries (debits equal credits); closing a period; tax reports and certificates. App-specific.
- **Other screens an app may need:** a customer portal; delivery confirmation on a phone (photo or signature); a delivery calendar; recurring documents; a first-run company setup. Build them when an app asks.
- **Administration:** roles and permissions; error pages (the rule exists, no pattern); faster data entry ("save and create another", keyboard use on long forms). Shared.

## Ask the owner first

These are business facts, so the owner is the source. Record the answers under "Business standards" in `rules.md`:

- Whether the app serves more than one company or branch. Decide this first: it shapes every table, so it can't be added after the documents are built.
- Which documents the app handles (sales invoice, purchase order, receiving, payment, others)
- Whether a posted document can ever be edited
- How cancellation works (a reason, keeping the number, who may cancel)
- Whether withholding tax applies, and how
- The document numbering format

## How

Follow `AGENTS.md`:

- Give each rule a basis from the live Carbon pages, using the archived snapshot only where they are silent.
- Each pattern passes `storybook-test` with 0 failures, 0 warnings and 0 accessibility issues, and is looked at in the browser.
- Add checks only where they are reliable, with the Gadgets fixture breaking each one.
- Run `eval/run.sh` with a prompt such as "Add sales invoices with customers, line items, VAT, posting, cancelling and payments".
- Release, then update `filament-starter` in a PR that waits for the owner.

# Roadmap

What the package covers at v0.25.0 (2026-10-11), and what comes next.

## Covered

Generic screens: list tables (search, filters, money, status, import and export), record forms (every field pattern, line items, modal, slide-over or wizard), record pages (details, related lists, locked records), delete, approve and reject, notifications and dashboards.

Transactional documents (v0.25.0): document totals with VAT per class, the lifecycle from draft to posted to void with gapless numbering, voiding, recording payments against the balance (withholding both ways), and picking a customer, supplier or item. The owner's answers are under "Business standards" in `rules.md`, with what is still to decide.

## Next

In this order (a proposal; the owner confirms):

1. Printing and sending documents (PDF; needs a package choice by the owner)
2. Reports (date range, a totals row, grouping such as ageing)
3. Bulk actions
4. Contact fields (email, Philippine phone number, address)
5. Percentages (discounts, tax rates)
6. A settings page

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

- Printing and sending: which PDF package (the owner picks), which documents are printed or emailed, and what a printed invoice must show (BIR's required fields, and whether the forms are pre-printed)
- Reports: which reports, the ageing buckets, and whether amounts are VAT-inclusive
- The still-to-decide items in `rules.md`: withholding on partial supplier payments (with the CPA), payment prefixes, short receipts, and voiding a document that has payments

## How

Follow `AGENTS.md`:

- Give each rule a basis from the live Carbon pages, using the archived snapshot only where they are silent.
- Each pattern passes `storybook-test` with 0 failures, 0 warnings and 0 accessibility issues, and is looked at in the browser.
- Add checks only where they are reliable, with the Gadgets fixture breaking each one.
- Run `eval/run.sh` with a prompt such as "Add sales invoices with customers, line items, VAT, posting, cancelling and payments".
- Release, then update `filament-starter` in a PR that waits for the owner.

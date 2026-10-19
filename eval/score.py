"""Score an agent eval transcript: tool sequence, Storybook tool use, and whether the code reuses the approved patterns."""
import json, re, subprocess, sys

transcript, app = sys.argv[1], sys.argv[2]
calls, result, audit_ids, audits = [], None, set(), []
for line in open(transcript):
    try:
        m = json.loads(line)
    except ValueError:
        continue
    if m.get("type") == "assistant":
        for block in m["message"].get("content", []):
            if block.get("type") == "tool_use":
                arg = block["input"]
                brief = arg.get("command") or arg.get("file_path") or arg.get("id") or arg.get("ids") or arg.get("queries") or ""
                calls.append((block["name"].replace("mcp__laravel-boost__", "boost:"), str(brief)[:90]))
                if "storybook-audit" in block["name"]:
                    audit_ids.add(block["id"])
    if m.get("type") == "user" and isinstance(m["message"].get("content"), list):
        for block in m["message"]["content"]:
            if isinstance(block, dict) and block.get("type") == "tool_result" and block.get("tool_use_id") in audit_ids:
                text = block["content"] if isinstance(block["content"], str) else " ".join(b.get("text", "") for b in block["content"])
                audits.append(text)
    if m.get("type") == "result":
        result = m

print(f"tool calls: {len(calls)}")
for i, (name, brief) in enumerate(calls, 1):
    print(f"{i:3}. {name:28} {brief}")

story_calls = [c for c in calls if "storybook" in c[0]]
print("\nstorybook tools used:", sorted({c[0] for c in story_calls}) or "NONE")
first_write = next((i for i, c in enumerate(calls) if c[0] in ("Write", "Edit") and "app/Filament" in c[1]), None)
first_story = next((i for i, c in enumerate(calls) if "storybook" in c[0]), None)
# A call that errors (a missing table, unbuilt assets) is not an audit.
completed = [a for a in audits if a.startswith("# ")]
print("audited its own screens:", bool(completed), f"({len(completed)} of {len(audits)} audit calls completed)")
if completed:
    print("last audit:", completed[-1].splitlines()[0])
print("looked up patterns before writing Filament code:", first_story is not None and (first_write is None or first_story < first_write))

diff = subprocess.run(["git", "-C", app, "diff", "HEAD", "--stat"], capture_output=True, text=True).stdout
untracked = subprocess.run(["git", "-C", app, "ls-files", "--others", "--exclude-standard"], capture_output=True, text=True).stdout
print("\nchanged:\n" + diff + untracked)

code = "".join(open(f"{app}/{path}").read() for path in untracked.split() if re.match(r"app/(Filament|Stories)/", path))
# Money is worked out in models and services too, so the money checks read all new app code.
app_code = "".join(open(f"{app}/{path}").read() for path in untracked.split() if re.match(r"app/.*\.php$", path))
checks = {
    "money input: prefix('₱')": "prefix('₱')" in code,
    # A unit price or cost takes 4 decimals (fields-money-input).
    "money input: step(0.01)": "step(0.01)" in code or "step(0.0001)" in code,
    "money column: money('PHP')": "money('PHP')" in code,
    "money column: alignEnd()": "alignEnd()" in code,
    "status: badge()": "badge()" in code,
    "terms: radios": "Radio::make" in code,
    "TIN: shared pattern reused (no mask, example in hint)": "For example, 123-456-789-000" in code and "mask(" not in code,
    # A column's or entry's placeholder() is the text for an empty value; only an input's is banned.
    "no placeholder() format examples": not re.search(r"TextInput::make\([^;]*?->placeholder\(", code, re.S),
    # Forms are one column by house default; inlineLabel() is right on a document's totals (forms-document-totals), so only a
    # field with an inline label counts against it.
    "record form screen: one column, labels above": not re.search(r"(TextInput|Select|DatePicker|Textarea)::make\((?:(?!::make\().)*?->inlineLabel\(", code, re.S),
    # Filament's own "Created"/"Saved" toast meets the rule, so only forcing persistent() (an app-wide default already) fails.
    "notification: no forced persistent()": "->persistent()" not in code,
    # An app that archives instead of deleting has no delete to confirm.
    "delete: confirmation says the consequence": "DeleteAction::make" not in code or "modalDescription(" in code,
    "delete: no solid danger trigger": "outlined(false)" not in code,
    # Purchase-order prompt: approvals, line items, a related list.
    "approval: Approve and Reject actions": "make('approve')" in code and "make('reject')" in code,
    "approval: Reject is gray, not danger": bool(re.search(r"make\('reject'\)(?:(?!make\().)*?->color\('gray'\)", code, re.S)),
    # Hidden by visible()/hidden(), or by authorize() with a policy that checks the status.
    "approval: shown only while awaiting a decision": bool(re.search(r"make\('approve'\)(?:(?!make\().)*?->(visible|hidden|authorize)\(", code, re.S)),
    "line items: table repeater": "Repeater::make" in code and "->table([" in code,
    "line items: a row each, through relationship()": bool(re.search(r"Repeater::make\([^;]*?->relationship\(", code, re.S)),
    # Or a document's payments in a table on its View page, as records-view-page shows them.
    "related list: a relation manager": "extends RelationManager" in code or "extends ManageRelatedRecords" in code or "RepeatableEntry::make('payments')" in code,
    # Import prompt: the importer validates like the form and never rounds amounts.
    "import: ImportAction with an importer": "ImportAction::make" in code and "extends Importer" in code,
    "import: row limit set": "maxRows(" in code,
    "import: amounts not numeric()": "extends Importer" in code and not re.search(r"ImportColumn::make\([^;]*?->numeric\(", code),
    "export: ExportAction with an exporter": "ExportAction::make" in code and "extends Exporter" in code,
    # Dashboard prompt: widgets that don't poll, charts with their values as text.
    "dashboard: widgets don't refresh by themselves": "Widget" in code and len(re.findall(r"pollingInterval = null", code)) >= len(re.findall(r"extends (?:StatsOverviewWidget|ChartWidget)", code)) > 0,
    "dashboard: chart values as text": "extends ChartWidget" in code and "getChartAssistiveContent" in code,
    "dashboard: bar or line axis from zero": "extends ChartWidget" in code and "beginAtZero" in code,
    # Sales invoice prompt: totals, posting, voiding, payments and the customer picker.
    "invoice totals: decimal arithmetic, no float round()": "bcadd(" in app_code and not re.search(r"(?<![\w>:])(?<!function )round\(", app_code),
    "invoice totals: VAT per class": "zero_rated" in app_code and "exempt" in app_code,
    "posting: confirms in a danger dialog": bool(re.search(r"make\('post'\)(?:(?!Action::make\().)*?->modalSubmitAction\((?:(?!Action::make\().)*?'danger'", code, re.S)),
    "posting: numbered on posting, with a row lock": "lockForUpdate" in app_code,
    "cancel: Void, with a reason": "make('void')" in code and "reason" in code,
    "payments: checked against the balance": "balance" in code and ("maxValue(" in code or "$fail(" in code),
    "payments: a collection under its receipt's serial, checked": bool(re.search(r"->unique\([^)]*Payment", code)),
    "payments: VAT withheld kept apart from income tax withheld": "vat_withheld" in app_code and "tax_withheld" in app_code,
    "payments: a duplicate serial from the unique index caught": "UniqueConstraintViolationException" in app_code,
    # Collections prompt: what the SAWT adds up, and the ageing report's as-of date.
    "payments: income payment and ATC kept for the SAWT": "income_payment" in app_code and "atc" in app_code.lower(),
    # The base may go through a closure around expectedWithholding(), as the pattern's own $withheld does.
    "payments: VAT withheld on the VATable sales alone": bool(re.search(r"(?i)(expectedWithholding|withheld)\([^;]*vatable_sales", app_code)),
    "ageing: invoices dated after the as-of date left out": "ReceivablesAgeing" in app_code or bool(re.search(r"invoice_date'?\s*,\s*'<='", app_code)),
    "purchases: withholding worked out once on the supplier's invoice": "withholding_code" in app_code and "DocumentTotals::withholding(" in app_code,
    "printing: the shared, tagged PDF layout": "filament-storybook::documents.print" in app_code and "->tagged()" in app_code,
    "customer picker: searchable relationship": bool(re.search(r"relationship\('customer'(?:(?!make\().)*?->searchable\(", code, re.S)),
    "chain: deliveries copy the order's lines, quantities up to what is left": "maxValue(" in code and ("Fulfilment" in app_code or "remaining" in app_code),
    "chain: one invoice per delivery": bool(re.search(r"make\('createInvoice'\)|Create invoice", code)),
    "credit limit: an order over it goes for approval": "credit_limit" in app_code and bool(re.search(r"for.?approval", app_code, re.I)),
    "email: field is email()": bool(re.search(r"make\('\w*email\w*'\)(?:(?!make\().)*?->email\(\)", code, re.S)),
    "phone: field is tel(), stored as +63": bool(re.search(r"make\('\w*phone\w*'\)(?:(?!make\().)*?->tel\(\)", code, re.S)) and "+63" in app_code,
    "address: in Philippine fields": "barangay" in code and "zip_code" in code,
    "bulk: actions as toolbar buttons, not a menu": "BulkAction" in code and "BulkActionGroup::make" not in code,
    "archive: soft deletes with an Archived filter and Restore": "SoftDeletes" in app_code and "TrashedFilter" in code and "RestoreAction" in code,
    # v0.66: a status's word and colour on its enum; a related record imported by a value people know; a dashboard filter.
    "status: word and colour on the enum": bool(re.search(r"implements[^{]*HasColor", app_code)),
    "import: a related record through relationship()": bool(re.search(r"ImportColumn::make\([^;]*?->relationship\(", code, re.S)),
    "bulk archive: archive many at once": bool(re.search(r"BulkAction::make\('archive", code)) or ("DeleteBulkAction" in code and "SoftDeletes" in app_code),
    "date range: one dashboard filter every widget reads": "HasFiltersForm" in code and "InteractsWithPageFilters" in code,
    # v0.79-v0.80: a record's key events in the activity log, notes on a record, global search.
    "history: key events written to the activity log": "activity()" in code and "performedOn(" in code and "LogsActivity" not in app_code,
    "notes: on the record, knowing their writer by id": bool(re.search(r"function notes\(\)", app_code)) and "user_id" in code,
    "global search: says what it searches": "getGloballySearchableAttributes" in code,
    # v0.82-v0.83: a chart's empty months, cached figures, related values loaded with search results, a wizard's last step.
    "dashboard: chart lists every month, empty ones as 0": "extends ChartWidget" in code and ("CarbonPeriod" in code or "range(" in code),
    "dashboard: figures cached": "extends StatsOverviewWidget" in code and bool(re.search(r"(Cache::|cache\(\)->)remember", code)),
    "global search: related values loaded with the results": "getGlobalSearchResultDetails" not in code or "getGlobalSearchEloquentQuery" in code,
    "wizard: ends with Check answers": "Step::make('Check answers')" in code,
}
shown = {c[1] for c in calls if "storybook-show" in c[0]}
print("patterns looked up:", sorted(shown) or "none")
# Checks for a feature the prompt doesn't ask for show as "-", so a ✗ is always a miss.
prompt = open(transcript.rsplit("/", 1)[0] + "/prompt.txt").read().lower() if __import__("os").path.exists(transcript.rsplit("/", 1)[0] + "/prompt.txt") else ""
asks = {"approval": "approv", "line items": "line", "related list": "page", "import": "import", "export": "export", "dashboard": "dashboard",
        "money": "peso", "status": "status", "terms": "terms", "TIN": "tin", "delete": "delet", "notification": "tell",
        "invoice": "vat", "posting": "posting", "cancel": "cancel", "payments": "payment(?! terms)", "customer picker": "(?=^(?!.*customer name)).*customer", "printing": "print", "purchases": "purchase",
        "chain": "deliver(?!y)", "ageing": "(days past due|ageing|aging|overdue)", "credit limit": "credit.limit[^.]*(approv|order)", "email": "email", "phone": "phone", "address": "(?<!email )address", "bulk": "(at once|several|bulk)", "archive": "(archiv|out of use)", "date range": "date range", "history": "(history|who created|who posted)", "notes": "notes?\\b", "global search": "search at the top", "wizard": "(in steps|wizard)"}
print("pattern reuse:")
for k, v in checks.items():
    topic = next((t for t in asks if k.startswith(t)), None)
    applies = not prompt or topic is None or re.search(r"\b" + asks[topic], prompt)
    print(f"  {('✓' if v else '✗') if applies else '-'} {k}")
if result:
    print(f"\nresult: {result.get('subtype')}, turns {result.get('num_turns')}, cost ${result.get('total_cost_usd', 0):.2f}, {result.get('duration_ms', 0) // 1000}s")

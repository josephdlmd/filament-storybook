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
checks = {
    "money input: prefix('₱')": "prefix('₱')" in code,
    "money input: step(0.01)": "step(0.01)" in code,
    "money column: money('PHP')": "money('PHP')" in code,
    "money column: alignEnd()": "alignEnd()" in code,
    "status: badge()": "badge()" in code,
    "terms: radios": "Radio::make" in code,
    "TIN: shared pattern reused (no mask, example in hint)": "For example, 123-456-789-000" in code and "mask(" not in code,
    "no placeholder() format examples": "->placeholder(" not in code,
    "record form screen: one column, labels above": "->columns(1)" in code and "inlineLabel()" not in code,
    # Filament's own "Created"/"Saved" toast meets the rule, so only forcing persistent() (an app-wide default already) fails.
    "notification: no forced persistent()": "->persistent()" not in code,
    "delete: confirmation says the consequence": "DeleteAction::make" in code and "modalDescription(" in code,
    "delete: no solid danger trigger": "outlined(false)" not in code,
    # Purchase-order prompt: approvals, line items, a related list.
    "approval: Approve and Reject actions": "make('approve')" in code and "make('reject')" in code,
    "approval: Reject is gray, not danger": bool(re.search(r"make\('reject'\)(?:(?!make\().)*?->color\('gray'\)", code, re.S)),
    "approval: shown only while awaiting a decision": bool(re.search(r"make\('approve'\)(?:(?!make\().)*?->(visible|hidden)\(", code, re.S)),
    "line items: table repeater": "Repeater::make" in code and "->table([" in code,
    "related list: a relation manager": "extends RelationManager" in code,
}
shown = {c[1] for c in calls if "storybook-show" in c[0]}
print("patterns looked up:", sorted(shown) or "none")
print("pattern reuse:")
for k, v in checks.items():
    print(f"  {'✓' if v else '✗'} {k}")
if result:
    print(f"\nresult: {result.get('subtype')}, turns {result.get('num_turns')}, cost ${result.get('total_cost_usd', 0):.2f}, {result.get('duration_ms', 0) // 1000}s")

"""Score an agent eval transcript: tool sequence, Storybook tool use, and whether the code reuses the approved patterns."""
import json, re, subprocess, sys

transcript, app = sys.argv[1], sys.argv[2]
calls, result = [], None
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
    if m.get("type") == "result":
        result = m

print(f"tool calls: {len(calls)}")
for i, (name, brief) in enumerate(calls, 1):
    print(f"{i:3}. {name:28} {brief}")

story_calls = [c for c in calls if "storybook" in c[0]]
print("\nstorybook tools used:", sorted({c[0] for c in story_calls}) or "NONE")
first_write = next((i for i, c in enumerate(calls) if c[0] in ("Write", "Edit") and "app/Filament" in c[1]), None)
first_story = next((i for i, c in enumerate(calls) if "storybook" in c[0]), None)
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
    "terms: ToggleButtons": "ToggleButtons" in code,
    "TIN story added in app/Stories": "app/Stories" in untracked,
    "no placeholder() format examples": "->placeholder(" not in code,
}
print("pattern reuse:")
for k, v in checks.items():
    print(f"  {'✓' if v else '✗'} {k}")
if result:
    print(f"\nresult: {result.get('subtype')}, turns {result.get('num_turns')}, cost ${result.get('total_cost_usd', 0):.2f}, {result.get('duration_ms', 0) // 1000}s")

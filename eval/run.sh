#!/usr/bin/env bash
# Agent eval: give a headless Claude Code an ordinary feature request in a throwaway Filament app
# (with this package installed and only the app's Boost MCP server, and none of the user's own plugins or settings),
# then score how it used the stories.
# Usage: eval/run.sh <app-dir> <out-dir> [prompt]   (the app should be a disposable git worktree with a clean tree)
set -euo pipefail
app=$(realpath "$1"); out=$(realpath "$2"); mkdir -p "$out"

PROMPT=${3:-'Add a Suppliers feature to the admin panel. A supplier has a name, a TIN (Philippine tax identification number), a credit limit in pesos, payment terms (cash on delivery, 30 days or 60 days) and a status (active, on hold or blacklisted). Build the model, migration, factory and a Filament resource with a list table and a create/edit form, and add feature tests. Run the test suite before you finish.'}

printf '%s' "$PROMPT" > "$out/prompt.txt"
(cd "$app" && claude -p "$PROMPT" \
  --mcp-config .mcp.json --strict-mcp-config \
  --setting-sources project,local \
  --permission-mode acceptEdits \
  --allowedTools "Bash(php artisan:*)" "Bash(vendor/bin/pest:*)" "Bash(vendor/bin/pint:*)" "Bash(composer:*)" "mcp__laravel-boost__*" \
  --max-budget-usd 6 \
  --output-format stream-json --verbose > "$out/transcript.jsonl" 2> "$out/stderr.txt")

python3 -I "$(dirname "$0")/score.py" "$out/transcript.jsonl" "$app"

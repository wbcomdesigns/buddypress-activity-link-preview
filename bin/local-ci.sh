#!/usr/bin/env bash
# Local CI for BuddyPress Activity Link Preview. Exits non-zero if any stage fails.
#
# Usage: bash bin/local-ci.sh
#
# Stages:
#   1 php -l over every plugin PHP file
#   2 WPCS (phpcs --standard=WordPress, errors only)
#   3 node --check on the frontend script
#   4 docs lint (wbcom-documentation)
#   5 core-first check (CF1-CF7)
#   6 portfolio gate-check (E6 "already tagged" is tolerated between releases)
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SKILLS="${HOME}/.claude/skills"
cd "$ROOT" || exit 1
FAILED=()
stage() { echo; echo "==> $1"; }
fail() { FAILED+=("$1"); echo "FAIL: $1"; }

PHP_FILES=()
while IFS= read -r -d '' f; do PHP_FILES+=("$f"); done < <(find . -name '*.php' -not -path './node_modules/*' -not -path './dist/*' -not -path './build/*' -not -path './vendor/*' -print0)

stage "1. php -l"
LINT_ERR=0
for f in "${PHP_FILES[@]}"; do
	out="$(php -l "$f" 2>&1)" || { echo "$out"; LINT_ERR=1; }
done
[ "$LINT_ERR" -eq 0 ] && echo "    ${#PHP_FILES[@]} file(s) parse" || fail "php -l"

stage "2. WPCS (errors only)"
if command -v phpcs >/dev/null 2>&1; then
	phpcs -q -n --standard=WordPress --report=emacs "${PHP_FILES[@]}" && echo "    no errors" || fail "phpcs"
else
	fail "phpcs not installed (composer global require wp-coding-standards/wpcs)"
fi

stage "3. JavaScript syntax"
node --check assets/js/bp-activity-link-preview.js && echo "    parses" || fail "node --check"

stage "4. Docs lint"
node "${SKILLS}/wbcom-documentation/bin/lint-plugin-docs.mjs" . || fail "docs lint"

stage "5. core-first"
php "${SKILLS}/wp-plugin-development/scripts/core-first-check.php" . || fail "core-first"

stage "6. gate-check"
GATE_OUT="$(bash "${SKILLS}/wp-plugin-development/scripts/gate-check.sh" . 2>&1)"
echo "$GATE_OUT"
GATE_FAILS="$(printf '%s\n' "$GATE_OUT" | sed $'s/\x1b\\[[0-9;]*m//g' | grep -E '^[[:space:]]*FAIL[[:space:]]' | grep -vE '^[[:space:]]*FAIL[[:space:]]+E6[[:space:]]' || true)"
[ -z "$GATE_FAILS" ] || fail "gate-check: $(printf '%s' "$GATE_FAILS" | tr -s ' ' | tr '\n' ';')"

echo
if [ "${#FAILED[@]}" -gt 0 ]; then
	echo "LOCAL CI FAILED (${#FAILED[@]}):"
	printf '  - %s\n' "${FAILED[@]}"
	exit 1
fi
echo "LOCAL CI PASSED"

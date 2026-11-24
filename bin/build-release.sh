#!/usr/bin/env bash
# Build the customer zip: dist/activity-link-preview-for-buddypress-<version>.zip (folder = WordPress.org slug)
#
# Usage: bash bin/build-release.sh [--allow-dirty]
#
#   --allow-dirty  build from an uncommitted tree (local checks only, never a release).
#
# Gates: clean tree, version agreement (header, constant, Stable tag, package.json,
# changelog block), docs lint, zip contents (no dev files, required files present).
# The pre-release browser smoke is run separately with the wp-plugin-smoke skill.
#
# Exit codes: 1 generic, 2 dirty tree, 3 version mismatch, 4 zip leaks dev files, 5 docs lint.
set -euo pipefail

slug=activity-link-preview-for-buddypress   # WordPress.org slug = zip folder. Locked by CI (wporg-folder-lock.yml).
SLUG="$slug"
MAIN="bp-activity-link-preview.php"
main="$MAIN"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${ROOT}/dist"
STAGE_DIR="${DIST_DIR}/${SLUG}"

ALLOW_DIRTY=0
for arg in "$@"; do
	case "$arg" in
		--allow-dirty) ALLOW_DIRTY=1 ;;
		*) echo "Unknown option: $arg" >&2; exit 1 ;;
	esac
done

cd "$ROOT"

# --- 1. Clean tree -----------------------------------------------------------
if [ "$ALLOW_DIRTY" -eq 0 ] && [ -n "$(git status --porcelain 2>/dev/null)" ]; then
	echo "FAIL: working tree has uncommitted changes. Commit first, or --allow-dirty for a local check." >&2
	exit 2
fi

# --- 2. Version agreement ----------------------------------------------------
HEADER_VERSION="$(grep -m1 -E '^[[:space:]*]*Version:' "$MAIN" | awk -F': *' '{print $2}' | tr -d ' \r\n')"
CONST_VERSION="$(grep -m1 "define( 'BP_ACTIVITY_LINK_PREVIEW_VERSION'" "$MAIN" | grep -oE "[0-9]+\.[0-9]+\.[0-9]+" || true)"
STABLE_TAG="$(grep -m1 -E '^Stable tag:' readme.txt | awk -F': *' '{print $2}' | tr -d ' \r\n')"
PKG_VERSION="$(grep -m1 '"version"' package.json | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' || true)"
VERSION="$HEADER_VERSION"

echo "==> ${SLUG} ${VERSION}"
echo "    header=${HEADER_VERSION} constant=${CONST_VERSION} stable_tag=${STABLE_TAG} package.json=${PKG_VERSION}"
if [ -z "$VERSION" ] || [ "$VERSION" != "$CONST_VERSION" ] || [ "$VERSION" != "$STABLE_TAG" ] || [ "$VERSION" != "$PKG_VERSION" ]; then
	echo "FAIL: version mismatch - plugin header, BP_ACTIVITY_LINK_PREVIEW_VERSION, readme Stable tag and package.json must agree." >&2
	exit 3
fi
if ! grep -qE "^= ${VERSION//./\\.} " readme.txt; then
	echo "FAIL: readme.txt has no changelog block '= ${VERSION} ...='." >&2
	exit 3
fi

# --- 3. Docs lint ------------------------------------------------------------
DOCS_LINT="${HOME}/.claude/skills/wbcom-documentation/bin/lint-plugin-docs.mjs"
if [ -f "$DOCS_LINT" ]; then
	echo "==> Docs lint"
	node "$DOCS_LINT" . || { echo "FAIL: docs lint reported errors." >&2; exit 5; }
else
	echo "WARN: docs lint not found at ${DOCS_LINT}; skipped."
fi

# --- 4. Stage + zip ------------------------------------------------------------
ZIP_PATH="${DIST_DIR}/${SLUG}-${VERSION}.zip"
rm -rf "$STAGE_DIR" "$ZIP_PATH"
mkdir -p "$STAGE_DIR"
rsync -a --exclude-from="${ROOT}/.distignore" ./ "${STAGE_DIR}/"
# Deflate every entry: a PHP file stored uncompressed trips the store's upload firewall.
( cd "$DIST_DIR" && python3 -I -c 'import os,sys,zipfile
out,top=sys.argv[1],sys.argv[2]
with zipfile.ZipFile(out,"w",zipfile.ZIP_DEFLATED,compresslevel=9) as z:
    for r,d,f in os.walk(top):
        d.sort()
        for n in sorted(f): z.write(os.path.join(r,n))' "$ZIP_PATH" "$SLUG" )
rm -rf "$STAGE_DIR"
zip="$ZIP_PATH"
# pipefail off for the lock block: unzip piped into head / grep -q exits 141 (SIGPIPE) on larger zips.
set +o pipefail
# --- WordPress.org folder lock (Wbcom standard - do not edit) ----------------------
top=$(unzip -Z1 "$zip" | head -1 | cut -d/ -f1)
[ "$top" = "$slug" ] || { echo "Zip folder '$top' is not the WordPress.org slug '$slug'"; exit 1; }
unzip -Z1 "$zip" | grep -qx "$slug/$main" || { echo "Main file $slug/$main missing from zip"; exit 1; }
[ "$(LC_ALL=C grep -a -c '<?php' "$zip" || true)" = 0 ] || { echo "Zip has plain-text PHP (upload firewall)"; exit 1; }
# -----------------------------------------------------------------------------------
set -o pipefail

# --- 5. Verify the zip -----------------------------------------------------------
# Listing captured once: piping unzip into `grep -q` under pipefail fails on SIGPIPE.
LISTING="$(unzip -Z1 "$ZIP_PATH")"
LEAKS="$(printf '%s\n' "$LISTING" | grep -iE "^${SLUG}/(docs|marketing|audit|tests|bin|node_modules|plan|plans|\.wordpress-org)/|\.md$|\.map$|(^|/)(package(-lock)?\.json|composer\.(json|lock)|gruntfile\.js|phpstan[^/]*|\.?phpcs\.xml(\.dist)?|\.distignore|\.git[^/]*|\.DS_Store|\.wbcom-i18n\.json|screenshot-[^/]*)$" || true)"
if [ -n "$LEAKS" ]; then
	echo "FAIL: customer zip contains dev files:" >&2
	echo "$LEAKS" >&2
	exit 4
fi
for required in "$MAIN" readme.txt languages/buddypress-activity-link-preview.pot assets/js/bp-activity-link-preview.js assets/css/bp-activity-link-preview.css; do
	printf '%s\n' "$LISTING" | grep -qx "${SLUG}/${required}" || { echo "FAIL: zip is missing ${required}" >&2; exit 4; }
done

COUNT="$(printf '%s\n' "$LISTING" | grep -vc '/$')"
SIZE_KB="$(( $(stat -f%z "$ZIP_PATH" 2>/dev/null || stat -c%s "$ZIP_PATH") / 1024 ))"
echo "OK: ${ZIP_PATH#"$ROOT"/} - ${COUNT} files, ${SIZE_KB} KB, no dev files"

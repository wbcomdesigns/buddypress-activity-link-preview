# Plugin: Activity Link Preview For BuddyPress

> **READ FIRST:** [`audit/manifest.json`](audit/manifest.json) is the canonical inventory — 1 AJAX action, 1 REST-embed filter, 5 own filters, 13 hook listeners, 2 activity-meta keys, **0** admin pages / settings / tables / CPTs / cron / WP-CLI / blocks / shortcodes. Use it before grepping. See also [`audit/FEATURE_AUDIT.md`](audit/FEATURE_AUDIT.md), [`audit/CODE_FLOWS.md`](audit/CODE_FLOWS.md), and the wppqa baseline [`audit/wppqa-baseline-2026-06-05/SUMMARY.md`](audit/wppqa-baseline-2026-06-05/SUMMARY.md). Refresh via `/wp-plugin-onboard --refresh` after non-trivial changes.

> **Development conventions:** Follow the [`wp-plugin-development`](https://example/skills/wp-plugin-development) skill for ALL changes — the 16 critical admin rules, Part 6 Admin UI patterns, design tokens (3-layer model), escaping/security rules, and dev hygiene (no em-dash in i18n, Lucide icons over inline SVG, WPCS). This plugin currently has **no admin UI** (see Admin-UI wrapper below); if one is ever added, it MUST use the NEW card-based shell pattern from `wp-plugin-development` Part 6 — not the legacy `admin/wbcom/` wrapper.

## Quick reference
- **Main file**: `bp-activity-link-preview.php` (single-file, procedural; 1033 lines, 20 functions)
- **Version**: `1.7.3` (header + `BP_ACTIVITY_LINK_PREVIEW_VERSION` + readme `Stable tag`)
- **Dev branch**: `v1.7.4` — NOTE: version bump not yet applied in code; all version strings still read 1.7.3.
- **Namespace**: none (no classes)
- **Text domain**: `buddypress-activity-link-preview`
- **Extends**: none (standalone free plugin)
- **Requires**: BuddyPress **or** BuddyBoss Platform (auto-deactivates on `admin_init` if neither active)

## Architecture
Single procedural file. There is no class layer, container, or seven-layer structure. Bootstrap is `bp_activity_link_preview_bootstrap()` fired from `plugins_loaded@20` (after BuddyPress, which uses default 10) only when the dependency is present. All behavior is hook-driven; extension is via 5 documented filters (see `audit/FEATURE_AUDIT.md`).

## Admin-UI wrapper classification: **NONE**
This plugin has **no settings page and no admin UI of any kind.**
- No `admin/wbcom/wbcom-admin-settings.php` (OLD wrapper) — absent.
- No `includes/shared-admin/class-wbcom-shared-dashboard.php` (INTERMEDIATE) — absent.
- No `includes/admin/views/shell.php` (NEW shell) — absent.
- No `add_menu_page` / `add_submenu_page` / `add_options_page` / `register_setting` anywhere.
- No `get_option` / `update_option` — zero settings options read or written.
- Only admin-side output: a dependency-missing `admin_notices` error (`bp_activity_link_preview_admin_notice`, registered at `:72` / `:119`).

Behavior toggles are code-level filters only (notably `bp_activity_link_preview_enable_comments`, default `true`). If a settings UI is added later, classify it NEW and follow `wp-plugin-development` Part 6.

## Key entry points
- AJAX: `bp_activity_parse_url_preview()` (`:170`) — URL parse endpoint (`is_user_logged_in()` + nonce + SSRF guard)
- Parse engine: `bp_activity_link_parse_url()` (`:231`); internal-URL fast path `bp_activity_link_parse_internal_url()` (`:472`)
- Save: `bp_activity_link_preview_save_link_data()` (`:601`) on `bp_activity_after_save`
- Render: `bp_activity_link_preview_render_preview()` (`:885`); content filters at `:744` / `:809`
- REST embed: `bp_activity_link_preview_data_embed_rest_api()` (`:987`) on `bp_rest_activity_prepare_value`
- Frontend JS: `assets/js/bp-activity-link-preview.js`

## Important patterns
- **Multi-platform compat is load-bearing.** Stands down for BuddyBoss native preview and Youzify wall preview to avoid duplicate cards; removes BuddyBoss's own filter at `bp_init@999`. Touching the render/save paths requires re-checking all three integration branches.
- **SSRF guard** on the AJAX endpoint (`:196-204`) blocks localhost/RFC1918/reserved IPs — do not weaken when editing the fetch path.
- **Caching**: per-URL transient `bp_oembed_<md5(url)>`, TTL `DAY_IN_SECONDS`.
- **Saved previews render server-side** (PHP filter), so they display with JS off; only compose-time live detection is JS-only.
- **Reddit URLs are deliberately skipped** in save paths.

## CSS selectors (for testing/dev)
`.activity-link-preview-container`, `.activity-comment-link-preview-container`, `.activity-url-scrapper-container`, `#whats-new-attachments`, `#comment-attachments-<id>`, `.activity-link-preview-title`, `.activity-link-preview-image`, `.activity-link-preview-excerpt`, `.fb-post`.

## Onboarding scope note
This onboarding pass was **artefacts-only** (manifest + audit reports + graph + CLAUDE.md + wppqa baseline). The Phase 4.5/4.7/4.8 scaffolds (paired-plugin contract, local-CI pipeline, journeys, scale benchmark, cleanup framework) were intentionally NOT created — no functional code, no CI scaffolding, no commit. Run those companion phases separately if/when desired.

## Recent changes
| Date | Type | Description | Files |
|---|---|---|---|
| 2026-06-05 | onboard | Generated audit/ manifest + reports + graph + wppqa baseline; wrote READ-FIRST CLAUDE.md | `audit/*`, `CLAUDE.md` |

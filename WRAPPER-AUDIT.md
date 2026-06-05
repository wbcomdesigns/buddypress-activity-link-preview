# Wrapper Audit: buddypress-activity-link-preview

**Branch:** v1.7.4  
**Audit date:** 2026-06-05  
**Wrapper type:** NONE (intentionally not migrated — single-file procedural, no settings page)  
**Auditor:** AutoVAP READ-ONLY pass

---

## 1. NONE Classification — Still Accurate?

**YES. Classification is confirmed correct.**

Full grep of the main file (`bp-activity-link-preview.php`) and the `audit/manifest.json` confirms zero admin infrastructure:

- `admin_pages: []` in manifest.
- `settings: []` in manifest.
- No `add_menu_page`, `add_submenu_page`, `add_options_page` anywhere.
- No `register_setting` anywhere.
- No `get_option` / `update_option` for plugin settings.
- No `admin/wbcom/`, no `includes/shared-admin/`, no `includes/admin/views/shell.php`.
- No `[wbcom_admin_setting_header]` shortcode reference.

The only admin-side output is the dependency-missing notice (`bp_activity_link_preview_admin_notice`) registered on `admin_notices` at lines 72 and 119. That notice fires only when BuddyPress / BuddyBoss Platform is absent and auto-deactivates the plugin. It is not a settings page.

---

## 2. No Persisted Options to Wire?

**CONFIRMED — zero plugin options.**

The plugin persists **activity meta** (not `wp_options`):
- `_bp_activity_link_preview_data` (main activity post previews)
- `_bp_activity_comment_link_preview_data` (comment previews)

These are operational data written/read by the content pipeline, not admin-configurable settings. There is no `register_setting`, no `get_option`, no `update_option` call anywhere in the codebase. The option-wiring table from the brief template is therefore **N/A** — there is nothing to wire.

---

## 3. Re-confirmed Onboarding Findings

### (a) VERSION DRIFT — Still Present

| Location | Value found |
|---|---|
| Plugin header `Version:` (line 8) | `1.7.3` |
| `define( 'BP_ACTIVITY_LINK_PREVIEW_VERSION', ... )` (line 26) | `1.7.3` |
| `readme.txt` `Stable tag:` (line 7) | `1.7.3` |
| Git/dev branch name | `v1.7.4` |

**All three canonical version strings still read 1.7.3 while the branch is named v1.7.4. The drift is unresolved.** The plugin header, constant, and readme must all be bumped to `1.7.4` before any release tagged from this branch. This is a low-severity but blocking pre-release item.

### (b) Raw `file_get_contents()` SSRF-Adjacent Fallback — Still Present

**Location:** `bp-activity-link-preview.php:261`

```
@file_get_contents( $url, null, stream_context_create( $context ) );
// phpcs:ignore ... -- Fallback for short URL resolution when wp_safe_remote_get fails.
```

**Context:** This executes only inside the short-URL branch (host in the `bit.ly / tinyurl / ...` allowlist, line 235), and only when `wp_safe_remote_get()` has already been called and returned a URL that still equals `$original_url` (i.e. the redirect was not followed in the first attempt). The purpose is to read `$http_response_header[6]` — the `Location:` header — from PHP's stream context after following one redirect.

**Risk assessment:**

The call is gated by:
1. Inclusion in the short-URL provider allowlist (a static array filtered by `bp_activity_link_parse_url_shorten_url_provider`).
2. `wp_safe_remote_get()` already ran and failed to resolve.
3. The resolved `$new_url` must still pass `FILTER_VALIDATE_URL` before it is used.

**However**, the SSRF IP-range guard (lines 196-204) runs on the **input URL** in the AJAX handler before `bp_activity_link_parse_url()` is called. That guard does NOT re-run against the `$url` inside `bp_activity_link_parse_url()` itself. If a short-URL provider (e.g. `b.link`) redirects to a private-IP URL, `file_get_contents` would follow that redirect before the resolved URL reaches the outer `FILTER_VALIDATE_URL` check. This is a residual SSRF vector: low probability (requires a malicious or compromised short-URL service) but real.

`wp_remote_get()` respects `WP_HTTP_BLOCK_EXTERNAL` and honours the WordPress HTTP API transport restrictions; `file_get_contents` bypasses all of that.

**Severity: Medium.** The fallback path should either be removed (accept that some short URLs cannot be resolved) or replaced with a second `wp_safe_remote_get()` call that enforces the same private-IP block as the AJAX guard.

---

## 4. Recommendation: Does This Plugin Need an Admin Page / Card Wrapper?

**No — leave as-is.** The plugin is correctly behaviour-via-filters only. It has no toggle-worthy settings that would warrant a UI: the one meaningful behaviour flag (`bp_activity_link_preview_enable_comments`, default `true`) is already a public filter with documented usage in both the main file and `readme.txt`. Adding a settings page for a single boolean would add ~3-4 PHP files, a menu entry under "WB Plugins", and ongoing maintenance surface for zero end-user benefit over the current filter pattern. If the Pro extension ever adds per-role controls, image limits, or domain allowlists, a settings page would make sense then. For the free plugin at v1.7.x, no wrapper is warranted.

---

## Findings (Severity-Ranked)

| # | Severity | Finding | File:line | Suggested fix |
|---|---|---|---|---|
| 1 | **Medium** | `file_get_contents()` short-URL fallback bypasses WordPress HTTP API and private-IP re-check; resolved redirect URL not run through the SSRF guard | `bp-activity-link-preview.php:261-267` | Replace with a second `wp_safe_remote_get()` call with `'redirection' => 1`; if it still fails, abort — do not fall back to `file_get_contents`. |
| 2 | **Low** | VERSION DRIFT: branch is `v1.7.4` but plugin header, `BP_ACTIVITY_LINK_PREVIEW_VERSION` constant, and `readme.txt` Stable tag all say `1.7.3` | `bp-activity-link-preview.php:8,26`; `readme.txt:7` | Bump all three to `1.7.4` before tagging the release. |

---

## Overall Verdict

**FIX-NEEDED (pre-release)**

- NONE wrapper classification: **confirmed correct, no action needed**.
- Zero persisted options: **confirmed, nothing to wire**.
- Must-fix before releasing v1.7.4:
  1. Bump version strings (header + constant + readme) from 1.7.3 to 1.7.4.
  2. Replace `file_get_contents()` fallback with a second `wp_safe_remote_get()` call to eliminate the residual SSRF gap.

No admin page or card wrapper is warranted for this plugin in its current form.

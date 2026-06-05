# Build Log: buddypress-activity-link-preview

## 2026-06-05 — v1.7.4 pre-release fixes (WRAPPER-AUDIT findings #1, #2)

**Branch:** v1.7.4 · **File:** `bp-activity-link-preview.php` (single-file procedural)

### Fix 1 — SSRF: short-URL fallback bypassed WP HTTP API + private-IP guard (MED)
- Extracted the inline SSRF host guard (formerly AJAX handler lines 196-204) into a
  reusable function `bp_activity_link_preview_is_blocked_host( $host )` that returns
  `true` when a host is empty / a private or reserved IP / loopback (127.0.0.1,
  localhost) / RFC1918 (10., 172.16-31., 192.168.). No logic duplicated.
- AJAX handler `bp_activity_parse_url_preview()` now calls the shared guard.
- Replaced the raw `@file_get_contents( $url, null, stream_context_create(...) )`
  short-URL fallback in `bp_activity_link_parse_url()` with
  `wp_safe_remote_head( $url, array( 'redirection' => 1, 'timeout' => 5, ... ) )`.
  The resolved `Location` header is read via `wp_remote_retrieve_header()`, validated
  with `FILTER_VALIDATE_URL`, then its host is re-validated against
  `bp_activity_link_preview_is_blocked_host()`. On WP_Error, empty/invalid location,
  or a blocked (private/loopback) resolved host, resolution is aborted and the
  original URL is used. This routes the redirect resolution through the WP HTTP API
  (honours `WP_HTTP_BLOCK_EXTERNAL` and transport restrictions) and closes the
  residual SSRF gap where a malicious short-URL provider could redirect to an
  internal address.

### Fix 2 — Version drift 1.7.3 → 1.7.4 (LOW, blocking)
- Plugin header `Version:` → 1.7.4
- `BP_ACTIVITY_LINK_PREVIEW_VERSION` constant → 1.7.4
- `readme.txt` `Stable tag:` → 1.7.4
- Added `= 1.7.4 =` changelog stub in readme.txt describing the SSRF hardening.

### Verification
- `php -l`: No syntax errors detected.
- WPCS (wpcs MCP, `wpcs_check_file`): 7 errors + 2 warnings — **identical in count and
  source to the pre-fix baseline** (verified against `git show HEAD:` original). All are
  pre-existing, outside the edited regions (i18n translators comments, Yoda, inline
  comment punctuation in unrelated functions). **Zero new WPCS issues introduced.**
- Re-read the SSRF fix in context: confirmed the redirect-resolved host is passed
  through `bp_activity_link_preview_is_blocked_host()` before use.

Not committed / not pushed (per instructions).

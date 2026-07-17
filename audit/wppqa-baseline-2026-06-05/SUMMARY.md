# wppqa Baseline — 2026-06-05

Plugin: Activity Link Preview For BuddyPress (`buddypress-activity-link-preview`) v1.7.3, branch `v1.7.4`.
Run as Phase 0 of `/wp-plugin-onboard` (before manifest generation).

## Per-check results

| Check | Passed | Failed | Skipped | Verdict |
|---|---|---|---|---|
| `wppqa_check_plugin_dev_rules` | 8 | 1 | 0 | 1 finding — pre-triaged FALSE POSITIVE (see below) |
| `wppqa_check_rest_js_contract` | 0 | 0 | 1 | Skipped — plugin registers no own REST routes (only a `bp_rest_activity_prepare_value` filter) |
| `wppqa_check_wiring_completeness` | 0 | 0 | 1 | Skipped — no `includes/admin/` settings + no `templates/` dir |

## Findings

### [high] PLUGIN-DEV-RULES-001 — "Nonce check without capability check" @ bp-activity-link-preview.php:178
**Classification: FALSE POSITIVE (known heuristic limitation).**

The `wp_ajax_bp_activity_parse_url_preview` handler verifies the nonce at line 178 but the
checker does not recognize the authorization that is already present:

- Line 172: `if ( ! is_user_logged_in() ) { wp_send_json_error(...) }` — the handler hard-stops
  before the nonce check for anonymous requests.
- This is a "any logged-in member may fetch a link preview" endpoint. There is no narrower
  capability that fits (it is a member-facing composer feature, not an admin/privileged action).
- `is_user_logged_in()` IS the correct authorization gate here; a `current_user_can()` on a
  specific cap would be wrong for this feature.
- The endpoint also layers an SSRF guard (lines 196-204) blocking private/internal hosts.

The checker's `nonce-no-cap` rule does not treat `is_user_logged_in()` as an authorization
signal (it looks for `current_user_can`). Per the documented environment quirk, nopriv/public
member endpoints false-positive here. No code change warranted.

## Release-readiness note
Per Phase 0 gate semantics, `failed > 0`. The single failure is a documented false positive, so
this does NOT block release on its own. No genuine wppqa findings outstanding.

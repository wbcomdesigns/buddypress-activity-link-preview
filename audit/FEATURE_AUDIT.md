# Feature Audit — Activity Link Preview For BuddyPress

> Derived from `audit/manifest.json`. Single-file procedural plugin (`bp-activity-link-preview.php`, 1033 lines). No classes, no namespace, no admin UI.

Version 1.7.3 (header + constant + readme) · dev branch `v1.7.4` · requires BuddyPress **or** BuddyBoss Platform.

## 1. Frontend tabs / pages
_None._ The plugin has no pages of its own. It augments the existing BuddyPress activity stream (directory, group, and member-activity views) by injecting preview cards into activity/comment content.

## 2. AJAX handlers
| Action | File:line | Nonce | Capability | JS caller |
|---|---|---|---|---|
| `bp_activity_parse_url_preview` | `bp-activity-link-preview.php:91` → `:170` | `bp_activity_link_preview_nonce` | `is_user_logged_in()` + SSRF guard | `assets/js/bp-activity-link-preview.js:236` (`jQuery.post(ajaxurl, {action:'bp_activity_parse_url_preview', url, nonce, comment_id})`) |

Handler flow: logged-in gate → nonce verify → `FILTER_VALIDATE_URL` → SSRF host guard (blocks localhost / 127.0.0.1 / RFC1918 / reserved IPs) → `bp_activity_link_parse_url()` (oEmbed → internal-URL parse → OG/meta DOM scrape) → `apply_filters('bp_activity_parse_url_preview')` → `wp_send_json`.

## 3. REST endpoints
_No own routes._ One filter on the BuddyPress REST activity response:
| Filter | Handler | Effect |
|---|---|---|
| `bp_rest_activity_prepare_value` | `bp_activity_link_preview_data_embed_rest_api` (`:106` / `:987`) | Adds `bp_activity_link` (and `bp_activity_comment_link` for comments) to each activity item in `buddypress/v1` responses. Permission inherited from BP's endpoint. |

## 4. Admin pages / settings
_None._ The plugin registers **no** admin menu, submenu, options page, `register_setting`, or any `get_option`/`update_option`. Behavior is controlled entirely by code-level filters (see §10). The only admin-side output is a dependency-missing `admin_notices` error when BuddyPress/BuddyBoss is absent.

## 5. Shortcodes
_None._

## 6. Content types
_No CPTs / taxonomies / meta boxes._ State is stored as **activity meta**:
| Meta key | Object | Purpose |
|---|---|---|
| `_bp_activity_link_preview_data` | activity | Main-post preview payload (url/title/description/image_url) |
| `_bp_activity_comment_link_preview_data` | activity | Comment preview payload |

## 7. JS modules
| Path | AJAX calls | Key selectors |
|---|---|---|
| `assets/js/bp-activity-link-preview.js` (652 lines) | `bp_activity_parse_url_preview` | `#whats-new`, `.ac-input`, `.activity-link-preview-container`, `.activity-comment-link-preview-container`, `#whats-new-attachments`, `#comment-attachments-<id>`, `.ac-reply-submit`, `[id^="activity-comment-url-..."]` |

Notable JS behaviors: `ajaxSend` interceptor injects hidden `link_*` fields into BuddyBoss `post_update` requests (JSON + urlencoded formats); `ajaxComplete` re-initializes Twitter/Facebook widgets after stream refresh; per-widget dedupe via a `Set`; abortable in-flight request tracking.

## 8. CSS modules
| Path | Notes |
|---|---|
| `assets/css/bp-activity-link-preview.css` (9.6 KB) | Styles for `.activity-link-preview-container`, image holder/nav, excerpt, close buttons. Uses dashicons for nav/close glyphs. |

## 9. Email templates
_None._

## 10. Cron jobs
_None._

## 11. DB tables
_None._ Caching uses transients: `bp_oembed_<md5(url)>`, TTL `DAY_IN_SECONDS`.

## 12. Integrations
| Integration | Detection | Behavior |
|---|---|---|
| BuddyPress | `class_exists('BuddyPress')` | Required dependency; core integration target. |
| BuddyBoss Platform | `defined('BP_PLATFORM_VERSION')` / `class_exists('BuddyBoss_Platform')` | Removes BuddyBoss's own `bp_get_activity_content_body` preview filter (priority 20) to avoid duplicate cards; stands down entirely if BuddyBoss native preview is active; uses `bb_create_jwt` `bb-preview-token` for same-site private-network scrapes. |
| Youzify | `defined('YOUZIFY_VERSION')` / `class_exists('Youzify')` | Stands down if Youzify wall URL preview is enabled or `url_preview` meta exists. |
| Twitter/X SDK | external enqueue | `platform.twitter.com/widgets.js`; `twttr.widgets.createTweet`. |
| Facebook SDK | external enqueue + `#fb-root` div | `connect.facebook.net/.../sdk.js`; `FB.XFBML.parse()`. |

## 13. Capabilities
_No custom capabilities._ Authorization is `is_user_logged_in()` on the single AJAX endpoint.

## Developer extension filters (fired by plugin)
| Filter | Args | Default | Purpose |
|---|---|---|---|
| `bp_activity_link_preview_enable_comments` | `bool` | `true` | Master switch for comment previews. |
| `bp_activity_link_parse_url_shorten_url_provider` | `array` | bit.ly/snip.ly/rb.gy/tinyurl/... | Short-URL hosts to resolve. |
| `bp_activity_parse_url_preview` | `array,string` | parsed data | Modify AJAX preview payload. |
| `bp_activity_link_parse_url` | `array` | parsed data | Modify final parsed data. |
| `bp_oembed_discover_support` | `bool,string` | `false` | Toggle oEmbed discovery per URL. |

# Code Flows — Activity Link Preview For BuddyPress

> Derived from `audit/manifest.json`. All references are to `bp-activity-link-preview.php` unless noted.

## Bootstrap
```
plugins_loaded@20  (:113)
  └─ bp_activity_link_preview_is_bp_active()  (BuddyPress | BuddyBoss)
       ├─ true  → bp_activity_link_preview_bootstrap()  (:83) registers all hooks
       └─ false → admin_notices (dependency error)
admin_init (:75) → auto-deactivate if dependency missing
```

## Flow A — Compose-time link preview (the core UX)
```
User types URL in #whats-new / .ac-input
  → JS keyup/input (debounced 500ms)  [js:466 / js:474]
  → scrap_URL() → loadLinkPreview()  [js:149 / js:187]
  → jQuery.post(ajaxurl, action=bp_activity_parse_url_preview, url, nonce)  [js:236]
PHP: wp_ajax_bp_activity_parse_url_preview → bp_activity_parse_url_preview()  [:91 / :170]
  → is_user_logged_in() gate  [:172]
  → wp_verify_nonce('bp_activity_link_preview_nonce')  [:178]
  → FILTER_VALIDATE_URL + SSRF host guard  [:187 / :196-204]
  → bp_activity_link_parse_url($url)  [:231]
       ├─ short-URL provider? → resolve redirect (wp_safe_remote_get, file_get_contents fallback)
       ├─ transient hit (bp_oembed_<md5>)? → return cached
       ├─ oEmbed available? → wp_oembed_get()
       ├─ same-site? → bp_activity_link_parse_internal_url()  (member/group cards, no HTTP)  [:472]
       └─ else → wp_safe_remote_get + DOMDocument/XPath OG-meta scrape  [:342-437]
  → set_transient(DAY_IN_SECONDS)  [:442]
  → apply_filters('bp_activity_parse_url_preview') → wp_send_json  [:219 / :222]
JS: setURLResponse() renders preview card into #whats-new-attachments / #comment-attachments-<id>  [js:286]
     + injects hidden link_*/comment_link_* fields  [js:334]
```

## Flow B — Persist on activity save
```
BuddyPress saves activity → bp_activity_after_save@10  [:94]
  → bp_activity_link_preview_save_link_data($activity)  [:601]
       ├─ main: reads $_POST[link_url|link_title|link_description|link_image]
       │        (BuddyBoss: injected by JS ajaxSend interceptor [js:14])
       │        → bp_activity_update_meta('_bp_activity_link_preview_data')
       └─ comment (if enabled): reads comment_link_* OR extracts URL from content
                → bp_activity_update_meta('_bp_activity_comment_link_preview_data')
   (reddit.com URLs skipped; nonce handled upstream by BuddyPress)
```

## Flow C — Render on read (works with JS off for SAVED previews)
```
Main activity:  bp_get_activity_content_body@8  [:97]
  → bp_activity_link_preview_content_body_with_comments()  [:744]
       → skip if BuddyBoss/Youzify own-preview active; skip comments; per-request dedupe
       → bp_activity_get_meta('_bp_activity_link_preview_data')
       → bp_activity_link_preview_render_preview()  [:885]
            ├─ x.com/twitter.com → data-url div (JS hydrates tweet)
            ├─ facebook.com → .fb-post div
            └─ else → title/image/excerpt card (esc_url/esc_html/esc_attr)

Comment:  bp_activity_comment_content (registered via bp_init init_comment_filter)  [:873 / :871]
  → bp_activity_link_preview_comment_content()  [:809]
       → render from comment meta, or extract+parse+save then render
```

## Flow D — REST embed
```
GET buddypress/v1/activity → bp_rest_activity_prepare_value@10  [:106]
  → bp_activity_link_preview_data_embed_rest_api()  [:987]
       → adds response.data['bp_activity_link'] (+ ['bp_activity_comment_link'] for comments)
```

## Key files
| File | Role |
|---|---|
| `bp-activity-link-preview.php` | Everything: bootstrap, AJAX, parse, save, render, REST embed (20 functions) |
| `assets/js/bp-activity-link-preview.js` | Compose-time detection, AJAX call, card render, BuddyBoss interceptor, widget hydration |
| `assets/css/bp-activity-link-preview.css` | Preview card styling |

## AJAX chain
| JS trigger | Action | PHP handler | Response consumer |
|---|---|---|---|
| keyup on `#whats-new` / `.ac-input` | `bp_activity_parse_url_preview` | `bp_activity_parse_url_preview()` | `setURLResponse()` (js:286) |

## Roles / permissions
Any logged-in BuddyPress member can trigger the preview AJAX. No admin-only paths. No custom caps.

## Required settings
None — no settings layer exists. Behavior is filter-controlled (see FEATURE_AUDIT §"Developer extension filters").

## Dependencies
BuddyPress or BuddyBoss Platform (hard, auto-deactivates otherwise). Optional compat: BuddyBoss native preview, Youzify, Twitter/X SDK, Facebook SDK.

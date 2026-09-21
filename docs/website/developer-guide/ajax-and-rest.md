# AJAX Endpoint and REST API

## The parse AJAX endpoint

The composer fetches preview data through a single WordPress AJAX action. The plugin registers no REST routes of its own.

| Property | Value |
|---|---|
| Action | `bp_activity_parse_url_preview` |
| Transport | `admin-ajax.php` (POST) |
| Authentication | Logged-in users only (`is_user_logged_in()`) |
| Nonce | `bp_activity_link_preview_nonce` |
| No-priv | Not registered (guests cannot call it) |

### Request fields

- `url` the URL to preview. Validated with `FILTER_VALIDATE_URL`.
- `nonce` the nonce created for the composer.
- `comment_id` optional, present when the preview is for a comment.

### Response

On success the handler returns a JSON object with `title`, `description`, `images` and related fields. If the request is not authenticated or the nonce fails, it returns a `wp_send_json_error` shape with a `message`. If the URL is invalid, blocked by the SSRF guard, or produces no preview, it returns a JSON object with an `error` message that the composer shows inline.

The returned payload passes through the `bp_activity_parse_url_preview` filter before output.

## REST API integration

The plugin does not register its own REST routes. Instead it filters the BuddyPress activity REST response (`bp_rest_activity_prepare_value`) to embed the saved preview data. The endpoint permissions are inherited from the BuddyPress activity endpoint.

Every activity response gains a `bp_activity_link` field carrying the main-post preview data. When the activity is a comment, the response also gains a `bp_activity_comment_link` field with the comment's preview data.

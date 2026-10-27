# Data Storage and Caching

The plugin stores no options and creates no database tables. It uses BuddyPress activity meta for saved previews and WordPress transients for its cache.

## Activity meta

Preview data is saved on the activity when it is created, through the `bp_activity_after_save` hook.

| Meta key | Object | Holds |
|---|---|---|
| `_bp_activity_link_preview_data` | activity | Preview payload for a main activity post: `url`, `title`, `description`, `image_url`, and for oEmbed videos `wp_embed` and `embed_html`. |
| `_bp_activity_comment_link_preview_data` | activity | Preview payload for an activity comment, with the same shape. |

Scraped titles and descriptions are sanitized before they are stored. Preview data is not saved for `www.reddit.com` URLs.

## Rendering

Saved previews are appended to the activity content on display by filters, so they render on the server and show with JavaScript disabled:

- `bp_get_activity_content_body` for main activity posts.
- `bp_activity_comment_content` for comments (registered only when comment previews are enabled).

The `iframe`, `div`, `img`, `button`, `span` and `i` tags used by preview markup are added to BuddyPress's allowed activity tags through `bp_activity_allowed_tags`.

## Caching

The parser caches per URL in a transient.

| Aspect | Detail |
|---|---|
| Key pattern | `bp_oembed_<md5(url)>` |
| Success TTL | `DAY_IN_SECONDS` (24 hours) |
| Negative cache | A genuinely empty page is stored as a `bpalp_failed` sentinel for 15 minutes (filterable via `bp_activity_link_preview_negative_cache_ttl`). |
| Not cached | Transient failures (timeout, blocked request, or an oEmbed provider that returned empty once) are retried on the next request rather than cached. |

There is no scheduled cleanup. Transients expire on their own TTL, and a fixed preview reappears once its negative-cache window passes.

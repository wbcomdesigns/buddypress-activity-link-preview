# Comments, Videos and Native Embeds

## Previews in comments and replies

Link previews work in activity comments and replies, not just top-level activity posts. When a comment is saved, its preview data is stored separately from the parent post's preview data, and the card is appended to the comment content on display.

If a comment has no saved preview data but its content contains a URL, the plugin extracts the first URL and builds a card. During display this uses cached data only, so rendering a comment never waits on a live external fetch.

Comment previews can be turned off with the `bp_activity_link_preview_enable_comments` filter (see the Developer Guide). Previews on main activity posts are always on while the plugin is active and have no off switch.

## Native embeds for Twitter/X and Facebook

Twitter/X and Facebook block automated scraping but provide their own embed widgets. For these links the plugin outputs the network's own embed markup and loads the network's script so the widget renders:

- Twitter/X links render through `widgets.js` loaded from `platform.twitter.com`.
- Facebook links render through the Facebook SDK loaded from `connect.facebook.net`, and the plugin outputs the required `#fb-root` element.

These scripts are covered on the Performance and Security page and in the plugin's Third-Party Services notice.

## Inline video and rich embeds through oEmbed

Video and rich media links from oEmbed providers that WordPress supports (YouTube, Vimeo and others) render as an inline player. The embed HTML is generated on the server with `wp_oembed_get()` when the activity is saved and stored with the activity, so the player renders on display without a live external fetch. The `iframe` element used by these embeds is added to BuddyPress's allowed activity tags.

## Short URL resolution

Short URLs are resolved to their real destination before the preview is built, so the card reflects the final page rather than the shortener. The providers resolved by default are bit.ly, snip.ly, rb.gy, tinyurl.com, tiny.one, rotf.lol, b.link and 4ubr.short.gy. This list is filterable (see the Developer Guide).

## Reddit is skipped

Reddit links are deliberately not previewed. Preview data is not saved for `www.reddit.com` URLs.

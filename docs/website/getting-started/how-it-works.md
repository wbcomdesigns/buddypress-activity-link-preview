# How it works

## For your members

1. Go to the activity stream.
2. Paste any website URL into the activity composer.
3. The preview card appears under the composer within a second or two - title, description and image.
4. If the page offers several images, use the previous and next buttons under the thumbnail to choose one. The counter shows which image is selected, for example "Image 3 of 11".
5. Remove the image with the small close button on the thumbnail, or dismiss the whole preview with the close button at the top right of the card.
6. Post the update as usual. The image you selected is the one that gets saved.

The same flow works in activity comments and replies, not only in top-level posts.

If you delete the URL from the composer before posting, the preview card removes itself. If a URL cannot be previewed, an inline message explains why instead of the card silently failing to appear.

## What happens behind the scenes

When a member pastes a URL, the browser asks the site to parse it. The site then:

1. **Checks the cache.** Results are cached per URL for 24 hours, so a link that has already been shared does not get fetched again.
2. **Resolves short URLs.** Links from bit.ly, snip.ly, rb.gy, tinyurl.com, tiny.one, rotf.lol, b.link and 4ubr.short.gy are followed to their real destination first. The destination is re-checked against the security guard below.
3. **Checks for an internal link.** A link to a member profile (`/members/username/`) or a group (`/groups/group-slug/`) on your own site is built directly from your database - display name, profile About text and avatar for a member; name, description and avatar for a group. No HTTP request leaves the server, which keeps busy sites fast.
4. **Checks for a native embed.** Twitter/X and Facebook links are handed to the platform's own widget script. Video and other oEmbed providers are handled by WordPress.
5. **Reads the page.** For everything else, the plugin fetches the page and reads its Open Graph tags, then falls back to `<title>` and `<meta name="description">`.

## Why a preview sometimes does not appear

- The target site blocks automated requests. LinkedIn, Instagram and Reddit commonly do.
- The response is not HTML the plugin can parse.
- The URL resolves to a private, loopback or reserved IP address, which the security guard blocks on purpose.

Failed lookups are remembered for 15 minutes so a dead link does not get re-fetched on every page view. If you fix the target page, wait for that window to pass before retesting - or shorten it with the `bp_activity_link_preview_negative_cache_ttl` filter.

A genuine timeout is treated differently from "this page has nothing to preview". A momentary network hiccup is retried on the next view rather than being remembered for the full 15 minutes.

## Performance notes

- Plugin assets and the Twitter and Facebook scripts load only on BuddyPress activity screens - the activity directory, member activity and group screens - never site-wide. Use `bp_activity_link_preview_load_assets` to change that.
- Comment rendering never fetches a remote URL, so a slow external site can never delay a page load.

# Frequently Asked Questions

## What are the plugin requirements?

WordPress 6.5 or higher, PHP 8.0 or higher, and either BuddyPress 6.0+ or BuddyBoss Platform. If neither BuddyPress nor BuddyBoss is active, the plugin deactivates itself and shows a notice explaining why.

## Is there a settings page?

No. The plugin works as soon as it is activated. Behaviour is changed through developer filters, listed in the Developer Guide.

## Why doesn't the preview show for some URLs?

Open Graph tags are preferred, not required. The plugin looks for `og:title`, `og:description` and `og:image` first, and falls back to the page `<title>`, the `<meta name="description">` tag, and the page's images. So a page with no Open Graph tags will usually still preview.

A preview fails when the site blocks automated requests outright, when the response is not HTML the plugin can parse, or when the URL resolves to a private or loopback address that the SSRF guard blocks. Reddit links are deliberately skipped. Genuinely empty pages are remembered for 15 minutes, so if you fix the target page, wait for that window to pass before retesting.

## How do I disable link previews in comments?

Add this to your theme's `functions.php`:

```php
add_filter( 'bp_activity_link_preview_enable_comments', '__return_false' );
```

This filter only controls previews on activity comments and replies. There is no filter to switch off previews on main activity posts, and no per-user setting; previews on posts are always on while the plugin is active.

## Is the plugin secure?

Yes. It includes SSRF protection (blocks internal and private IPs and re-validates short-URL redirect targets), CSRF protection through nonce verification, logged-in-only URL parsing, sanitization of scraped titles and descriptions on save, and escaping on output.

## Does it work with BuddyBoss?

Yes, and it deliberately gets out of the way of BuddyBoss's own link preview. If BuddyBoss Platform is active with its native link preview enabled, this plugin stands down so you never see two previews on the same post. Turn the BuddyBoss link preview off and this plugin takes over.

## Does it work with Youzify?

Same idea. If Youzify is active with its wall URL preview enabled, this plugin leaves the content alone so Youzify's preview is the only one shown. Turn the Youzify wall URL preview off and this plugin handles the card instead.

## What happens when someone shares a link to a profile or a group on my own site?

The plugin recognises it as an internal URL and builds the card straight from your database, with no external HTTP request. A member link shows the display name, the XProfile About text and the member's avatar. A group link shows the group name, description and avatar.

## Can I customize it?

Yes. There is no settings screen, but the plugin exposes filters for the parts site owners usually change: `bp_activity_link_preview_load_assets`, `bp_activity_parse_url_preview`, `bp_activity_link_parse_url`, `bp_activity_link_parse_url_shorten_url_provider`, `bp_oembed_discover_support`, `bp_activity_link_preview_enable_comments`, and `bp_activity_link_preview_negative_cache_ttl`. See the Developer Guide.

## Will this work with my theme?

It has been tested with popular BuddyPress and BuddyBoss themes including BuddyX, Reign and Youzify. It hooks the standard BuddyPress activity filters, so it should work with any properly coded BuddyPress-compatible theme.

## Are there translations?

The plugin ships a POT template and translations for German, Spanish, French, Italian and Portuguese (Brazil), and includes RTL stylesheet support.

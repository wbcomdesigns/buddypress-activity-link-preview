# Performance and Security

## Caching

- **Per-URL cache.** A successful parse is cached in a transient keyed on the URL (`bp_oembed_<md5(url)>`) for 24 hours, so the same link is not re-fetched on every view.
- **Negative caching.** A page that was fetched successfully but has nothing to preview is remembered for 15 minutes, so a genuinely empty page is not re-scraped on every request. The window is filterable with `bp_activity_link_preview_negative_cache_ttl`.
- **Transient failures are not cached.** A timeout, a blocked request, or an oEmbed provider that comes back empty once is not negative-cached, so a good link (a YouTube video, for example) is retried on the next view rather than showing no preview for the whole window.

## Rendering never blocks on a remote fetch

When previews are shown on the activity stream, comment rendering uses cached data only and never fetches a remote URL. Fetching happens at compose time (through the AJAX endpoint) and at save time, not during a page view, so a slow or dead link cannot delay a page load.

## Asset loading

The plugin's CSS and JavaScript, the Twitter and Facebook SDK scripts, and dashicons load only on BuddyPress activity contexts (the activity directory, member activity, and group screens), not site-wide. Which pages load the assets can be changed with the `bp_activity_link_preview_load_assets` filter.

## Third-party scripts and privacy

On activity screens the Twitter/X and Facebook scripts load whether or not a Twitter or Facebook link is present on the page. The visitor's browser therefore contacts Twitter and Facebook on every activity page view, and those services can see the visitor's IP address, user agent and referring page, and may set their own cookies. No data is sent to Wbcom Designs, and the plugin itself collects nothing.

To stop the assets (and therefore those third-party scripts) from loading, for example to satisfy a consent requirement:

```php
add_filter( 'bp_activity_link_preview_load_assets', '__return_false' );
```

## Security measures

- **SSRF protection.** Before any fetch, the AJAX endpoint blocks requests to `localhost`, `127.0.0.1`, and private or reserved IP ranges (the 10.x, 172.16 to 172.31, and 192.168 ranges, plus IPs in reserved ranges). Redirect targets from short-URL resolution are re-validated against the same guard.
- **CSRF protection.** The parse endpoint verifies a nonce (`bp_activity_link_preview_nonce`).
- **Logged-in only.** Only logged-in users can call the parse endpoint.
- **Input validation.** URLs are validated with `FILTER_VALIDATE_URL` on input and on resolved redirect targets.
- **Safe output.** Scraped titles and descriptions are sanitized before being saved to activity meta, and escaped on output.
- **Safe HTTP.** Remote requests use `wp_safe_remote_get()` and `wp_safe_remote_head()`, which honour WordPress's HTTP settings.

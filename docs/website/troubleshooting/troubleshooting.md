# Troubleshooting

## The plugin keeps deactivating itself

The plugin requires BuddyPress or BuddyBoss Platform. If neither is active, it deactivates on the next admin request and shows the notice "Activity Link Preview For BuddyPress requires BuddyPress or BuddyBoss Platform to be installed and active." Activate BuddyPress or BuddyBoss Platform first, then activate this plugin.

## No preview appears for a link

Work through these in order:

1. **The page blocks scraping.** Some sites reject automated requests. Twitter/X and Facebook block scraping but are handled through their native embeds; other sites that block requests will not preview.
2. **The response is not parseable HTML.** The plugin reads Open Graph tags, the `<title>`, the meta description and images from an HTML page. A non-HTML response cannot be parsed.
3. **It is a Reddit link.** Reddit links are deliberately skipped.
4. **The URL is blocked by the SSRF guard.** URLs pointing at `localhost`, `127.0.0.1`, or private/reserved IP ranges are blocked and show "This URL cannot be previewed for security reasons."
5. **A recent failure is cached.** A genuinely empty page is remembered for 15 minutes. If you fixed the target page, wait for that window to pass, or shorten it with `bp_activity_link_preview_negative_cache_ttl`.

## A YouTube or Vimeo preview did not show once, then worked on refresh

A momentary provider hiccup is treated as a transient failure and is not cached, so the link is retried on the next view. Reload the activity and the embed should appear.

## Two preview cards on one post

This means another link-preview feature is also active. The plugin stands down for BuddyBoss's native link preview and for Youzify's wall URL preview to prevent this. If you see duplicates, check whether both features are enabled and turn one off. On BuddyBoss, either use its native preview or disable it and let this plugin render the card, not both.

## Previews load third-party scripts you want to block

On activity screens the Twitter/X and Facebook scripts load whether or not such a link is present. To stop the plugin's assets and those scripts from loading:

```php
add_filter( 'bp_activity_link_preview_load_assets', '__return_false' );
```

## Composer preview controls appear in English on a translated site

The composer controls (Cancel Preview, previous/next image, the image counter) are translatable from version 1.7.4 onward. Make sure the plugin's translation for your locale is installed and that WordPress is set to that locale.

## Previews do not appear on a custom page that embeds an activity stream

Assets only load on standard BuddyPress activity contexts. If your activity stream is embedded on a custom page (for example via shortcode or widget), opt that page in:

```php
add_filter( 'bp_activity_link_preview_load_assets', function ( $should_load ) {
    if ( is_page( 'community' ) ) {
        return true;
    }
    return $should_load;
} );
```

## Getting support

- Documentation: https://docs.wbcomdesigns.com/
- Support: https://wbcomdesigns.com/support/
- Report bugs or request features: https://wbcomdesigns.com/contact/

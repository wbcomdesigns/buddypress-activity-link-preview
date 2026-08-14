# Filters

This plugin has no settings page. Filters are the only way to change its behaviour, so this page is the configuration reference.

Add these to your theme's `functions.php` or, better, to a small site-specific plugin.

---

## `bp_activity_link_preview_load_assets`

Controls whether the plugin's CSS and JavaScript - and the Twitter and Facebook scripts - load on the current page.

```php
apply_filters( 'bp_activity_link_preview_load_assets', bool $should_load );
```

By default this is true on the activity directory, member activity screens and group screens. Use it to load assets on a custom page that embeds an activity stream through a shortcode or widget, or to stop the third-party scripts loading at all.

```php
// Never load the Twitter and Facebook scripts.
add_filter( 'bp_activity_link_preview_load_assets', '__return_false' );

// Also load on a custom page that embeds a stream.
add_filter( 'bp_activity_link_preview_load_assets', function ( $should_load ) {
    return $should_load || is_page( 'community-feed' );
} );
```

---

## `bp_activity_link_preview_enable_comments`

Turns link preview handling in activity comments and replies on or off. Top-level activity previews are unaffected.

```php
apply_filters( 'bp_activity_link_preview_enable_comments', bool $enabled );
```

```php
// Previews in top-level posts only.
add_filter( 'bp_activity_link_preview_enable_comments', '__return_false' );
```

---

## `bp_activity_link_preview_negative_cache_ttl`

How long a failed lookup is remembered before the plugin tries that URL again. Defaults to 15 minutes.

```php
apply_filters( 'bp_activity_link_preview_negative_cache_ttl', int $ttl, string $url );
```

```php
// Retry failed links after 5 minutes instead of 15.
add_filter( 'bp_activity_link_preview_negative_cache_ttl', function ( $ttl, $url ) {
    return 5 * MINUTE_IN_SECONDS;
}, 10, 2 );
```

A genuine timeout is never negative-cached, so this only affects pages that returned a real response with nothing to preview.

---

## `bp_activity_link_parse_url_shorten_url_provider`

The list of URL-shortener hosts that get followed to their destination before the preview is built.

```php
apply_filters( 'bp_activity_link_parse_url_shorten_url_provider', array $hosts );
```

Default: `bit.ly`, `snip.ly`, `rb.gy`, `tinyurl.com`, `tiny.one`, `rotf.lol`, `b.link`, `4ubr.short.gy`.

```php
// Add your own branded shortener.
add_filter( 'bp_activity_link_parse_url_shorten_url_provider', function ( $hosts ) {
    $hosts[] = 'go.example.com';
    return $hosts;
} );
```

Resolved destinations are re-checked against the private and loopback IP guard, so adding a host here does not weaken that protection.

---

## `bp_activity_link_parse_url`

Filters the parsed result just before it is returned, whether it came from cache, from an internal lookup or from a live fetch.

```php
apply_filters( 'bp_activity_link_parse_url', array $parsed_url_data );
```

The array typically contains `title`, `description`, `images` (an array of URLs) and, for oEmbed content, `wp_embed` and `embed_html`. It can be empty when nothing could be parsed.

```php
// Force a house description onto previews of your own docs site.
add_filter( 'bp_activity_link_parse_url', function ( $data ) {
    if ( ! empty( $data['url'] ) && str_contains( $data['url'], 'docs.example.com' ) ) {
        $data['description'] = 'Documentation';
    }
    return $data;
} );
```

---

## `bp_activity_parse_url_preview`

Filters the payload the AJAX endpoint returns to the composer. This is the last point before the data reaches the browser.

```php
apply_filters( 'bp_activity_parse_url_preview', array $parse_url_data, string $url );
```

Use this to change what the member sees in the composer without changing what gets stored. To cap how many images the composer offers, for example:

```php
add_filter( 'bp_activity_parse_url_preview', function ( $data, $url ) {
    if ( ! empty( $data['images'] ) && is_array( $data['images'] ) ) {
        $data['images'] = array_slice( $data['images'], 0, 10 );
    }
    return $data;
}, 10, 2 );
```

---

## `bp_oembed_discover_support`

Whether to attempt oEmbed discovery for a provider WordPress does not already know about. Defaults to false, because discovery costs an extra request to an untrusted host.

```php
apply_filters( 'bp_oembed_discover_support', bool $discover, string $url );
```

```php
// Allow discovery for one trusted internal video host.
add_filter( 'bp_oembed_discover_support', function ( $discover, $url ) {
    return str_contains( $url, 'video.example.com' ) ? true : $discover;
}, 10, 2 );
```

---

## Where preview data is stored

Two activity meta keys, if you need to read or clear them directly:

| Key | Holds |
|---|---|
| `_bp_activity_link_preview_data` | Preview for a top-level activity |
| `_bp_activity_comment_link_preview_data` | Preview for an activity comment |

Both store an array with `url`, `title`, `description` and `image_url`, plus `wp_embed` and `embed_html` for oEmbed content.

Parsed results are cached in a transient keyed `bp_oembed_<md5 of the url>` for 24 hours.

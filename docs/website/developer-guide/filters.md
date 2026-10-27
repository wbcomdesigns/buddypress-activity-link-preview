# Filters

The plugin has no settings screen. All behaviour changes are made through filters. These are every filter the plugin exposes in code.

## bp_activity_link_preview_load_assets

Controls whether the plugin's CSS, JavaScript and the Twitter/Facebook SDK scripts are enqueued on the current request. By default they load on BuddyPress activity contexts (the activity directory, member activity and group screens).

```php
// Stop all assets (and the third-party scripts) from loading anywhere.
add_filter( 'bp_activity_link_preview_load_assets', '__return_false' );

// Or load them on a custom page that embeds an activity stream.
add_filter( 'bp_activity_link_preview_load_assets', function ( $should_load ) {
    if ( is_page( 'community' ) ) {
        return true;
    }
    return $should_load;
} );
```

Argument: `bool $should_load`.

## bp_activity_link_preview_enable_comments

Master switch for link previews in activity comments and replies. Default `true`. It has no effect on previews for main activity posts, which are always on.

```php
// Turn off comment link previews everywhere.
add_filter( 'bp_activity_link_preview_enable_comments', '__return_false' );

// Or disable them only in groups.
add_filter( 'bp_activity_link_preview_enable_comments', function ( $enabled ) {
    if ( bp_is_group() ) {
        return false;
    }
    return $enabled;
} );
```

Argument: `bool $enabled`.

## bp_activity_link_parse_url_shorten_url_provider

Filters the list of short-URL host providers that trigger redirect resolution. The defaults are bit.ly, snip.ly, rb.gy, tinyurl.com, tiny.one, rotf.lol, b.link and 4ubr.short.gy.

```php
add_filter( 'bp_activity_link_parse_url_shorten_url_provider', function ( $providers ) {
    $providers[] = 'ow.ly';
    return $providers;
} );
```

Argument: `array $providers`.

## bp_oembed_discover_support

Toggles oEmbed discovery for a given URL. Default `false`, meaning only known oEmbed providers are used. Return `true` to let WordPress attempt discovery on an unknown URL.

```php
add_filter( 'bp_oembed_discover_support', function ( $discover, $url ) {
    return true;
}, 10, 2 );
```

Arguments: `bool $discover`, `string $url`.

## bp_activity_parse_url_preview

Filters the preview payload returned by the AJAX parse endpoint, just before it is sent to the composer as JSON.

```php
add_filter( 'bp_activity_parse_url_preview', function ( $data, $url ) {
    // Adjust $data before it reaches the composer.
    return $data;
}, 10, 2 );
```

Arguments: `array $parse_url_data`, `string $url`.

## bp_activity_link_parse_url

Filters the final parsed URL data. It fires on every return path of the parser (cached-only miss, internal-URL hit and miss, oEmbed hit, and the external scrape path), so it is the single place to adjust parsed data regardless of how it was produced.

```php
add_filter( 'bp_activity_link_parse_url', function ( $data ) {
    return $data;
} );
```

Argument: `array $parsed_url_data`.

## bp_activity_link_preview_negative_cache_ttl

Filters how long a genuinely empty page is remembered before it is re-fetched. Default is 15 minutes (`15 * MINUTE_IN_SECONDS`). Return `0` to disable negative caching.

```php
add_filter( 'bp_activity_link_preview_negative_cache_ttl', function ( $ttl, $url ) {
    return 5 * MINUTE_IN_SECONDS;
}, 10, 2 );
```

Arguments: `int $ttl` (seconds), `string $url`.

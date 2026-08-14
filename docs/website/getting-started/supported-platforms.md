# Supported platforms and limits

## How each kind of link is previewed

| Link type | How it renders | Needs JavaScript in the browser |
|---|---|---|
| Ordinary website | Scraped card: title, description, image | No - rendered server-side |
| Member profile on your site (`/members/username/`) | Card built from your database, no HTTP request | No |
| Group on your site (`/groups/group-slug/`) | Card built from your database, no HTTP request | No |
| YouTube, Vimeo, other oEmbed providers | Embedded player, generated at save time | No |
| Twitter / X | Native embed via Twitter's widget script | **Yes** |
| Facebook | Native embed via the Facebook SDK | **Yes** |
| Short URLs (bit.ly and similar) | Resolved to the destination, then previewed as above | Depends on destination |
| Reddit | Skipped deliberately | - |

Twitter/X and Facebook are the only two link types that require JavaScript, because both networks will only render their own embeds through their own script. Everything else displays with JavaScript disabled.

## Third-party requests

Rendering native Twitter/X and Facebook embeds means loading each network's script directly from the network:

- `platform.twitter.com/widgets.js` - [terms](https://twitter.com/en/tos), [privacy](https://twitter.com/en/privacy)
- `connect.facebook.net` SDK - [terms](https://www.facebook.com/terms.php), [privacy](https://www.facebook.com/privacy/policy/)

These load on BuddyPress activity screens only, never site-wide. On those screens they load whether or not a Twitter or Facebook link is present, so a visitor's browser contacts Twitter and Facebook on every activity page view. Those services can therefore see the visitor's IP address, user agent and referring page, and may set their own cookies.

If your site needs to avoid that - to satisfy a consent requirement, for example - stop the assets loading:

```php
add_filter( 'bp_activity_link_preview_load_assets', '__return_false' );
```

No data is sent to Wbcom Designs, and the plugin itself collects nothing.

## Known limits

- **LinkedIn, Instagram and Reddit** will usually produce no preview. LinkedIn and Instagram block automated requests; Reddit is skipped on purpose. LinkedIn has never had special support, despite an earlier version of this documentation saying so.
- **Pages behind a login** cannot be previewed, because the request is unauthenticated.
- **Private and loopback addresses are refused.** The plugin blocks localhost, RFC1918 ranges and reserved IPs, and re-validates the destination after following a redirect. This is a deliberate security guard against server-side request forgery and is not configurable.
- **Only logged-in members can generate previews.** The parse endpoint requires a logged-in user and a valid nonce.
- **Image count is not capped.** A page offering 60 images produces a 60-entry list in the cached record. Only one image is ever placed in the page, so this costs payload size rather than memory or bandwidth.

## Working alongside other plugins

- On **BuddyBoss Platform** with its own link preview enabled, this plugin stands down so you never get two cards on one post.
- On **Youzify** with its wall URL preview enabled, it does the same.

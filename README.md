# Activity Link Preview For BuddyPress

Turns a pasted URL into a rich preview card in the BuddyPress and BuddyBoss activity stream.

## What it does

- Detects a link as a member types or pastes it into the activity form or a comment, and shows a card with title, description and image before they post.
- Lets the member pick between several images when the page offers more than one.
- Builds cards for links to your own member profiles and groups from the database, with no external request.
- Shows native Twitter/X and Facebook embeds, and inline video through WordPress oEmbed.
- Resolves short URLs (bit.ly, tinyurl and similar) before building the card.
- Works in activity comments and replies, and in forms placed outside BuddyPress pages, such as BuddyPress Post From Anywhere and the `[activity-listing]` shortcode.
- Caches results per URL, blocks private and reserved addresses, and stops reading a remote page after 1 MB.
- Stands down when BuddyBoss or Youzify already shows its own link preview, so a post never gets two cards.

There is no settings page. Previews work as soon as the plugin is active, and developers adjust behaviour through filters.

## Who it is for

BuddyPress and BuddyBoss communities where members share articles, videos and news, and site owners who want links in the activity stream to look like cards instead of bare URLs.

## Get the plugin

- Store: https://wbcomdesigns.com/downloads/buddypress-activity-link-preview/
- WordPress.org: https://wordpress.org/plugins/activity-link-preview-for-buddypress/

## Documentation

- Docs: https://docs.wbcomdesigns.com/bp-family/buddypress-activity-link-preview/
- Release notes: https://wbcomdesigns.com/release-notes/buddypress-activity-link-preview/
- Source of the customer docs: [`docs/website/`](docs/website/)

## Requirements

- WordPress 6.5 or higher
- PHP 8.1 or higher
- BuddyPress 12.0 or higher, or BuddyBoss Platform (the plugin deactivates itself if neither is active)

## Installation

1. Download the plugin zip, then go to Plugins > Add New > Upload Plugin and upload it.
2. Activate it.
3. Post a link in the activity form to see the preview.

## For developers

- Text domain: `buddypress-activity-link-preview`. No PHP namespace; functions are prefixed `bp_activity_link_preview_`.
- One logged-in AJAX action, `bp_activity_parse_url_preview`, protected by a nonce. No custom REST routes; preview data is added to BuddyPress REST activity responses.
- Seven filters, documented in the [filters reference](docs/website/developer-guide/filters.md): `bp_activity_link_preview_load_assets`, `bp_activity_parse_url_preview`, `bp_activity_link_parse_url`, `bp_activity_link_parse_url_shorten_url_provider`, `bp_oembed_discover_support`, `bp_activity_link_preview_enable_comments`, `bp_activity_link_preview_negative_cache_ttl`.
- No database tables, WP-CLI commands, blocks or template overrides.

## Support and licence

- Support: https://wbcomdesigns.com/support/
- Licence: GPL-2.0-or-later

# Introduction

Activity Link Preview For BuddyPress turns a plain URL into a rich card. When a member pastes a link into the activity composer or into a comment, the plugin reads the linked page, pulls out a title, description and image, and shows a preview card before they post.

Saved previews are rendered server-side, so they still display for visitors browsing with JavaScript disabled.

## What you need

- WordPress 6.5 or higher
- PHP 8.0 or higher
- BuddyPress 6.0+ **or** BuddyBoss Platform

The plugin deactivates itself with an admin notice if neither BuddyPress nor BuddyBoss Platform is active.

## There is no settings page

This is deliberate. Previews work the moment you activate the plugin, with no configuration step. Everything that can be changed is changed in code, through filters - see [Filters](../developer-guide/filters.md).

## Which platforms get special handling

Two platforms are rendered as native embeds using the platform's own widget script rather than as a scraped card:

- **Twitter / X** (`twitter.com`, `x.com`)
- **Facebook** (`facebook.com`)

Video and other rich media - YouTube, Vimeo and any other provider WordPress supports - are embedded through the WordPress oEmbed system.

Every other URL is previewed by reading the page's Open Graph tags, falling back to the standard `<title>` and `<meta name="description">` tags, so most sites preview without doing anything special.

> **Note:** earlier versions of this documentation listed LinkedIn as a specially supported platform. That was never accurate and the claim has been removed. LinkedIn links are previewed the same way as any other website, and LinkedIn blocks automated requests, so a LinkedIn link will often produce no preview at all.

Reddit links are deliberately skipped.

## Related

- [How it works](how-it-works.md)
- [Supported platforms and limits](supported-platforms.md)
- [Filters](../developer-guide/filters.md)
- [REST API](../developer-guide/rest-api.md)

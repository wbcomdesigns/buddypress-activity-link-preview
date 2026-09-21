# Introduction

Activity Link Preview For BuddyPress turns a plain URL into a rich card. When a member pastes a link into the activity composer or a comment, the plugin reads the linked page, pulls a title, description and image, and shows a preview card before the member posts. Once the activity is saved, the card is rendered on the server, so it still displays when JavaScript is disabled.

## What it does

- Detects a URL as it is typed or pasted into the activity form and builds a preview card in the composer.
- Lets the member pick between multiple images when the page offers more than one, with an "Image X of Y" counter and previous/next buttons.
- Builds cards for internal member profile and group links straight from your own database, with no external request.
- Renders link previews in activity comments and replies, not just top-level posts.
- Shows native embeds for Twitter/X and Facebook, and inline video and rich embeds through WordPress oEmbed (YouTube, Vimeo and other providers WordPress supports).
- Resolves short URLs (bit.ly, tinyurl and similar) to their real destination before building the card.
- Includes the saved preview data in the BuddyPress REST API activity response.

## No settings page

The plugin has no settings screen. Previews start working as soon as you activate the plugin. Behaviour is changed through developer filters, which are covered in the Developer Guide.

## Requirements at a glance

The plugin needs BuddyPress or BuddyBoss Platform to be active. If neither is active, the plugin deactivates itself and shows an admin notice explaining why. See the Requirements page for the full list.

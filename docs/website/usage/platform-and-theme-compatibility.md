# Platform and Theme Compatibility

The plugin is designed to sit next to other tools that also build link previews, without producing two cards on the same post.

## BuddyPress

The plugin hooks the standard BuddyPress activity filters (`bp_get_activity_content_body` for posts and `bp_activity_comment_content` for comments). It should work with any properly coded BuddyPress-compatible theme.

## BuddyBoss Platform

The plugin runs on BuddyBoss Platform, but it deliberately gets out of the way of BuddyBoss's own link preview:

- If BuddyBoss Platform is active with its native link preview enabled, this plugin stands down and lets BuddyBoss render the card, so there are never two previews on one post.
- To avoid a duplicate card, the plugin also removes BuddyBoss's own link preview filter when both are active.
- If you turn the BuddyBoss link preview off, this plugin takes over.

For same-site scrapes on a BuddyBoss private network, the plugin attaches a short-lived BuddyBoss preview token so the fetch is authorised.

## Youzify

The behaviour mirrors BuddyBoss. If Youzify is active and its wall URL preview is enabled, or Youzify has already saved its own `url_preview` data for an activity, this plugin leaves the content alone so Youzify's preview is the only one shown. Turn the Youzify wall URL preview off and this plugin handles the card instead.

## Themes

The plugin has been tested with popular BuddyPress and BuddyBoss themes including BuddyX, Reign and Youzify. Because it hooks the standard activity filters rather than theme-specific markup, it does not depend on a particular theme. The preview stylesheet reads theme colour tokens and includes dark mode and RTL support.

=== Activity Link Preview For BuddyPress ===
Contributors: wbcomdesigns, vapvarun
Donate link: https://wbcomdesigns.com/donate/
Tags: buddypress, activity, link preview, social, open graph
Requires at least: 6.5
Tested up to: 7.0
Stable tag: 1.7.6
Requires PHP: 8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: buddypress-activity-link-preview
Domain Path: /languages

BuddyPress activity link preview displays image, title and description from websites when links are shared in activity posts.

== Description ==

**Activity Link Preview For BuddyPress** turns a plain URL into a rich card. When a member pastes a link into the activity composer or a comment, the plugin reads the page, pulls a title, description and image, and shows a preview card before they post. Saved previews render server-side, so they still display when JavaScript is off.

It needs BuddyPress or BuddyBoss Platform active. There is no settings page: previews work as soon as you activate the plugin, and developers can change behaviour through filters.

= What you get =

**Rich previews as members type**
* Detects URLs as they are typed or pasted into the activity form.
* Shows title, description and a featured image in a card the member can review before posting.
* Multiple image selection: when a page offers several images, prev and next buttons let the member pick one, with an "Image X of Y" counter.
* The preview clears itself when the URL is removed from the composer, and blocked or invalid URLs show an inline error instead of failing quietly.

**Internal member and group cards, with no HTTP request**
* Sharing a link to a member profile on your own site (/members/username/) builds a card from your database: display name, the XProfile About text, and the member's avatar.
* Sharing a link to a group (/groups/group-slug/) builds a card with the group name, description and group avatar.
* Because this reads locally, there is no self-request and no external fetch, which keeps busy sites fast.

**Comments and embeds**
* Link previews work in activity comments and replies, not just top-level posts.
* Native embeds for Twitter/X and Facebook.
* Inline video and rich embeds via WordPress oEmbed (YouTube, Vimeo and other providers WordPress supports).
* Short URLs (bit.ly, tinyurl and similar) are resolved to the real destination before the preview is built.

**Fast and safe by default**
* Per-URL caching, plus 15-minute negative caching so a slow or unreachable link is not retried on every render.
* Comment rendering never fetches remote URLs, so a dead link cannot delay a page load.
* SSRF protection blocks localhost, private and reserved IP ranges, and re-validates redirect targets.
* Nonce verification, logged-in-only parsing, and sanitized and escaped output.

**Works alongside your platform**
* On BuddyBoss Platform with its own link preview enabled, this plugin stands down so you never get two cards on one post.
* On Youzify with its wall URL preview enabled, it does the same.
* Preview data is included in the BuddyPress REST API activity response.

**Developer friendly**
* `bp_activity_link_preview_load_assets` - control which pages load the CSS, JS and social SDKs.
* `bp_activity_parse_url_preview` - filter the preview data returned by the parse endpoint.
* `bp_activity_link_parse_url` - filter the parsed result before it is returned.
* `bp_activity_link_parse_url_shorten_url_provider` - add or remove short-URL providers to resolve.
* `bp_oembed_discover_support` - opt in to oEmbed discovery for unknown providers.
* `bp_activity_link_preview_enable_comments` - turn preview handling in activity comments on or off.

= Perfect For =

* BuddyPress and BuddyBoss communities where members share articles, videos and news
* Groups that use the activity stream as a reading or link-sharing feed
* Communities that link internally to member profiles and groups
* Any activity stream that currently shows bare, unclickable-looking URLs

= Premium Support =

Our support team can help with setup, theme compatibility and troubleshooting. Reach us through the links below.

= Documentation =

* **[Documentation and Support](https://docs.wbcomdesigns.com/)** - Setup walkthrough and usage guides for every Wbcom plugin

= Translations =

* English (default)
* Ready for translation in your language with the included POT file
* RTL language support included

= Links =

* [Plugin Homepage](https://wbcomdesigns.com/downloads/buddypress-activity-link-preview/)
* [Documentation](https://docs.wbcomdesigns.com/)
* [Support](https://wbcomdesigns.com/support/)
* [Request Features](https://wbcomdesigns.com/contact/)

= Compatibility =

* WordPress 6.5 and higher
* PHP 8.0 and higher
* BuddyPress 6.0+ or BuddyBoss Platform (required - the plugin deactivates itself if neither is active)
* Tested with popular themes including BuddyX, Reign and Youzify

= What's New in 1.7.4 =

A performance and security pass. Plugin assets and the Twitter and Facebook SDKs now load only in activity contexts instead of on every page. Failed link lookups are cached for 15 minutes and comment rendering never fetches remote URLs, so slow links no longer hold up a page. Blocked or invalid URLs now report the problem in the composer, scraped titles and descriptions are sanitized before saving, and short-URL resolution re-validates its redirect target against the SSRF guard.

== Third-Party Services ==

This plugin renders native Twitter/X and Facebook embeds. Those embeds can only be rendered by each network's own script, so the plugin loads that script directly from the network:

* Twitter/X widgets.js, loaded from platform.twitter.com. Terms of service: https://twitter.com/en/tos - Privacy policy: https://twitter.com/en/privacy
* Facebook SDK, loaded from connect.facebook.net. Terms of service: https://www.facebook.com/terms.php - Privacy policy: https://www.facebook.com/privacy/policy/

These scripts load only on BuddyPress activity screens (the activity directory, member activity and group screens), never site-wide. On those screens they load whether or not a Twitter or Facebook link is actually present, so the visitor's browser contacts Twitter and Facebook on every activity page view. Those services can therefore see the visitor's IP address, user agent and referring page, and may set their own cookies.

If your site needs to avoid that (for example to satisfy a consent requirement), use the bp_activity_link_preview_load_assets filter to stop the assets loading:

`add_filter( 'bp_activity_link_preview_load_assets', '__return_false' );`

No data is sent to Wbcom Designs, and the plugin itself collects nothing.

== More Free Tools from Wbcom Designs ==

Rich link previews make your activity stream worth reading, but a stream is only one part of a community. These other free tools from Wbcom Designs fill in the rest of the space your members spend time in, from the theme and social network itself to forums, media, events, gamification, directories, jobs, and courses.

* **[BuddyX](https://wbcomdesigns.com/downloads/buddyx-theme/)** - A free, fast community theme for BuddyPress, BuddyBoss and PeepSo with a modern layout and dark mode.
* **[BuddyNext](https://wbcomdesigns.com/downloads/buddynext/)** - Stand up a complete WordPress community with activity streams, member spaces, profiles, direct messaging, and built-in moderation.
* **[Jetonomy](https://wbcomdesigns.com/downloads/jetonomy/)** - Add forums, question-and-answer boards, and idea spaces that stay tidy through trust-based auto-moderation even past 100,000 topics.
* **[Mediaverse](https://wbcomdesigns.com/downloads/mediaverse/)** - Let members build photo and video albums, react, follow each other, and message privately while AI moderation keeps things clean.
* **[Eventonomy](https://wbcomdesigns.com/downloads/eventonomy/)** - Run community events with RSVPs, calendars, and front-end submissions.
* **[WB Gamification](https://wbcomdesigns.com/downloads/wordpress-gamification-plugin/)** - Reward members with points, badges, and leaderboards to keep engagement high.
* **[Listora](https://wbcomdesigns.com/downloads/listora/)** - Publish searchable directories across ten listing types with reviews, maps, and member-submitted entries from the front end.
* **[WP Career Board](https://wbcomdesigns.com/downloads/wp-career-board/)** - Add a job board with front-end listings, applications, and employer profiles.
* **[Learnomy](https://wbcomdesigns.com/downloads/learnomy/)** - Build and sell online courses, auto-grade quizzes, collect payments, and award certificates when learners finish.

== Installation ==

= Manual Installation =

1. Upload the `buddypress-activity-link-preview` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Make sure BuddyPress or BuddyBoss Platform is active
4. Paste a link into an activity post and the preview appears automatically

There is no settings page. The plugin works as soon as it is activated.

= Requirements =

* WordPress 6.5 or higher
* PHP 8.0 or higher
* BuddyPress 6.0 or higher, or BuddyBoss Platform

== Frequently Asked Questions ==

= What are the plugin requirements? =

WordPress 6.5 or higher, PHP 8.0 or higher, and either BuddyPress 6.0+ or BuddyBoss Platform. If neither BuddyPress nor BuddyBoss is active, the plugin deactivates itself and shows a notice explaining why.

= Why doesn't the preview show for some URLs? =

Open Graph tags are preferred, not required. The plugin looks for og:title, og:description and og:image first, and falls back when they are missing: it reads the page's `<title>` element for the title, the `<meta name="description">` tag for the description, and collects every `<img src>` on the page for the image choices.

So a page with no Open Graph tags will usually still preview. A preview fails when the site blocks automated requests outright, when the response is not HTML the plugin can parse, or when the URL resolves to a private or loopback address that the SSRF guard blocks. Reddit links are deliberately skipped. Failed lookups are remembered for 15 minutes, so if you fix the target page, wait for that window to pass before retesting.

= How do I disable link previews in comments? =

Add this to your theme's functions.php:

`add_filter( 'bp_activity_link_preview_enable_comments', '__return_false' );`

This filter only controls previews on activity comments and replies. There is no filter to switch off previews on main activity posts, and no per-user setting - previews on posts are always on while the plugin is active.

= Is the plugin secure? =

Yes. The plugin includes:
* SSRF protection (blocks internal and private IPs, and re-validates short-URL redirect targets)
* CSRF protection via nonce verification
* Logged-in-only URL parsing
* Sanitization of scraped titles and descriptions on save, and escaping on output

= Does it work with BuddyBoss? =

It runs on BuddyBoss Platform, but it deliberately gets out of the way of BuddyBoss's own link preview. If BuddyBoss Platform is active with its native link preview enabled, this plugin stands down and lets BuddyBoss render the card, so you never see two previews on the same post. If you turn the BuddyBoss link preview off, this plugin takes over.

= Does it work with Youzify? =

Same idea. If Youzify is active and its wall URL preview is enabled, this plugin leaves the content alone so Youzify's preview is the only one shown. Turn the Youzify wall URL preview off and this plugin handles the card instead.

= What happens when someone shares a link to a profile or a group on my own site? =

The plugin recognises it as an internal URL and builds the card straight from your database, with no external HTTP request. A member link shows the display name, the XProfile About text and the member's avatar. A group link shows the group name, description and avatar.

= Can I customize it? =

Yes. The plugin has no settings screen, but it exposes filters for the parts site owners usually want to change: `bp_activity_link_preview_load_assets` (which pages load the assets), `bp_activity_parse_url_preview` and `bp_activity_link_parse_url` (the preview data itself), `bp_activity_link_parse_url_shorten_url_provider` (which short-URL hosts are resolved), `bp_oembed_discover_support` (oEmbed discovery), and `bp_activity_link_preview_enable_comments` (previews in comments).

= Will this work with my theme? =

It has been tested with popular BuddyPress and BuddyBoss themes including BuddyX, Reign and Youzify. It hooks the standard BuddyPress activity filters, so it should work with any properly coded BuddyPress-compatible theme.

= Where can I get support? =

* [Documentation](https://docs.wbcomdesigns.com/) - Free guides for every Wbcom plugin
* [Support](https://wbcomdesigns.com/support/) - Get help from our team
* [Contact us](https://wbcomdesigns.com/contact/) - Report bugs and request features

== Screenshots ==

1. Link preview in an activity post - title, description and image pulled from the shared link.

== Changelog ==

= 1.7.6 - August 2026 =

Fixes Twitter/X and Facebook previews that never appeared outside the activity directory, plus a round of composer layout and accessibility corrections.

* Fix      - Twitter/X and Facebook embeds stayed blank on activity permalinks and on themes that do not load the stream over AJAX. They were only ever initialised after an activity AJAX call, so a normal page view left an empty box.
* Fix      - Tweet embeds now follow the site's dark mode instead of always rendering the light Twitter card.
* Fix      - The preview image's previous and next buttons now sit directly under the thumbnail. On wide screens they were centred across the whole card, far from the image they change.
* Fix      - The preview close button now sits top right at every screen width. It previously appeared top left on desktop and top right on mobile.
* Fix      - The image remove button is legible over light images; it was white on a transparent background.
* Fix      - Removed the empty space that every saved preview carried below its description in the activity stream.
* Improve  - Preview close and image navigation controls now meet the 40px minimum touch target (32px for the control that overlays the thumbnail), up from 20x24 and 34x31.
* Dev      - Cleared all 11 PHPStan level 5 findings, including two undefined-method calls on DOM nodes returned by XPath.
* Dev      - Removed dead CSS rules and unused JavaScript variables left behind by earlier renames.

= 1.7.5 - July 2026 =

Corrective release. There are no functional changes since 1.7.4.

* Fix      - Restored the correct plugin files. The 1.7.4 package published on WordPress.org contained the files of a different plugin.

= 1.7.4 - July 2026 =

* New      - Added German, Spanish, French, Italian and Portuguese (Brazil) translations.
* Improve  - Plugin assets and the Twitter/Facebook SDKs now load only in BuddyPress activity contexts (the activity directory, member activity and group screens) instead of every page. Use the bp_activity_link_preview_load_assets filter to load them on custom pages that embed an activity stream.
* Improve  - Pages with nothing to preview are remembered for 15 minutes so they are not re-fetched on every view, and comment rendering never fetches remote URLs, so slow pages no longer delay page loads. A momentary timeout or provider hiccup is no longer remembered, so a good link (a YouTube video, for example) is retried on the next view rather than showing no preview for the whole window. The window is filterable via bp_activity_link_preview_negative_cache_ttl.
* Fix      - The composer preview controls (Cancel Preview, previous/next image and the image counter) were built in JavaScript with no translatable source, so they always rendered in English. They are now translatable.
* Fix      - The plugin never registered its text domain, so bundled translations could never load.
* Fix      - Invalid or blocked URLs now show an error message in the composer instead of failing silently.
* Fix      - Preview close and image navigation icons render correctly when the admin toolbar is hidden.
* Security - Scraped link titles and descriptions are sanitized before saving and escaped on output.
* Security - Hardened short-URL resolution to resolve redirects through the WordPress HTTP API and re-validate the redirect target against the SSRF private/loopback IP guard.

= 1.7.3 =
* Code Quality: Fixed all WordPress Coding Standards (WPCS) violations
* Code Quality: Applied strict comparisons, Yoda conditions, and proper inline comment punctuation
* Code Quality: Added ABSPATH direct access protection
* Code Quality: Added missing PHPDoc parameter documentation for all functions
* Code Quality: Fixed all Plugin Check errors (0 errors)

= 1.7.2 =
* Fixed: Twitter/X and Facebook link previews now work in activity comments
* Fixed: @mentions no longer generate unwanted link previews
* Fixed: Hash symbol (#) no longer added to browser URL when closing previews
* Fixed: "Image X of undefined" no longer shows when images can't be determined
* Added: Helper function to detect social media URLs for native embed handling
* Added: Same-site URL filtering to prevent internal profile links from generating previews
* Improved: Better null checking for image navigation in JavaScript

= 1.7.1 =
* Fixed: Plugin now auto-deactivates when BuddyPress or BuddyBoss Platform is not active
* Fixed: Added proper dependency check on admin_init hook
* Improved: Better error handling for missing dependencies

= 1.7.0 =
* Fixed: Scripts now load in footer for better performance
* Fixed: Proper input sanitization with wp_unslash() for POST data
* Fixed: Use wp_parse_url() instead of parse_url() for better compatibility
* Fixed: Added translators comments for internationalization
* Fixed: Plugin Check compatibility improvements
* Fixed: Nonce verification now mandatory for security (CSRF protection)
* Fixed: BuddyPress/BuddyBoss compatibility - function_exists checks added
* Fixed: PHP 8.2+ compatibility - removed deprecated HTML-ENTITIES encoding
* Fixed: BuddyPress class name detection improved
* Fixed: Comment filter registration timing for proper enable/disable support
* Added: Plugin version constant for proper asset cache busting
* Updated: Tested up to WordPress 7.0
* Updated: Requires PHP 7.4 minimum

= 1.6.1 =
* Security: Patched SSRF (Server Side Request Forgery) vulnerability in the URL parser.

= 1.6.0 =
* Added: Filter and event hooks to extend the activity preview functionality.
* Fixed: Twitter card preview duplication issue in multiple activities.
* Fixed: Twitter preview incorrectly appended to the second activity.
* Fixed: Activity content not displaying when preview is enabled.
* Fixed: Iframe not rendering correctly in activity previews.
* Fixed: Preview not visible when sharing X (formerly Twitter) links.
* Fixed: Activity link preview index logic for accurate rendering.
* Improved: String labels and content clarity across the plugin.
* Security: Patched SSRF (Server Side Request Forgery) vulnerability in the URL parser.
* Security: Fixed XSS issues in link preview rendering to improve safety.

= 1.4.4 =
* Fix: Hide raw Facebook and Twitter URLs in BuddyPress activity content.
* Fix: Addressed multiple issues with Facebook embed functionality.
* Fix: Resolved issues with console errors during content injection.
* Enhancement: Improved code quality for better readability and maintainability.
* Update: Added support for Twitter, YouTube, and LinkedIn link previews.
* Update: Enhanced compatibility with Reddit link previews.
* Feature: Included activity link preview data in the REST API activity endpoint.
* Fix: Resolved a YouTube link preview issue.
* Fix: Addressed issues where comments and replies could not be added to activities.

= 1.4.3 =
* Fix: Issue with Reddit
* Fix: Issue with YouTube link preview

= 1.4.2 =
* Fixed: Twitter/Instagram/Facebook preview issue

= 1.4.0 =
* Fixed: Added spacing between link preview container and post button
* Fixed: Unable to comment and reply issue

= 1.3.0 =
* Fixed: Added activity link data in REST API activity endpoint
* Fixed: PHPCS Fixes

= 1.2.0 =
* Fixed: Plugin activated when BuddyPress is not activated
* Fixed: Update spacing between text and buttons
* Fixed: YouTube link issue

= 1.1.0 =
* Fixed: Legacy Support
* Fixed: Preview generation on pasting URLs
* Fixed: Error message when meta values are not readable

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.7.5 =
Corrective release. The 1.7.4 package published on WordPress.org contained the files of a different plugin. Update immediately if you installed or updated to 1.7.4 from WordPress.org. There are no functional changes since 1.7.4.

= 1.7.4 =
Performance and security update. Assets load only in activity contexts, failed lookups are cached, and short-URL resolution is hardened against SSRF. Note the minimum supported WordPress version is now stated correctly as 6.5.

= 1.7.2 =
Bug fix release. Fixes Twitter/Facebook previews in comments, @mention link preview issue, hash symbol in URL, and undefined image count. Recommended for all users.

= 1.7.1 =
Dependency handling improvement. Plugin now properly deactivates when BuddyPress or BuddyBoss is not active.

= 1.7.0 =
Security and compatibility update. Includes CSRF protection improvements, PHP 8.2+ compatibility, and BuddyBoss Platform support. Recommended for all users.

= 1.6.1 =
Security update. Patches SSRF vulnerability. Update immediately.

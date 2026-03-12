=== Activity Link Preview For BuddyPress ===
Contributors: wbcomdesigns, vapvarun
Donate link: https://wbcomdesigns.com/donate/
Tags: buddypress, activity, link preview, social, open graph
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.7.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

BuddyPress activity link preview displays image, title and description from websites when links are shared in activity posts.

== Description ==

**Activity Link Preview For BuddyPress** automatically generates beautiful link previews when users share URLs in BuddyPress activity posts and comments. The plugin fetches Open Graph data (image, title, description) from shared links and displays them in an attractive card format.

= Key Features =

* **Automatic Link Detection** - Detects URLs as users type in the activity form
* **Rich Previews** - Displays title, description, and featured image from shared links
* **Comment Support** - Link previews work in activity comments and replies
* **Social Media Embeds** - Special handling for Twitter/X, Facebook, YouTube, and more
* **Short URL Support** - Resolves shortened URLs (bit.ly, tinyurl, etc.)
* **Caching** - Previews are cached for better performance
* **REST API Support** - Link preview data available via BuddyPress REST API
* **Developer Friendly** - Filters to customize or disable functionality

= Supported Platforms =

* Twitter/X - Native tweet embeds
* Facebook - Native post embeds
* YouTube - Video embeds via oEmbed
* LinkedIn, Instagram, Reddit - Link previews
* Any website with Open Graph meta tags

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* BuddyPress 6.0+ or BuddyBoss Platform

== Installation ==

1. Upload the `buddypress-activity-link-preview` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure BuddyPress or BuddyBoss Platform is active
4. Start sharing links in BuddyPress activities!

== Frequently Asked Questions ==

= What are the plugin requirements? =

The plugin requires:
* WordPress 5.0+
* PHP 7.4+
* BuddyPress 6.0+ OR BuddyBoss Platform

= How do I disable link previews in comments? =

Add this code to your theme's functions.php:
`add_filter( 'bp_activity_link_preview_enable_comments', '__return_false' );`

= Can I disable previews for specific users? =

Yes, use the filter conditionally:
`add_filter( 'bp_activity_link_preview_enable_comments', function( $enabled ) {
    if ( current_user_can( 'administrator' ) ) {
        return false;
    }
    return $enabled;
});`

= Why doesn't the preview show for some URLs? =

Some websites block automated requests or don't have Open Graph meta tags. The plugin requires websites to have proper og:title, og:description, or og:image meta tags.

= Is the plugin secure? =

Yes. The plugin includes:
* SSRF protection (blocks internal/private IPs)
* CSRF protection via nonce verification
* Proper input sanitization and output escaping
* User authentication requirements

= Does it work with BuddyBoss? =

Yes, the plugin is fully compatible with both BuddyPress and BuddyBoss Platform.

== Screenshots ==

1. Link preview in activity post
2. Link preview in activity comment
3. Twitter/X embed preview
4. Multiple image selection

== Changelog ==

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
* Updated: Tested up to WordPress 6.9
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

= 1.7.2 =
Bug fix release. Fixes Twitter/Facebook previews in comments, @mention link preview issue, hash symbol in URL, and undefined image count. Recommended for all users.

= 1.7.1 =
Dependency handling improvement. Plugin now properly deactivates when BuddyPress or BuddyBoss is not active.

= 1.7.0 =
Security and compatibility update. Includes CSRF protection improvements, PHP 8.2+ compatibility, and BuddyBoss Platform support. Recommended for all users.

= 1.6.1 =
Security update. Patches SSRF vulnerability. Update immediately.

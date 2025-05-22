=== Wbcom Designs - Activity Link Preview For BuddyPress ===
Contributors: wbcomdesigns, vapvarun
Donate link: https://wbcomdesigns.com/donate/
Tags: buddypress, Activity, Link Preview
Requires at least: 3.0.1
Tested up to: 6.8.0
Stable tag: 1.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Paste the link on your BuddyPress activity in the new area and check the link preview with og:image.
The link will be converted into a beautiful preview with an image, title, and description.

== Installation ==

1. Upload the entire `buddypress-activity-link-preview` folder to the /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= What are the plugin requirements? =

The plugin requires plugins :
1. [BuddyPress](https://buddypress.org/download/)

= For support, You can contact us at our website. =

You can contact at our website [wbcomdesigns.com](https://wbcomdesigns.com/contact) for any query related to plugin and BuddyPress.

== Screenshots ==

The screenshots are present in the root of the plugin folder.
1. screenshot-1

== Changelog ==
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
* Fix: Hide raw Facebook and Twitter URLs in BuddyPress activity content. Only embed previews are displayed.
* Fix: Addressed multiple issues with Facebook embed functionality, ensuring better DOM handling and error prevention.
* Fix: Resolved issues with console errors during content injection.
* Enhancement: Improved code quality for better readability and maintainability.
* Update: Removed obsolete 'hard-g' folder for cleanup.
* Update: Added support for Twitter, YouTube, and LinkedIn link previews.
* Update: Enhanced compatibility with Reddit link previews.
* Update: Improved handling of Instagram, Facebook, and Twitter previews for a consistent experience.
* Update: Added spacing between link preview container and the post button for improved UI.
* Feature: Included activity link preview data in the REST API activity endpoint.
* Fix: Resolved a YouTube link preview issue. (#27)
* Fix: Addressed issues where comments and replies could not be added to activities. (#18)
* Misc: General code updates and PHPCS fixes for better standards compliance.

= 1.4.3 =
* Fix: Issue with Reddit
* Fix: (#27) Issue with YouTube link preview

= 1.4.2 =
* Fixed: (#26)Fixed twitter/ig/facebook preview issue

= 1.4.0 =
* Fixed: Added some spacing between link preview container and post button
* Fixed: #18 - unable to comment and reply

= 1.3.0 =
* Fixed: Added activity link data in rest api activity endpoint
* Fixed: PHPCS Fixes

= 1.2.0 =
* Fixed: (#6) - Fixed plugin-activated-when-buddypress-is-not-activated
* Fixed: (#5) - Update spacing between text and buttons
* Fixed: (#3) - Youtube link issue

= 1.1.0 =
* Fixed: Legacy Support
* Fixed: preview generation on pasting urls
* Fixed: Error message when meta values are not readable.

= 1.0.0 =
* first version.

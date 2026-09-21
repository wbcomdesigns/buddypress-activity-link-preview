# Requirements

The plugin declares these minimums in its plugin header and readme.

| Requirement | Minimum |
|---|---|
| WordPress | 6.5 or higher |
| PHP | 8.0 or higher |
| Community platform | BuddyPress 6.0 or higher, or BuddyBoss Platform |

## Community platform dependency

Either BuddyPress or BuddyBoss Platform must be installed and active. The plugin checks for this on every request:

- On the front end it only registers its hooks when the dependency is present.
- On `admin_init` it deactivates itself if neither BuddyPress nor BuddyBoss Platform is active, and shows an admin notice: "Activity Link Preview For BuddyPress requires BuddyPress or BuddyBoss Platform to be installed and active."

The dependency is detected through the `BuddyPress` class, the `BP_PLATFORM_VERSION` constant, or the `BuddyBoss_Platform` class.

## Tested environment

- Tested up to WordPress 7.0.
- Tested with the BuddyX and Reign themes, and with Youzify.

## Translations

The plugin ships a POT template and translations for German, Spanish, French, Italian and Portuguese (Brazil). Right-to-left (RTL) stylesheets are included.

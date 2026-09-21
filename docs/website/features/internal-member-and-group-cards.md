# Internal Member and Group Cards

When a member shares a link that points at a profile or a group on your own site, the plugin recognises it as an internal URL and builds the card straight from your database. There is no external HTTP request and no self-request, which keeps busy sites fast.

## Member profile links

For a URL such as `/members/username/` or `/user/username/`, the card is built from:

- **Title** the member display name.
- **Description** the member's XProfile "About" field. If that is empty, the plugin tries an XProfile "Description" field, then the WordPress user `description` meta. If none of those has content, the card shows "View {name}'s profile".
- **Image** the member's BuddyPress avatar (full size), falling back to the WordPress avatar.

## Group links

For a URL such as `/groups/group-slug/`, the card is built from:

- **Title** the group name.
- **Description** the group description, trimmed to about 30 words. If the group has no description, the card shows "View the {group} group".
- **Image** the group avatar.

## How a URL is recognised as internal

A URL is treated as internal when its host and scheme match your site's home URL. Subdirectory installs are handled by stripping the site path before matching the `/members/`, `/user/` or `/groups/` prefix.

Internal cards are the only preview type still produced during render without a live fetch, because they read from the local database rather than the network.

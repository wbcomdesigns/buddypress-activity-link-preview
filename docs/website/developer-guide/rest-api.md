# REST API

Preview data is added to the BuddyPress activity REST response, so mobile apps and headless front-ends can render the same cards.

## Endpoint

```
GET /wp-json/buddypress/v1/activity
GET /wp-json/buddypress/v1/activity/<id>
```

The plugin does not register an endpoint of its own. It filters the existing BuddyPress activity response through `bp_rest_activity_prepare_value`.

## Response keys

| Key | Present on | Holds |
|---|---|---|
| `bp_activity_link` | Every activity | Preview data for a top-level activity |
| `bp_activity_comment_link` | Activities of type `activity_comment` | Preview data for a comment |

Both are the stored preview array, or an empty value when the activity has no preview.

| Field | Description |
|---|---|
| `url` | The URL that was previewed |
| `title` | Page title, from Open Graph or `<title>` |
| `description` | Page description, from Open Graph or the meta description |
| `image_url` | The image the member selected in the composer |
| `wp_embed` | Present and true for oEmbed content such as YouTube |
| `embed_html` | The embed markup, generated at save time |

## Example

```json
{
  "id": 802,
  "user_id": 1,
  "component": "activity",
  "type": "activity_update",
  "title": "admin posted an update",
  "date": "2023-04-26T07:44:58",
  "content": { "rendered": "" },
  "bp_activity_link": {
    "url": "https://buddypress.org/",
    "title": "BuddyPress.org",
    "description": "Fun & flexible software for online communities, teams, and groups",
    "image_url": "https://buddypress.org/wp-content/themes/buddypress-org/images/screenshots.png"
  }
}
```

For an oEmbed video the shape is different - `wp_embed` and `embed_html` carry the player, and there is no scraped title or image:

```json
{
  "bp_activity_link": {
    "url": "https://www.youtube.com/watch?v=aSR1tndcaLE",
    "wp_embed": true,
    "embed_html": "<iframe title=\"...\" width=\"720\" height=\"405\" src=\"https://www.youtube.com/embed/aSR1tndcaLE?feature=oembed\" ...></iframe>"
  }
}
```

For a Twitter/X or Facebook link, only `url` is stored. There is no title, description or image to send, because the card is rendered by the network's own widget script from the URL alone. A REST consumer should treat a preview record that has a `url` and nothing else as "render this yourself".

## Notes for API consumers

- `embed_html` is stored markup, not generated per request, so reading the API never triggers an outbound fetch.
- Preview data is stored in activity meta, so it is available without re-parsing the activity content.
- BuddyBoss Platform exposes the same activity endpoint and the filter applies there too.

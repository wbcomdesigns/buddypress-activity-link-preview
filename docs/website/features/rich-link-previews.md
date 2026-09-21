# Rich Link Previews

The core feature: a plain URL becomes a card with a title, description and image.

## In the composer, as the member types

When a member types or pastes a URL into the activity composer, the plugin's JavaScript detects it and sends the URL to a server-side parse endpoint. The endpoint returns a title, description and any images it found, and the composer shows a preview card before the member posts.

- The preview card clears itself when the URL is removed from the composer.
- Blocked or invalid URLs show an inline error in the composer instead of failing silently.

## Choosing between multiple images

When the linked page offers several images, the composer card shows previous and next buttons and an "Image X of Y" counter so the member can pick which image the card uses. The chosen image is the one saved with the post.

## How the preview data is built

The parse endpoint tries these sources, in order:

1. **WordPress oEmbed** for known providers (YouTube, Vimeo and any other provider WordPress supports). When the URL is a recognised oEmbed provider, the returned embed code is used.
2. **Internal member and group pages** on your own site, read directly from the database with no HTTP request. See the Internal Member and Group Cards page.
3. **Open Graph and page scraping** for everything else. The plugin fetches the page and reads its meta tags:
   - `og:title` for the title, falling back to the page `<title>` element.
   - `og:description` or `<meta name="description">` for the description.
   - `og:image`, plus every `<img src>` on the page, for the image choices.

Open Graph tags are preferred but not required. A page with no Open Graph tags will usually still preview from its `<title>`, meta description and images.

## Saved previews render on the server

When the activity is saved, the preview data (URL, title, description and image) is stored in activity meta. On display, the card is appended to the activity content by a server-side filter, so a saved preview shows even when the visitor has JavaScript disabled. Only the compose-time live detection needs JavaScript.

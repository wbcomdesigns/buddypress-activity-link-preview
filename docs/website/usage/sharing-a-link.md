# Sharing a Link

There is nothing to configure. Once the plugin is active, previews are part of posting to the activity stream.

## Posting an activity with a link

1. Open any BuddyPress activity form (the activity directory, your profile activity, or a group).
2. Type or paste a URL into the composer.
3. The plugin detects the URL and shows a preview card with a title, description and image.
4. If the page offers several images, use the previous and next buttons to choose one. The counter shows "Image X of Y".
5. If you decide not to include the preview, use the cancel control in the card.
6. Post the activity. The card is saved with the post and shown to everyone who sees it.

## Commenting with a link

Paste a URL into an activity comment or reply the same way. A card is built for the comment. If a comment already contains a URL but no card was built at compose time, the plugin builds one from cached data when the comment is displayed.

## Removing a preview

Delete the URL from the composer and the preview card clears itself.

## What renders after posting

The saved card is rendered on the server, so it displays even for visitors with JavaScript disabled. Twitter/X, Facebook and oEmbed video links render as native embeds or inline players rather than a plain title/image card.

# Documentation - Activity Link Preview For BuddyPress

Source of truth for this plugin's customer documentation. Markdown only, GitHub only. Nothing here is published or synced from this repo.

## Contents

### Getting started
- [Introduction](getting-started/introduction.md) - what the plugin does, requirements, why there is no settings page
- [How it works](getting-started/how-it-works.md) - the member flow, what happens server-side, why a preview sometimes does not appear
- [Supported platforms and limits](getting-started/supported-platforms.md) - per-link-type behaviour, third-party requests, known limits

### Developer guide
- [Filters](developer-guide/filters.md) - all seven filters with signatures and examples. **This is the configuration reference**, because the plugin has no admin UI.
- [REST API](developer-guide/rest-api.md) - the two activity response keys and their shapes

## Migration note

Migrated on 2026-08-14 from `docs.wbcomdesigns.com/docs/buddypress-activity-link-preview/`, which had four pages last updated 2025-04-24 - roughly sixteen months stale, spanning releases 1.7.0 through 1.7.6.

Every claim was re-verified against the 1.7.6 source before being carried over. Three corrections were needed:

1. **LinkedIn support removed.** Both the Introduction and Features pages claimed "specialized support for Facebook, Twitter, YouTube, and LinkedIn". The code has never had it - the social embed list is `x.com`, `twitter.com` and `facebook.com` only. The plugin's own `readme.txt` dropped the claim in 1.7.4; the docs site never did.
2. **Filters documented for the first time.** No live page mentioned a single filter, even though filters are the only way to configure the plugin.
3. **Second REST key added.** The live REST page documented `bp_activity_link` but not `bp_activity_comment_link`, which is present on activity comments.

The `bp_activity_link` key itself was verified correct and carried over unchanged.

## When updating

Keep these in step with the code. If a release changes a filter signature, a stored meta key or a REST response shape, update the matching page in the same commit - that is the drift this migration was cleaning up.

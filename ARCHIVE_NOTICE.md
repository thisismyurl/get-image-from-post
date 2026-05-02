# Archived — 2026-05-02

This repository is archived. Read-only.

## Why

The plugin uses `create_function()`, which was deprecated in PHP 7.2 and removed in PHP 8.0. On any modern WordPress stack the plugin fatals on activation, so it can't responsibly stay in the active catalogue.

## What to do instead

Modern WordPress already covers what this plugin set out to do.

- For a featured-image fallback that grabs the first attached image, use `get_attached_media( 'image', $post_id )` and pull `[0]->guid` (or render via `wp_get_attachment_image()`).
- For automatic featured-image extraction from post content, the canonical pattern is `Auto Set Featured Image` or the `post_thumbnail_html` filter.
- For more involved media-library work, see [thisismyurl-image-support](https://github.com/thisismyurl/thisismyurl-image-support).

— Christopher Ross / [thisismyurl.com](https://thisismyurl.com/)

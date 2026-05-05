# Get Image From Post

> **⚠️ This plugin is archived and no longer maintained.**  
> See [ARCHIVE_NOTICE.md](ARCHIVE_NOTICE.md) for context and modern alternatives.

This plugin returned the first inline image from a WordPress post's content.

**Do not install on PHP 8.0+ — the plugin uses `create_function()` which was removed in PHP 8.0 and will fatal-error on activation.**

## Modern Alternatives

- `get_attached_media( 'image', $post_id )` — returns attached images as objects.
- `wp_get_attachment_image()` — renders with proper `srcset`/`sizes`.
- [thisismyurl-image-support](https://github.com/thisismyurl/thisismyurl-image-support) — full media library management.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

---

**Author:** [Christopher Ross](https://thisismyurl.com)

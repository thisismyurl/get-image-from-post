# Get Image from Post

Fetches the most representative image for a WordPress post — useful for excerpts,
archive listings, and custom templates. It resolves in a sensible cascade:

1. the post's **featured image**, then
2. the **first attached image** in the media library, then
3. the **first inline image** in the post content.

One resolver underneath every entry point — no duplicated extraction logic.

## Usage

**Template tag** (in the Loop):

```php
echo horshipsrectors_get_image_from_post();              // returns <img …> markup
echo horshipsrectors_get_image_from_post( 'link=true' ); // linked to the permalink
horshipsrectors_get_image_from_post( 'show=true' );      // echoes instead of returns
```

**Shortcode:**

```text
[get_image_from_post]
[get_image_from_post id="123" link="true" width="600"]
```

**WordPress 7 ability** (REST / AI-invokable, read-only):

```text
get-image-from-post/get-image
```

Returns a structured `{ url, id, width, height, source }` descriptor for a
known, readable post. No-ops cleanly on WordPress versions without the
Abilities API.

## Requirements

- WordPress 6.0+
- PHP 7.4+ (runs clean on PHP 8.x)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

---

**Author:** [Christopher Ross](https://thisismyurl.com)

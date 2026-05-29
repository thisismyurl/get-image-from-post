=== Get Image from Post ===
Contributors: thisismyurl
Plugin URI: https://thisismyurl.com/
Tags: images, featured-image, excerpt, thumbnail
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 7.0
Stable tag: 2026.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Fetches the most representative image for a post — featured, then first attached, then first inline — via a template tag, a shortcode, or a WordPress 7 ability.

== Description ==

Get Image from Post returns a single, representative image for a post. It resolves in a sensible cascade so you always get the best available image without writing the fallback logic yourself:

1. The post's featured image.
2. The first attached image in the media library.
3. The first inline image in the post content.

One resolver sits underneath every entry point, so the template tag, the shortcode, and the WordPress 7 ability all agree on which image represents a post.

= Entry points =

* **Template tag** — `horshipsrectors_get_image_from_post()`, the original function, preserved with its option string for backward compatibility.
* **Shortcode** — `[get_image_from_post]` for use in content and widgets.
* **WordPress 7 ability** — `get-image-from-post/get-image`, a read-only, REST- and AI-invokable ability that returns a structured `url` / `id` / `width` / `height` / `source` result. It no-ops on WordPress versions without the Abilities API.

== Installation ==

1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate it through the Plugins screen in WordPress.
3. Call the template tag in your theme, drop the shortcode into content, or invoke the ability.

== Frequently Asked Questions ==

= How do I display an image in my theme? =

Inside the Loop:

 echo horshipsrectors_get_image_from_post();

This returns an `<img>` markup string built from the resolved image URL.

= How do I echo instead of return? =

Pass `show=true`:

 horshipsrectors_get_image_from_post( 'show=true' );

By default the function returns the markup so you can assign it to a variable.

= How do I link the image to the post? =

 horshipsrectors_get_image_from_post( 'link=true' );

= How do I set or strip dimensions? =

Set a fixed width or height:

 horshipsrectors_get_image_from_post( 'width=600&height=400' );

Or omit the width/height attributes entirely:

 horshipsrectors_get_image_from_post( 'strip=true' );

= What happened to the image=N selector? =

The original plugin scraped inline `<img>` tags and let you pick the Nth one with `image=2`. The plugin now resolves a single representative image (featured, then first attached, then first inline), so the `image` option is retained for call-signature compatibility but no longer selects an index. Existing calls that pass it keep working and return the representative image.

= Can I target a specific post with the shortcode? =

Yes:

 [get_image_from_post id="123"]

Without an `id`, the shortcode resolves the current post in the Loop.

= How is the image's alt text chosen? =

By default the plugin uses the best available alternative text: the attachment's editor-authored alt text for a featured or attached image, the inline image's own alt for a content-sourced image, and the post title only as a last resort.

To set it yourself, pass `alt`:

 [get_image_from_post alt="A red barn at sunset"]

For a decorative image that screen readers should skip, pass an empty alt:

 [get_image_from_post alt=""]

The template tag accepts the same option: `horshipsrectors_get_image_from_post( 'alt=' )`. One exception: a decorative empty alt on a linked image (`link=true`) falls back to the post title, because a link must have an accessible name.

== Changelog ==

= 2026.0.2 =

* Bumped `Tested up to` to 7.0 in the plugin header and readme so the compatibility metadata agrees with the WordPress 7 ability the plugin ships.

= 2026.0.1 =

* Accessibility (WCAG 1.1.1): the `<img>` alt text is no longer hardcoded to the post title. It now resolves through a chain — an explicit caller-supplied alt (including an empty `alt=""` for decorative use), then the editor-authored attachment alt text, then the inline image's own alt for content-sourced images, then the post title as a last resort.
* Added an `alt` option to the template tag, an `alt` attribute to the `[get_image_from_post]` shortcode, and inline-alt capture to the content-source resolver.
* Accessibility (WCAG 4.1.2): when an image resolves to a decorative empty alt but is also linked (`link=true`), the alt falls back to the post title so the link is not left without an accessible name.

= 2026.0.0 =

* Revived from archive. Fixed the PHP 8 fatal: the legacy `horshipsrectors_strip_tags_attributes()` helper used `create_function()`, removed in PHP 8.0. That scraper is gone; image resolution now runs through a single modern resolver, `horshipsrectors_resolve_post_image()`, built on DOMDocument and the media APIs.
* The template tag `horshipsrectors_get_image_from_post()` keeps its original signature and `<img>`-string return shape; its internals now sit on the modern resolver.
* Added a `[get_image_from_post]` shortcode mapping the same options.
* Added WordPress 7 Abilities API support: the read-only ability `get-image-from-post/get-image` resolves a post's image via the featured → attached → inline-content cascade and returns a structured `url` / `id` / `width` / `height` / `source` result.
* All output is escaped (`esc_url`, `esc_attr`). Runs clean on PHP 8.x with no notices or warnings.
* Updated plugin header: Requires at least 6.0, Requires PHP 7.4, Tested up to 6.9, Text Domain `get-image-from-post`.

= 2.0.0 =

* Renamed function to be more compatible.
* Tested and optimized in WordPress 3.2.
* Added a test to determine whether images existed.

= 1.1.0 =

* Minor fixes for the WordPress admin.

= 1.0.0 =

* Official release.
* Added the strip-attribute option.

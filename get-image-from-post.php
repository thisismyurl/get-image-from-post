<?php
/**
 * Plugin Name:       Get Image from Post
 * Plugin URI:        https://thisismyurl.com/
 * Description:        Fetches the most representative image for a post — featured image first, then the first attached image, then the first inline image in the content — for use in excerpts, listings, and templates. Exposes a template tag, a shortcode, and a WordPress 7 ability.
 * Version:           2026.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Tested up to:      6.9
 * Author:            Christopher Ross
 * Author URI:        https://thisismyurl.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       get-image-from-post
 *
 * @package Get_Image_from_Post
 */

defined( 'ABSPATH' ) || exit;

// Shared resolver + WP 7 Abilities API registration. The resolver lives there so
// every entry point in this plugin reads images from one source of truth.
require_once __DIR__ . '/abilities.php';

/**
 * Returns the representative image for a post as an HTML <img> string.
 *
 * Backward-compatible template tag preserved from the original 2017 plugin. It
 * accepts the same `key=value&key=value` option string and returns (or echoes)
 * an `<img>` markup string, so existing theme code keeps working. The internals
 * now sit on top of {@see horshipsrectors_resolve_post_image()} — the same
 * featured → attached → inline-content cascade the WP 7 ability uses — instead
 * of the removed `create_function()` scraper that fataled on PHP 8.
 *
 * Recognised options (unchanged names):
 *  - `show`   When truthy, echoes the markup and returns ''. Default: returns it.
 *  - `link`   When truthy, wraps the image in an `<a>` to the post permalink.
 *  - `width`  Fixed width attribute in pixels. Ignored when `strip` is truthy.
 *  - `height` Fixed height attribute in pixels. Ignored when `strip` is truthy.
 *  - `strip`  When truthy, omits the width/height attributes from the markup.
 *  - `image`  Legacy image-index selector. Retained for signature compatibility;
 *             the resolver now returns a single representative image, so this
 *             no longer selects the Nth inline image. See readme.txt.
 *
 * @since 1.0.0
 * @since 2026.0.0 Reimplemented on the modern resolver; PHP 8 safe.
 *
 * @param string $options Optional `key=value&key=value` option string.
 * @return string The `<img>` markup, or '' when echoed or when no image resolves.
 */
function horshipsrectors_get_image_from_post( $options = '' ) {
	$settings = horshipsrectors_parse_legacy_options( (string) $options );

	$post_id = (int) get_queried_object_id();
	if ( 0 === $post_id ) {
		$current = get_post();
		$post_id = $current instanceof WP_Post ? (int) $current->ID : 0;
	}

	if ( 0 === $post_id ) {
		return '';
	}

	$image = horshipsrectors_resolve_post_image( $post_id );
	if ( is_wp_error( $image ) ) {
		return '';
	}

	$markup = horshipsrectors_build_image_markup( $image, $settings, $post_id );

	if ( $settings['show'] ) {
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts in horshipsrectors_build_image_markup().
		return '';
	}

	return $markup;
}

/**
 * Parses the legacy `key=value&key=value` option string into a settings array.
 *
 * Mirrors the original plugin's option parsing so existing callers behave the
 * same, but reads values safely rather than indexing into possibly-missing
 * array offsets (which raised notices on PHP 8).
 *
 * @since 2026.0.0
 *
 * @param string $options Raw option string.
 * @return array{show:bool,link:bool,width:int,height:int,strip:bool} Parsed settings.
 */
function horshipsrectors_parse_legacy_options( string $options ): array {
	$settings = array(
		'show'   => false,
		'link'   => false,
		'width'  => 0,
		'height' => 0,
		'strip'  => false,
	);

	if ( '' === $options ) {
		return $settings;
	}

	foreach ( explode( '&', $options ) as $pair ) {
		if ( false === strpos( $pair, '=' ) ) {
			continue;
		}

		list( $key, $value ) = array_map( 'trim', explode( '=', $pair, 2 ) );

		switch ( $key ) {
			case 'show':
			case 'link':
			case 'strip':
				$settings[ $key ] = horshipsrectors_is_truthy( $value );
				break;
			case 'width':
			case 'height':
				$settings[ $key ] = absint( $value );
				break;
		}
	}

	return $settings;
}

/**
 * Interprets a legacy option value as a boolean.
 *
 * The original plugin treated any non-empty value as true. This keeps that
 * behaviour while explicitly rejecting the common falsey strings, so
 * `show=false` returns the markup as the readme documented.
 *
 * @since 2026.0.0
 *
 * @param string $value Raw option value.
 * @return bool Whether the value should be treated as true.
 */
function horshipsrectors_is_truthy( string $value ): bool {
	$value = strtolower( trim( $value ) );

	if ( '' === $value || '0' === $value || 'false' === $value || 'no' === $value ) {
		return false;
	}

	return true;
}

/**
 * Builds the `<img>` markup (optionally linked) for a resolved image descriptor.
 *
 * Every dynamic value is escaped at the point it enters the markup: the URL with
 * `esc_url()`, the alt text and dimensions with `esc_attr()`. The result is safe
 * to echo directly.
 *
 * @since 2026.0.0
 *
 * @param array{url:string,id:int,width:int,height:int,source:string} $image    Resolved image descriptor.
 * @param array{show:bool,link:bool,width:int,height:int,strip:bool}   $settings Parsed legacy settings.
 * @param int                                                          $post_id  Post the image belongs to.
 * @return string The `<img>` markup, wrapped in an `<a>` when `link` is set.
 */
function horshipsrectors_build_image_markup( array $image, array $settings, int $post_id ): string {
	$attributes = array(
		'src' => esc_url( $image['url'] ),
		'alt' => esc_attr( get_the_title( $post_id ) ),
	);

	if ( ! $settings['strip'] ) {
		$width  = $settings['width'] > 0 ? $settings['width'] : (int) $image['width'];
		$height = $settings['height'] > 0 ? $settings['height'] : (int) $image['height'];

		if ( $width > 0 ) {
			$attributes['width'] = esc_attr( (string) $width );
		}
		if ( $height > 0 ) {
			$attributes['height'] = esc_attr( (string) $height );
		}
	}

	$parts = array();
	foreach ( $attributes as $name => $value ) {
		$parts[] = sprintf( '%s="%s"', $name, $value );
	}

	$markup = '<img ' . implode( ' ', $parts ) . ' />';

	if ( $settings['link'] ) {
		$permalink = get_permalink( $post_id );
		if ( false !== $permalink ) {
			$markup = sprintf( '<a href="%s">%s</a>', esc_url( $permalink ), $markup );
		}
	}

	return $markup;
}

/**
 * Renders the representative post image via the `[get_image_from_post]` shortcode.
 *
 * The original plugin shipped no shortcode; this is new in the revival and maps
 * the same options as the template tag onto shortcode attributes. `id` targets a
 * specific post; omitted, it resolves the current post in the Loop.
 *
 * @since 2026.0.0
 *
 * @param array|string $atts Shortcode attributes.
 * @return string The `<img>` markup, or '' when no image resolves.
 */
function horshipsrectors_image_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'id'     => 0,
			'link'   => 'false',
			'width'  => 0,
			'height' => 0,
			'strip'  => 'false',
		),
		$atts,
		'get_image_from_post'
	);

	$post_id = absint( $atts['id'] );
	if ( 0 === $post_id ) {
		$post_id = (int) get_queried_object_id();
	}
	if ( 0 === $post_id ) {
		$current = get_post();
		$post_id = $current instanceof WP_Post ? (int) $current->ID : 0;
	}
	if ( 0 === $post_id ) {
		return '';
	}

	$image = horshipsrectors_resolve_post_image( $post_id );
	if ( is_wp_error( $image ) ) {
		return '';
	}

	$settings = array(
		'show'   => false,
		'link'   => horshipsrectors_is_truthy( (string) $atts['link'] ),
		'width'  => absint( $atts['width'] ),
		'height' => absint( $atts['height'] ),
		'strip'  => horshipsrectors_is_truthy( (string) $atts['strip'] ),
	);

	return horshipsrectors_build_image_markup( $image, $settings, $post_id );
}

add_shortcode( 'get_image_from_post', 'horshipsrectors_image_shortcode' );

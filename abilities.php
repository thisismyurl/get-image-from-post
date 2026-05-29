<?php
/**
 * WP 7 Abilities API registration for Get Image from Post.
 *
 * Exposes the post-image resolver as a discoverable, REST/AI-invokable ability.
 *
 * The 2017 plugin shipped its image lookup inside the procedural template tag
 * `horshipsrectors_get_image_from_post()`, which only ever scraped inline
 * `<img>` markup out of a `strtolower()`-corrupted content blob, returned an
 * HTML string rather than a resolvable URL, and relied on `create_function()`
 * (removed in PHP 8). That code cannot satisfy a structured `url`/`id`/`source`
 * contract, so the reusable resolution logic is extracted here into one named
 * function, `horshipsrectors_resolve_post_image()`. The ability wraps that
 * function; nothing reimplements the cascade twice.
 *
 * @package Get_Image_from_Post
 * @since   2026.0.0
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the most representative image for a post.
 *
 * Tries, in order: the featured image, then the first attached image, then the
 * first inline `<img>` found in the rendered post content. Returns a structured
 * array describing the resolved image, or a `WP_Error` when none is found.
 *
 * This is the single source of truth for "which image represents this post."
 * Both the Abilities API callback and any future template tag should call it
 * rather than duplicating the cascade.
 *
 * @since 2026.0.0
 *
 * Content-sourced descriptors carry an `alt` key holding the inline image's own
 * alt text (possibly ''); attachment-sourced descriptors omit it, since their
 * alt is read from attachment meta at markup time.
 *
 * @param int    $post_id Post ID to resolve an image for.
 * @param string $size    Registered image size name. Default 'large'.
 * @return array{url:string,id:int,width:int,height:int,source:string,alt?:string}|WP_Error
 *     Image descriptor on success, or WP_Error 'no_image' when none is found.
 */
function horshipsrectors_resolve_post_image( int $post_id, string $size = 'large' ) {
	// Featured image: the canonical, editor-chosen representative image.
	$featured_id = (int) get_post_thumbnail_id( $post_id );
	if ( $featured_id > 0 ) {
		$image = horshipsrectors_describe_attachment( $featured_id, $size, 'featured' );
		if ( ! is_wp_error( $image ) ) {
			return $image;
		}
	}

	// First attached image in the media library.
	$attached = get_attached_media( 'image', $post_id );
	if ( ! empty( $attached ) ) {
		$first = array_shift( $attached );
		$image = horshipsrectors_describe_attachment( (int) $first->ID, $size, 'attached' );
		if ( ! is_wp_error( $image ) ) {
			return $image;
		}
	}

	// Last resort: the first inline <img> in the rendered content. The scraper
	// returns the image's own alt text alongside its URL so callers building
	// markup can preserve the editor-authored alternative text rather than
	// substituting an unrelated string (WCAG 1.1.1).
	$inline = horshipsrectors_first_inline_image( $post_id );
	if ( '' !== $inline['url'] ) {
		return array(
			'url'    => $inline['url'],
			'id'     => 0,
			'width'  => 0,
			'height' => 0,
			'source' => 'content',
			'alt'    => $inline['alt'],
		);
	}

	return new WP_Error(
		'no_image',
		__( 'No image could be resolved for this post.', 'get-image-from-post' ),
		array( 'status' => 404 )
	);
}

/**
 * Builds an image descriptor for a known attachment ID at a given size.
 *
 * @since 2026.0.0
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Registered image size name.
 * @param string $source        Source label to record ('featured' or 'attached').
 * @return array{url:string,id:int,width:int,height:int,source:string}|WP_Error
 *     Descriptor on success, or WP_Error when the size cannot be resolved.
 */
function horshipsrectors_describe_attachment( int $attachment_id, string $size, string $source ) {
	$image = wp_get_attachment_image_src( $attachment_id, $size );
	if ( false === $image || empty( $image[0] ) ) {
		return new WP_Error(
			'no_image',
			__( 'The attachment could not be resolved at the requested size.', 'get-image-from-post' )
		);
	}

	return array(
		'url'    => (string) $image[0],
		'id'     => $attachment_id,
		'width'  => (int) $image[1],
		'height' => (int) $image[2],
		'source' => $source,
	);
}

/**
 * Extracts the first inline image's URL and alt text from rendered content.
 *
 * Parses the post content with DOMDocument rather than a regex so that
 * attribute order, quoting, and srcset variations do not corrupt the match.
 * Capturing the alt alongside the URL lets markup callers preserve the
 * editor-authored alternative text instead of substituting the post title
 * (WCAG 1.1.1). Returns an empty `url` when no inline image is present.
 *
 * @since 2026.0.0
 *
 * @param int $post_id Post ID.
 * @return array{url:string,alt:string} The first inline image's URL (absolute
 *     or relative) and alt text, both '' when no inline image is found.
 */
function horshipsrectors_first_inline_image( int $post_id ): array {
	$empty = array(
		'url' => '',
		'alt' => '',
	);

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return $empty;
	}

	$content = apply_filters( 'the_content', $post->post_content );
	if ( false === strpos( $content, '<img' ) ) {
		return $empty;
	}

	$previous = libxml_use_internal_errors( true );
	$document = new DOMDocument();
	$document->loadHTML(
		'<?xml encoding="utf-8" ?>' . $content,
		LIBXML_NOERROR | LIBXML_NOWARNING
	);
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	$images = $document->getElementsByTagName( 'img' );
	if ( 0 === $images->length ) {
		return $empty;
	}

	$first = $images->item( 0 );
	$src   = $first->getAttribute( 'src' );
	if ( '' === $src ) {
		return $empty;
	}

	return array(
		'url' => esc_url_raw( $src ),
		'alt' => $first->getAttribute( 'alt' ),
	);
}

add_action(
	'wp_abilities_api_init',
	static function (): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return; // Abilities API unavailable (WordPress < 6.9).
		}

		wp_register_ability(
			'get-image-from-post/get-image',
			array(
				'label'               => __( 'Get Image from Post', 'get-image-from-post' ),
				'description'         => __( 'Resolves the most representative image for a post, trying the featured image first, then the first attached image, then the first inline image in the post content. Use this to fetch a thumbnail or hero image URL for a known, readable post.', 'get-image-from-post' ),
				'category'            => 'site',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'The post ID to resolve an image for. Optional inside the Loop, where it defaults to the queried post; required otherwise.', 'get-image-from-post' ),
						),
						'size'    => array(
							'type'        => 'string',
							'description' => __( 'A registered image size name (e.g. thumbnail, medium, large, full). Defaults to large. Ignored for content-sourced images, which have no registered sizes.', 'get-image-from-post' ),
							'default'     => 'large',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'                 => 'object',
					'required'             => array( 'url', 'id', 'source' ),
					'properties'           => array(
						'url'    => array(
							'type'        => 'string',
							'format'      => 'uri',
							'description' => __( 'The resolved image URL.', 'get-image-from-post' ),
						),
						'id'     => array(
							'type'        => 'integer',
							'description' => __( 'The attachment ID, or 0 when the image was found inline in content and has no attachment record.', 'get-image-from-post' ),
						),
						'width'  => array(
							'type'        => 'integer',
							'description' => __( 'Image width in pixels at the requested size, or 0 when unknown.', 'get-image-from-post' ),
						),
						'height' => array(
							'type'        => 'integer',
							'description' => __( 'Image height in pixels at the requested size, or 0 when unknown.', 'get-image-from-post' ),
						),
						'source' => array(
							'type'        => 'string',
							'enum'        => array( 'featured', 'attached', 'content' ),
							'description' => __( 'Where the image was resolved from: the featured image, the first attached image, or the first inline image in the content.', 'get-image-from-post' ),
						),
						'alt'    => array(
							'type'        => 'string',
							'description' => __( 'The inline image\'s own alt text, present only for content-sourced images. Attachment-sourced images carry their alt in attachment meta, read at render time.', 'get-image-from-post' ),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => static function ( $input = array() ) {
					$input   = is_array( $input ) ? $input : array();
					$post_id = horshipsrectors_resolve_input_post_id( $input );

					if ( is_wp_error( $post_id ) ) {
						return $post_id;
					}

					$size = isset( $input['size'] ) ? sanitize_key( (string) $input['size'] ) : 'large';
					if ( '' === $size ) {
						$size = 'large';
					}

					return horshipsrectors_resolve_post_image( $post_id, $size );
				},
				'permission_callback' => static function ( $input = array() ) {
					if ( ! current_user_can( 'read' ) ) {
						return new WP_Error(
							'rest_forbidden',
							__( 'You are not allowed to read site content.', 'get-image-from-post' ),
							array( 'status' => 403 )
						);
					}

					$input   = is_array( $input ) ? $input : array();
					$post_id = horshipsrectors_resolve_input_post_id( $input );

					if ( is_wp_error( $post_id ) ) {
						return $post_id;
					}

					if ( ! current_user_can( 'read_post', $post_id ) ) {
						return new WP_Error(
							'rest_forbidden',
							__( 'You are not allowed to read this post.', 'get-image-from-post' ),
							array( 'status' => 403 )
						);
					}

					return true;
				},
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}
);

/**
 * Resolves and validates the target post ID from ability input.
 *
 * Falls back to the queried post when `post_id` is omitted and a post is in
 * scope (the Loop). Returns a `WP_Error` when no usable post can be determined
 * or the referenced post does not exist.
 *
 * Defined here so both `permission_callback` and `execute_callback` resolve the
 * post identically — the per-post capability check must apply to the exact post
 * the resolver will read.
 *
 * @since 2026.0.0
 *
 * @param array $input Ability input.
 * @return int|WP_Error Validated post ID, or WP_Error on failure.
 */
function horshipsrectors_resolve_input_post_id( array $input ) {
	$post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;

	if ( 0 === $post_id ) {
		$post_id = (int) get_queried_object_id();
	}

	if ( 0 === $post_id ) {
		return new WP_Error(
			'missing_post_id',
			__( 'A post_id is required when no post is in scope.', 'get-image-from-post' ),
			array( 'status' => 400 )
		);
	}

	if ( ! get_post( $post_id ) instanceof WP_Post ) {
		return new WP_Error(
			'invalid_post_id',
			__( 'No post was found for the supplied post_id.', 'get-image-from-post' ),
			array( 'status' => 404 )
		);
	}

	return $post_id;
}

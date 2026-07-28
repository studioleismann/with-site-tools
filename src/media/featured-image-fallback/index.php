<?php
/**
 * Provide content-image and neutral placeholder fallbacks for featured images.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Featured image fallback', 'with-site-tools' ),
	__( 'Uses the first suitable content image, then a neutral placeholder, for empty Featured Image and featured-image Cover blocks.', 'with-site-tools' )
);

if ( ! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug ) ) {
	return;
}

/**
 * Get the first suitable image from a media block in a post.
 *
 * Add `is-featured-image-fallback-excluded` to reject an otherwise eligible
 * media block, or `is-featured-image-fallback` to accept it regardless of size.
 *
 * @param int $post_id Post ID.
 * @return array{attachment_id:int,src:string,alt:string,width:int,height:int,srcset:string,sizes:string,is_placeholder:bool}
 */
function with_site_tools_get_featured_image_fallback( int $post_id ): array {
	static $cache = array();

	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$placeholder = array(
		'attachment_id'  => 0,
		'src'            => plugins_url( 'src/media/featured-image-fallback/placeholder.svg', WITH_SITE_TOOLS_FILE ),
		'alt'            => '',
		'width'          => 1600,
		'height'         => 900,
		'srcset'         => '',
		'sizes'          => '',
		'is_placeholder' => true,
	);

	/**
	 * Filter the neutral fallback before post content is inspected.
	 *
	 * @param array $placeholder Placeholder image data.
	 * @param int   $post_id     Post ID.
	 */
	$placeholder = apply_filters( 'with_site_tools_featured_image_placeholder', $placeholder, $post_id );
	$post        = get_post( $post_id );

	if ( ! $post ) {
		$cache[ $post_id ] = $placeholder;

		return $placeholder;
	}

	$eligible_blocks = array( 'core/image', 'core/cover', 'core/media-text' );
	$minimum_width   = (int) get_option( 'medium_size_w', 300 );
	$minimum_height  = (int) get_option( 'medium_size_h', 300 );
	$stack           = array_reverse( parse_blocks( $post->post_content ) );

	while ( $stack ) {
		$block = array_pop( $stack );

		foreach ( array_reverse( $block['innerBlocks'] ?? array() ) as $inner_block ) {
			$stack[] = $inner_block;
		}

		if ( ! in_array( $block['blockName'] ?? '', $eligible_blocks, true ) ) {
			continue;
		}

		$attributes = $block['attrs'] ?? array();
		$processor  = new WP_HTML_Tag_Processor( $block['innerHTML'] ?? '' );

		if ( ! $processor->next_tag( 'img' ) ) {
			continue;
		}

		$image_attributes = array();
		foreach ( $processor->get_attribute_names_with_prefix( '' ) as $name ) {
			$value = $processor->get_attribute( $name );

			if ( is_scalar( $value ) ) {
				$image_attributes[ $name ] = (string) $value;
			}
		}

		$image_url = $image_attributes['src'] ?? '';
		if ( '' === $image_url ) {
			continue;
		}

		$class_name = trim( ( $attributes['className'] ?? '' ) . ' ' . ( $image_attributes['class'] ?? '' ) );
		$is_forced  = str_contains( ' ' . $class_name . ' ', ' is-featured-image-fallback ' );

		if ( str_contains( ' ' . $class_name . ' ', ' is-featured-image-fallback-excluded ' ) ) {
			continue;
		}

		if ( ! $is_forced && 'thumbnail' === ( $attributes['sizeSlug'] ?? '' ) ) {
			continue;
		}

		$rendered_dimensions = array(
			'width'  => $attributes['width'] ?? ( $image_attributes['width'] ?? '' ),
			'height' => $attributes['height'] ?? ( $image_attributes['height'] ?? '' ),
		);
		$image_style         = $image_attributes['style'] ?? '';

		foreach ( array( 'width', 'height' ) as $dimension ) {
			if (
				'' === $rendered_dimensions[ $dimension ]
				&& preg_match( '/(?:^|;)\s*' . $dimension . ':\s*([0-9.]+)px(?:;|$)/', $image_style, $matches )
			) {
				$rendered_dimensions[ $dimension ] = $matches[1];
			}
		}

		if ( ! $is_forced ) {
			if (
				$minimum_width > 0
				&& preg_match( '/^([0-9.]+)(?:px)?$/', (string) $rendered_dimensions['width'], $matches )
				&& (float) $matches[1] < $minimum_width
			) {
				continue;
			}

			if (
				$minimum_height > 0
				&& preg_match( '/^([0-9.]+)(?:px)?$/', (string) $rendered_dimensions['height'], $matches )
				&& (float) $matches[1] < $minimum_height
			) {
				continue;
			}
		}

		$attachment_id = isset( $attributes['id'] ) ? (int) $attributes['id'] : 0;
		if ( ! $attachment_id && preg_match( '/(?:^|\s)wp-image-(\d+)(?:\s|$)/', $class_name, $matches ) ) {
			$attachment_id = (int) $matches[1];
		}

		$cache[ $post_id ] = array(
			'attachment_id'  => $attachment_id,
			'src'            => esc_url_raw( $image_url ),
			'alt'            => sanitize_text_field( $image_attributes['alt'] ?? '' ),
			'width'          => isset( $image_attributes['width'] ) ? (int) $image_attributes['width'] : 0,
			'height'         => isset( $image_attributes['height'] ) ? (int) $image_attributes['height'] : 0,
			'srcset'         => sanitize_text_field( $image_attributes['srcset'] ?? '' ),
			'sizes'          => sanitize_text_field( $image_attributes['sizes'] ?? '' ),
			'is_placeholder' => false,
		);

		return $cache[ $post_id ];
	}

	$cache[ $post_id ] = $placeholder;

	return $placeholder;
}

/**
 * Add a fallback image to a featured-image Cover block.
 *
 * @param string   $block_content Rendered Cover markup.
 * @param array    $block         Parsed block data.
 * @param WP_Block $instance      Block instance.
 * @return string
 */
function with_site_tools_render_cover_featured_image_fallback(
	string $block_content,
	array $block,
	WP_Block $instance
): string {
	$attributes = $instance->attributes;

	if (
		empty( $attributes['useFeaturedImage'] )
		|| str_contains( $block_content, 'wp-block-cover__image-background' )
		|| empty( $instance->context['postId'] )
	) {
		return $block_content;
	}

	$fallback      = with_site_tools_get_featured_image_fallback( (int) $instance->context['postId'] );
	$image_url     = $fallback['src'];
	$image_alt     = $fallback['alt'];
	$size_slug     = $attributes['sizeSlug'] ?? 'post-thumbnail';
	$attachment_id = $fallback['attachment_id'];

	if ( $attachment_id ) {
		$attachment_url = wp_get_attachment_image_url( $attachment_id, $size_slug );
		if ( $attachment_url ) {
			$image_url = $attachment_url;
		}

		$attachment_alt = trim( wp_strip_all_tags( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
		if ( '' !== $attachment_alt ) {
			$image_alt = $attachment_alt;
		}
	}

	$object_position = isset( $attributes['focalPoint'] )
		? round( (float) $attributes['focalPoint']['x'] * 100 ) . '% ' . round( (float) $attributes['focalPoint']['y'] * 100 ) . '%'
		: '50% 50%';

	if ( ! empty( $attributes['hasParallax'] ) || ! empty( $attributes['isRepeated'] ) ) {
		$image = new WP_HTML_Tag_Processor( '<div></div>' );
		$image->next_tag();
		$image->add_class( 'wp-block-cover__image-background' );

		if ( ! empty( $attributes['hasParallax'] ) ) {
			$image->add_class( 'has-parallax' );
		}
		if ( ! empty( $attributes['isRepeated'] ) ) {
			$image->add_class( 'is-repeated' );
		}
		if ( '' !== $image_alt ) {
			$image->set_attribute( 'role', 'img' );
			$image->set_attribute( 'aria-label', $image_alt );
		}

		$image->set_attribute(
			'style',
			'background-position:' . $object_position . ';background-image:url(' . esc_url( $image_url ) . ');'
		);
	} else {
		$image_attributes = array(
			'alt'             => $image_alt,
			'data-object-fit' => 'cover',
			'class'           => 'wp-block-cover__image-background',
		);

		if ( $attachment_id ) {
			$image_attributes['class'] .= ' wp-image-' . $attachment_id;
		}

		if ( '50% 50%' !== $object_position ) {
			$image_attributes['data-object-position'] = $object_position;
			$image_attributes['style']                = 'object-position:' . $object_position . ';';
		}

		if ( $attachment_id ) {
			$image_html = wp_get_attachment_image( $attachment_id, $size_slug, false, $image_attributes );
			$image      = new WP_HTML_Tag_Processor( $image_html );
			$image->next_tag( 'img' );
		} else {
			$image_attributes['src']    = $image_url;
			$image_attributes['width']  = ! empty( $fallback['width'] ) ? $fallback['width'] : null;
			$image_attributes['height'] = ! empty( $fallback['height'] ) ? $fallback['height'] : null;
			$image_attributes['srcset'] = ! empty( $fallback['srcset'] ) ? $fallback['srcset'] : null;
			$image_attributes['sizes']  = ! empty( $fallback['sizes'] ) ? $fallback['sizes'] : null;
			$image_attributes           = array_merge(
				$image_attributes,
				wp_get_loading_optimization_attributes( 'img', $image_attributes, 'core/cover' )
			);

			$image = new WP_HTML_Tag_Processor( '<img>' );
			$image->next_tag();

			foreach ( $image_attributes as $name => $value ) {
				if ( null !== $value && is_scalar( $value ) ) {
					$image->set_attribute( (string) $name, (string) $value );
				}
			}
		}
	}

	if ( ! preg_match( '/<div\b[^>]+wp-block-cover__inner-container[\s|"][^>]*>/U', $block_content, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $block_content;
	}

	$offset = $matches[0][1];

	return substr( $block_content, 0, $offset ) . $image->get_updated_html() . substr( $block_content, $offset );
}
add_filter( 'render_block_core/cover', 'with_site_tools_render_cover_featured_image_fallback', 10, 3 );

/**
 * Replace the Core Featured Image render callback.
 *
 * @param array  $args Block registration arguments.
 * @param string $name Block type name.
 * @return array
 */
function with_site_tools_register_featured_image_fallback_renderer( array $args, string $name ): array {
	if ( 'core/post-featured-image' === $name ) {
		$args['render_callback'] = 'with_site_tools_render_post_featured_image_with_fallback';
	}

	return $args;
}
add_filter( 'register_block_type_args', 'with_site_tools_register_featured_image_fallback_renderer', 10, 2 );

/**
 * Build fallback thumbnail markup during the second Featured Image render.
 *
 * @param string       $html         Existing thumbnail markup.
 * @param int          $post_id      Post ID.
 * @param int          $thumbnail_id Featured image attachment ID.
 * @param string|int[] $size         Requested image size.
 * @param string|array $attr         Requested image attributes.
 * @return string
 */
function with_site_tools_get_featured_image_fallback_html(
	string $html,
	int $post_id,
	int $thumbnail_id,
	$size,
	$attr
): string {
	if ( '' !== $html ) {
		return $html;
	}

	$fallback                      = with_site_tools_get_featured_image_fallback( $post_id );
	$requested_attributes          = wp_parse_args( $attr );
	$requested_attributes['class'] = trim( 'wp-post-image ' . ( $requested_attributes['class'] ?? '' ) );

	if ( $fallback['attachment_id'] ) {
		$attachment_html = wp_get_attachment_image(
			$fallback['attachment_id'],
			$size,
			false,
			$requested_attributes
		);

		if ( '' !== $attachment_html ) {
			return $attachment_html;
		}
	}

	$image_attributes = array_merge(
		array(
			'src'    => $fallback['src'],
			'alt'    => $fallback['alt'],
			'width'  => ! empty( $fallback['width'] ) ? $fallback['width'] : null,
			'height' => ! empty( $fallback['height'] ) ? $fallback['height'] : null,
			'srcset' => ! empty( $fallback['srcset'] ) ? $fallback['srcset'] : null,
			'sizes'  => ! empty( $fallback['sizes'] ) ? $fallback['sizes'] : null,
		),
		$requested_attributes
	);
	$image_attributes = array_merge(
		$image_attributes,
		wp_get_loading_optimization_attributes( 'img', $image_attributes, 'post_thumbnail' )
	);

	$image = new WP_HTML_Tag_Processor( '<img>' );
	$image->next_tag();

	foreach ( $image_attributes as $name => $value ) {
		if ( null !== $value && is_scalar( $value ) ) {
			$image->set_attribute( (string) $name, (string) $value );
		}
	}

	return $image->get_updated_html();
}

/**
 * Render Core Featured Image with the configured fallback.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Saved block content.
 * @param WP_Block $block      Block instance.
 * @return string
 */
function with_site_tools_render_post_featured_image_with_fallback(
	array $attributes,
	string $content,
	WP_Block $block
): string {
	$attributes['useFirstImageFromPost'] = false;
	$block_content                       = render_block_core_post_featured_image( $attributes, $content, $block );

	if ( '' !== $block_content ) {
		return $block_content;
	}

	add_filter( 'post_thumbnail_html', 'with_site_tools_get_featured_image_fallback_html', 10, 5 );

	try {
		return render_block_core_post_featured_image( $attributes, $content, $block );
	} finally {
		remove_filter( 'post_thumbnail_html', 'with_site_tools_get_featured_image_fallback_html', 10 );
	}
}

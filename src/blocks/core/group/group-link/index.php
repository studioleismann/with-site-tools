<?php
/**
 * Add an accessible link overlay to Group blocks.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Linked Group', 'with-site-tools' ),
	__( 'Adds the Core link interface to Group blocks and renders an accessible overlay link.', 'with-site-tools' )
);

if ( ! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug ) ) {
	return;
}

/**
 * Build a concise accessible label from Group content.
 *
 * @param string $block_content Rendered Group markup.
 * @return string
 */
function with_site_tools_get_linked_group_label( string $block_content ): string {
	$label = html_entity_decode( wp_strip_all_tags( $block_content ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	$label = preg_replace( '/\s+/', ' ', trim( $label ) );

	if ( ! is_string( $label ) || '' === $label ) {
		return __( 'Open linked group', 'with-site-tools' );
	}

	return wp_html_excerpt( $label, 120, '&hellip;' );
}

/**
 * Render the Group overlay link.
 *
 * Existing withBase attributes remain readable so content created with
 * with-base continues to work after moving the behavior into this plugin.
 *
 * @param string $block_content Rendered block markup.
 * @param array  $block         Parsed block data.
 * @return string
 */
function with_site_tools_render_linked_group( string $block_content, array $block ): string {
	$attributes = $block['attrs'] ?? array();
	$is_legacy  = ! isset( $attributes['withSiteToolsGroupLinkUrl'] )
		&& isset( $attributes['withBaseGroupLinkUrl'] );
	$url_value  = $attributes['withSiteToolsGroupLinkUrl']
		?? $attributes['withBaseGroupLinkUrl']
		?? '';
	$url        = '' !== $url_value
		? esc_url_raw( trim( (string) $url_value ) )
		: '';

	if ( '' === $url ) {
		return $block_content;
	}

	$target_value = $attributes['withSiteToolsGroupLinkTarget']
		?? $attributes['withBaseGroupLinkTarget']
		?? '';
	$rel_value    = $attributes['withSiteToolsGroupLinkRel']
		?? $attributes['withBaseGroupLinkRel']
		?? '';
	$label_value  = $attributes['withSiteToolsGroupLinkLabel']
		?? $attributes['withBaseGroupLinkLabel']
		?? '';
	$target       = '' !== $target_value
		? sanitize_key( (string) $target_value )
		: '';
	$rel          = '' !== $rel_value
		? sanitize_text_field( (string) $rel_value )
		: '';
	$label        = '' !== $label_value
		? sanitize_text_field( (string) $label_value )
		: '';

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() ) {
		return $block_content;
	}

	$wrapper_tag = strtolower( (string) $processor->get_tag() );
	if ( $is_legacy ) {
		$processor->add_class( 'with-base-group-link' );
	}
	$processor->add_class( 'with-site-tools-group-link' );
	$block_content = $processor->get_updated_html();

	$link = new WP_HTML_Tag_Processor( '<a></a>' );
	$link->next_tag( 'a' );
	if ( $is_legacy ) {
		$link->add_class( 'with-base-group-link__overlay' );
	}
	$link->add_class( 'with-site-tools-group-link__overlay' );
	$link->set_attribute( 'href', $url );
	$link->set_attribute(
		'aria-label',
		'' !== $label ? $label : with_site_tools_get_linked_group_label( $block_content )
	);

	if ( '_blank' === $target ) {
		$link->set_attribute( 'target', '_blank' );

		$rel_tokens = array_filter( array_map( 'trim', explode( ' ', $rel ) ) );
		$rel_tokens = array_unique( array_merge( $rel_tokens, array( 'noopener', 'noreferrer' ) ) );
		$rel        = implode( ' ', $rel_tokens );
	}

	if ( '' !== $rel ) {
		$link->set_attribute( 'rel', $rel );
	}

	$linked_content = preg_replace(
		'/(<\/' . preg_quote( $wrapper_tag, '/' ) . '>\s*)$/i',
		$link->get_updated_html() . '$1',
		$block_content,
		1
	);

	return is_string( $linked_content ) ? $linked_content : $block_content;
}
add_filter( 'render_block_core/group', 'with_site_tools_render_linked_group', 10, 2 );

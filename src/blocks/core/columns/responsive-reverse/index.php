<?php
/**
 * Add responsive reverse options to Columns blocks.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Responsive Columns order', 'with-site-tools' ),
	__( 'Adds controls for reversing the visual order of Columns on mobile, tablet, or desktop.', 'with-site-tools' )
);

if ( ! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug ) ) {
	return;
}

/**
 * Add responsive reverse classes to rendered Columns markup.
 *
 * @param string $block_content Rendered block markup.
 * @param array  $block         Parsed block data.
 * @return string
 */
function with_site_tools_render_responsive_reversed_columns( string $block_content, array $block ): string {
	$reverse_on = $block['attrs']['withSiteToolsReverseColumnsOn']
		?? $block['attrs']['withBaseReverseColumnsOn']
		?? array();

	if ( ! is_array( $reverse_on ) ) {
		return $block_content;
	}

	$class_names = array();

	foreach ( array( 'mobile', 'tablet', 'desktop' ) as $viewport ) {
		if ( ! empty( $reverse_on[ $viewport ] ) ) {
			$class_names[] = 'has-reversed-columns-' . $viewport;
		}
	}

	if ( array() === $class_names ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag( array( 'class_name' => 'wp-block-columns' ) ) ) {
		return $block_content;
	}

	foreach ( $class_names as $class_name ) {
		$processor->add_class( $class_name );
	}

	return $processor->get_updated_html();
}
add_filter( 'render_block_core/columns', 'with_site_tools_render_responsive_reversed_columns', 10, 2 );

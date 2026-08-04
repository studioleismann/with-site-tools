<?php
/**
 * Add selectable Dashicon markers to List blocks.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Custom List icons', 'with-site-tools' ),
	__( 'Adds a toolbar picker for using a supported Dashicon as a list marker.', 'with-site-tools' )
);

if ( ! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug ) ) {
	return;
}

/**
 * Get supported Dashicon marker codes.
 *
 * @return array<string, string>
 */
function with_site_tools_get_list_icon_codes(): array {
	return array(
		'yes'             => '\\f147',
		'no'              => '\\f158',
		'saved'           => '\\f15e',
		'star-filled'     => '\\f155',
		'heart'           => '\\f487',
		'warning'         => '\\f534',
		'info'            => '\\f348',
		'lightbulb'       => '\\f339',
		'arrow-right-alt' => '\\f344',
		'plus'            => '\\f132',
		'minus'           => '\\f460',
		'flag'            => '\\f227',
		'marker'          => '\\f159',
		'admin-users'     => '\\f110',
	);
}

/**
 * Add the selected marker to rendered List markup.
 *
 * @param string $block_content Rendered block markup.
 * @param array  $block         Parsed block data.
 * @return string
 */
function with_site_tools_render_custom_list_icon( string $block_content, array $block ): string {
	$attributes = $block['attrs'] ?? array();
	$is_legacy  = ! isset( $attributes['withSiteToolsListIcon'] )
		&& isset( $attributes['withBaseListIcon'] );
	$icon_value = $attributes['withSiteToolsListIcon']
		?? $attributes['withBaseListIcon']
		?? '';
	$icon_name  = '' !== $icon_value
		? sanitize_key( (string) $icon_value )
		: '';
	$icon_map   = with_site_tools_get_list_icon_codes();

	if ( ! isset( $icon_map[ $icon_name ] ) ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() ) {
		return $block_content;
	}

	$processor->add_class( 'has-custom-list-icon' );

	$style = trim( (string) $processor->get_attribute( 'style' ) );
	if ( '' !== $style && ! str_ends_with( $style, ';' ) ) {
		$style .= ';';
	}

	if ( $is_legacy ) {
		$style .= '--with-base-list-icon:"' . $icon_map[ $icon_name ] . '";';
	}
	$style .= '--with-site-tools-list-icon:"' . $icon_map[ $icon_name ] . '";';
	$processor->set_attribute( 'style', $style );

	wp_enqueue_style( 'dashicons' );

	return $processor->get_updated_html();
}
add_filter( 'render_block_core/list', 'with_site_tools_render_custom_list_icon', 10, 2 );

/**
 * Declare Dashicons as a dependency of this feature's selective block style.
 *
 * @param array<int, string> $dependencies Style dependencies.
 * @param string             $feature_slug Feature slug.
 * @return array<int, string>
 */
function with_site_tools_add_list_icon_style_dependency( array $dependencies, string $feature_slug ): array {
	if ( 'blocks/core/list/custom-icons' === $feature_slug ) {
		$dependencies[] = 'dashicons';
	}

	return array_values( array_unique( $dependencies ) );
}
add_filter( 'with_site_tools_feature_style_dependencies', 'with_site_tools_add_list_icon_style_dependency', 10, 2 );

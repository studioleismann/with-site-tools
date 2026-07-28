<?php
/**
 * Disable margin controls for the Spacer block.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Disable Spacer margins', 'with-site-tools' ),
	__( 'Removes margin controls from the Spacer block to keep spacing responsibilities unambiguous.', 'with-site-tools' )
);

if ( ! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug ) ) {
	return;
}

/**
 * Disable Core Spacer margin support.
 *
 * @param array  $args Block registration arguments.
 * @param string $name Block type name.
 * @return array
 */
function with_site_tools_disable_spacer_margin( array $args, string $name ): array {
	if ( 'core/spacer' !== $name ) {
		return $args;
	}

	$args['supports']                      = $args['supports'] ?? array();
	$args['supports']['spacing']           = $args['supports']['spacing'] ?? array();
	$args['supports']['spacing']['margin'] = false;

	return $args;
}
add_filter( 'register_block_type_args', 'with_site_tools_disable_spacer_margin', 10, 2 );

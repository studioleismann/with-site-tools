<?php
/**
 * Disable the File block preview by default.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Disable file preview by default', 'with-site-tools' ),
	__( 'New File blocks start without an embedded PDF preview.', 'with-site-tools' )
);

if ( ! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug ) ) {
	return;
}

/**
 * Change the default preview setting for the Core File block.
 *
 * @param array  $args Block registration arguments.
 * @param string $name Block type name.
 * @return array
 */
function with_site_tools_disable_file_preview_by_default( array $args, string $name ): array {
	if ( 'core/file' !== $name ) {
		return $args;
	}

	$args['attributes']                              = $args['attributes'] ?? array();
	$args['attributes']['displayPreview']            = $args['attributes']['displayPreview'] ?? array();
	$args['attributes']['displayPreview']['default'] = false;

	return $args;
}
add_filter( 'register_block_type_args', 'with_site_tools_disable_file_preview_by_default', 10, 2 );

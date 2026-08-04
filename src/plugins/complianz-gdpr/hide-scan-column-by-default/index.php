<?php
/**
 * Hide the Complianz Website Scan column by default.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Hide Complianz scan column by default', 'with-site-tools' ),
	__( 'Hides the Complianz Website Scan column by default while keeping it available in Screen Options.', 'with-site-tools' )
);

if (
	! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug )
	|| ! with_site_tools_is_feature_available( $with_site_tools_feature_slug )
) {
	return;
}

/**
 * Add the Complianz scan column to the default hidden columns on post lists.
 *
 * @param string[]  $hidden Columns hidden by default.
 * @param WP_Screen $screen Current admin screen.
 * @return string[]
 */
function with_site_tools_complianz_hide_scan_column_by_default( array $hidden, WP_Screen $screen ): array {
	if ( 'edit' !== $screen->base || in_array( 'cmplz_scan', $hidden, true ) ) {
		return $hidden;
	}

	$hidden[] = 'cmplz_scan';

	return $hidden;
}
add_filter( 'default_hidden_columns', 'with_site_tools_complianz_hide_scan_column_by_default', 10, 2 );

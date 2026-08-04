<?php
/**
 * Remove With Site Tools data.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_site_transient( 'with_site_tools_github_release' );

if ( ! is_multisite() ) {
	delete_option( 'with_site_tools_feature_settings' );
	return;
}

$with_site_tools_offset = 0;

do {
	$with_site_tools_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 100,
			'offset' => $with_site_tools_offset,
		)
	);

	foreach ( $with_site_tools_site_ids as $with_site_tools_site_id ) {
		switch_to_blog( (int) $with_site_tools_site_id );
		delete_option( 'with_site_tools_feature_settings' );
		restore_current_blog();
	}

	$with_site_tools_site_count = count( $with_site_tools_site_ids );
	$with_site_tools_offset    += 100;
} while ( 100 === $with_site_tools_site_count );

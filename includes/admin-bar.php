<?php
/**
 * Site Tools admin-bar navigation.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Add a direct link to Site Tools for administrators.
 *
 * No build, sync, or shell process can be triggered from WordPress.
 *
 * @param WP_Admin_Bar $admin_bar WordPress admin bar.
 * @return void
 */
function with_site_tools_register_admin_bar( WP_Admin_Bar $admin_bar ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$enabled_count = 0;
	$settings      = with_site_tools_get_feature_settings();

	foreach ( array_keys( with_site_tools_get_registered_features() ) as $slug ) {
		if (
			! empty( $settings[ $slug ] )
			&& with_site_tools_is_feature_available( $slug )
		) {
			++$enabled_count;
		}
	}

	$admin_bar->add_node(
		array(
			'id'     => 'with-site-tools',
			'parent' => 'top-secondary',
			'title'  => sprintf(
				/* translators: %d: number of enabled features. */
				_n( 'Site Tools (%d)', 'Site Tools (%d)', $enabled_count, 'with-site-tools' ),
				$enabled_count
			),
			'href'   => admin_url( 'tools.php?page=with-site-tools' ),
			'meta'   => array(
				'title' => __( 'Open Site Tools settings', 'with-site-tools' ),
			),
		)
	);
}
add_action( 'admin_bar_menu', 'with_site_tools_register_admin_bar', 210 );

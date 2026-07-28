<?php
/**
 * Feature registration and discovery.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

if ( ! isset( $GLOBALS['with_site_tools_registered_features'] ) ) {
	$GLOBALS['with_site_tools_registered_features'] = array();
}

/**
 * Register one feature.
 *
 * The feature slug, group, and optional plugin dependency are derived from the
 * feature directory, so a feature cannot drift away from its filesystem owner.
 *
 * @param string $directory   Absolute feature directory.
 * @param string $label       Human-readable feature label.
 * @param string $description Human-readable feature description.
 * @return string Feature slug, or an empty string for an invalid directory.
 */
function with_site_tools_register_feature( string $directory, string $label, string $description ): string {
	$plugin_directory  = trailingslashit( wp_normalize_path( WITH_SITE_TOOLS_DIR . '/src' ) );
	$feature_directory = trailingslashit( wp_normalize_path( $directory ) );

	if ( ! str_starts_with( $feature_directory, $plugin_directory ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Feature directories must be inside the With Site Tools source directory.', 'with-site-tools' ),
			esc_html( WITH_SITE_TOOLS_VERSION )
		);

		return '';
	}

	$slug     = trim( substr( $feature_directory, strlen( $plugin_directory ) ), '/' );
	$segments = explode( '/', $slug );
	$expected_segment_counts = array(
		'blocks'  => 4,
		'media'   => 2,
		'plugins' => 3,
		'site'    => 2,
	);

	if (
		! isset( $expected_segment_counts[ $segments[0] ] )
		|| count( $segments ) !== $expected_segment_counts[ $segments[0] ]
	) {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Feature directories must follow a supported With Site Tools path.', 'with-site-tools' ),
			esc_html( WITH_SITE_TOOLS_VERSION )
		);

		return '';
	}

	$feature = array(
		'slug'        => $slug,
		'label'       => $label,
		'description' => $description,
		'directory'   => untrailingslashit( $feature_directory ),
		'group'       => $segments[0],
		'plugin'      => 'plugins' === $segments[0] && isset( $segments[1] ) ? sanitize_key( $segments[1] ) : '',
	);

	if ( isset( $GLOBALS['with_site_tools_registered_features'][ $slug ] ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: feature slug. */
				esc_html__( 'The feature slug "%s" is registered more than once.', 'with-site-tools' ),
				esc_html( $slug )
			),
			esc_html( WITH_SITE_TOOLS_VERSION )
		);

		return '';
	}

	$GLOBALS['with_site_tools_registered_features'][ $slug ] = $feature;

	return $slug;
}

/**
 * Get all registered features.
 *
 * @return array<string, array{slug:string,label:string,description:string,directory:string,group:string,plugin:string}>
 */
function with_site_tools_get_registered_features(): array {
	$features = $GLOBALS['with_site_tools_registered_features'] ?? array();

	if ( ! is_array( $features ) ) {
		return array();
	}

	ksort( $features, SORT_NATURAL | SORT_FLAG_CASE );

	return $features;
}

/**
 * Check whether an optional plugin directory is active.
 *
 * Both site-active and network-active plugins are supported. The check uses the
 * plugin directory rather than a main filename because optional integrations
 * may have different entrypoint filenames across editions.
 *
 * @param string $plugin_directory Plugin directory slug.
 * @return bool
 */
function with_site_tools_is_plugin_active( string $plugin_directory ): bool {
	$plugin_directory = sanitize_key( $plugin_directory );

	if ( '' === $plugin_directory ) {
		return true;
	}

	$active_plugins = get_option( 'active_plugins', array() );
	$active_plugins = is_array( $active_plugins ) ? $active_plugins : array();

	if ( is_multisite() ) {
		$network_plugins = get_site_option( 'active_sitewide_plugins', array() );
		if ( is_array( $network_plugins ) ) {
			$active_plugins = array_merge( $active_plugins, array_keys( $network_plugins ) );
		}
	}

	foreach ( $active_plugins as $plugin_file ) {
		if ( dirname( (string) $plugin_file ) === $plugin_directory ) {
			return true;
		}
	}

	return false;
}

/**
 * Check whether the inferred dependency for a feature is available.
 *
 * @param string $feature_slug Feature slug.
 * @return bool
 */
function with_site_tools_is_feature_available( string $feature_slug ): bool {
	$segments = explode( '/', $feature_slug );

	if ( 'plugins' !== ( $segments[0] ?? '' ) ) {
		return true;
	}

	return isset( $segments[1] ) && with_site_tools_is_plugin_active( $segments[1] );
}

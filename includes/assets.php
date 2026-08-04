<?php
/**
 * Feature asset registration.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Read wp-scripts dependency metadata for an asset.
 *
 * @param string $asset_file Absolute .asset.php path.
 * @return array{dependencies:array<int,string>,version:string}
 */
function with_site_tools_read_asset_file( string $asset_file ): array {
	$asset            = file_exists( $asset_file ) ? require $asset_file : array();
	$asset_base       = preg_replace( '/\.asset\.php$/', '', $asset_file );
	$fallback_version = WITH_SITE_TOOLS_VERSION;

	if ( is_string( $asset_base ) ) {
		foreach ( array( $asset_base . '.js', $asset_base . '.css' ) as $asset_path ) {
			if ( file_exists( $asset_path ) ) {
				$fallback_version = (string) filemtime( $asset_path );
				break;
			}
		}
	}

	return array(
		'dependencies' => isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] )
			? $asset['dependencies']
			: array(),
		'version'      => isset( $asset['version'] ) && is_string( $asset['version'] )
			? $asset['version']
			: $fallback_version,
	);
}

/**
 * Build a unique WordPress asset handle.
 *
 * @param string $feature_slug Feature slug.
 * @param string $context      Asset context.
 * @return string
 */
function with_site_tools_get_asset_handle( string $feature_slug, string $context ): string {
	return sanitize_key( 'with-site-tools-' . str_replace( '/', '-', $feature_slug ) . '-' . $context );
}

/**
 * Enqueue editor scripts only for enabled and available features.
 *
 * @return void
 */
function with_site_tools_enqueue_editor_assets(): void {
	foreach ( with_site_tools_get_registered_features() as $feature ) {
		$slug = $feature['slug'];

		if (
			! with_site_tools_is_feature_enabled( $slug )
			|| ! with_site_tools_is_feature_available( $slug )
		) {
			continue;
		}

		$relative_script = 'build/' . $slug . '/editor.js';
		$script_file     = WITH_SITE_TOOLS_DIR . '/' . $relative_script;

		if ( ! file_exists( $script_file ) ) {
			continue;
		}

		$asset  = with_site_tools_read_asset_file( substr( $script_file, 0, -3 ) . '.asset.php' );
		$handle = with_site_tools_get_asset_handle( $slug, 'editor' );

		wp_enqueue_script(
			$handle,
			plugins_url( $relative_script, WITH_SITE_TOOLS_FILE ),
			$asset['dependencies'],
			$asset['version'],
			false
		);
		wp_set_script_translations( $handle, 'with-site-tools' );
	}
}
add_action( 'enqueue_block_editor_assets', 'with_site_tools_enqueue_editor_assets' );

/**
 * Register selectively loaded block feature styles.
 *
 * @return void
 */
function with_site_tools_register_block_feature_styles(): void {
	foreach ( with_site_tools_get_registered_features() as $feature ) {
		$slug = $feature['slug'];

		if (
			'blocks' !== $feature['group']
			|| ! with_site_tools_is_feature_enabled( $slug )
			|| ! with_site_tools_is_feature_available( $slug )
		) {
			continue;
		}

		$segments = explode( '/', $slug );

		if ( count( $segments ) < 4 ) {
			continue;
		}

		$relative_style = 'build/' . $slug . '/style.css';
		$style_file     = WITH_SITE_TOOLS_DIR . '/' . $relative_style;

		if ( ! file_exists( $style_file ) ) {
			continue;
		}

		$asset        = with_site_tools_read_asset_file( substr( $style_file, 0, -4 ) . '.asset.php' );
		$dependencies = apply_filters(
			'with_site_tools_feature_style_dependencies',
			$asset['dependencies'],
			$slug
		);
		$handle       = with_site_tools_get_asset_handle( $slug, 'style' );
		$block_name   = sanitize_key( $segments[1] ) . '/' . sanitize_key( $segments[2] );

		wp_register_style(
			$handle,
			plugins_url( $relative_style, WITH_SITE_TOOLS_FILE ),
			is_array( $dependencies ) ? $dependencies : array(),
			$asset['version']
		);

		wp_enqueue_block_style(
			$block_name,
			array(
				'handle' => $handle,
			)
		);
	}
}
add_action( 'init', 'with_site_tools_register_block_feature_styles', 20 );

/**
 * Enqueue styles owned by enabled non-block features.
 *
 * @return void
 */
function with_site_tools_enqueue_site_feature_styles(): void {
	foreach ( with_site_tools_get_registered_features() as $feature ) {
		$slug = $feature['slug'];

		if (
			'blocks' === $feature['group']
			|| ! with_site_tools_is_feature_enabled( $slug )
			|| ! with_site_tools_is_feature_available( $slug )
		) {
			continue;
		}

		$relative_style = 'build/' . $slug . '/style.css';
		$style_file     = WITH_SITE_TOOLS_DIR . '/' . $relative_style;

		if ( ! file_exists( $style_file ) ) {
			continue;
		}

		$asset = with_site_tools_read_asset_file( substr( $style_file, 0, -4 ) . '.asset.php' );

		wp_enqueue_style(
			with_site_tools_get_asset_handle( $slug, 'style' ),
			plugins_url( $relative_style, WITH_SITE_TOOLS_FILE ),
			$asset['dependencies'],
			$asset['version']
		);
	}
}
add_action( 'wp_enqueue_scripts', 'with_site_tools_enqueue_site_feature_styles' );

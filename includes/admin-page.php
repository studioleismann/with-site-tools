<?php
/**
 * Site Tools settings page.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the Tools > Site Tools page.
 *
 * @return void
 */
function with_site_tools_register_admin_page(): void {
	add_management_page(
		__( 'Site Tools', 'with-site-tools' ),
		__( 'Site Tools', 'with-site-tools' ),
		'manage_options',
		'with-site-tools',
		'with_site_tools_render_admin_page'
	);
}
add_action( 'admin_menu', 'with_site_tools_register_admin_page' );

/**
 * Render the settings page application root.
 *
 * @return void
 */
function with_site_tools_render_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to manage these settings.', 'with-site-tools' ) );
	}

	echo '<div class="wrap with-site-tools-admin-page"><div id="with-site-tools-admin"></div></div>';
}

/**
 * Build automatically grouped feature data for the settings UI.
 *
 * @return array<int, array{slug:string,label:string,description:string,group:string,groupSlug:string,context:string,plugin:string,available:bool,enabled:bool}>
 */
function with_site_tools_get_admin_features(): array {
	$settings = with_site_tools_get_feature_settings();
	$features = array();
	$groups   = array(
		'blocks'  => __( 'Blocks', 'with-site-tools' ),
		'media'   => __( 'Media', 'with-site-tools' ),
		'plugins' => __( 'Plugins', 'with-site-tools' ),
		'site'    => __( 'Site', 'with-site-tools' ),
	);

	foreach ( with_site_tools_get_registered_features() as $feature ) {
		$slug      = $feature['slug'];
		$segments  = explode( '/', $slug );
		$context   = '';
		$available = with_site_tools_is_feature_available( $slug );

		if ( 'blocks' === $feature['group'] && isset( $segments[2] ) ) {
			$context = sprintf(
				/* translators: %s: Core block name. */
				__( 'Core block: %s', 'with-site-tools' ),
				ucwords( str_replace( '-', ' ', $segments[2] ) )
			);
		} elseif ( 'plugins' === $feature['group'] && isset( $segments[1] ) ) {
			$context = sprintf(
				/* translators: %s: plugin directory name. */
				__( 'Integration: %s', 'with-site-tools' ),
				ucwords( str_replace( '-', ' ', $segments[1] ) )
			);
		}

		$features[] = array(
			'slug'        => $slug,
			'label'       => $feature['label'],
			'description' => $feature['description'],
			'group'       => $groups[ $feature['group'] ] ?? ucwords( str_replace( '-', ' ', $feature['group'] ) ),
			'groupSlug'   => $feature['group'],
			'context'     => $context,
			'plugin'      => $feature['plugin'],
			'available'   => $available,
			'enabled'     => $available && true === ( $settings[ $slug ] ?? false ),
		);
	}

	return $features;
}

/**
 * Enqueue the settings page assets.
 *
 * @param string $hook_suffix Current admin screen hook.
 * @return void
 */
function with_site_tools_enqueue_admin_page_assets( string $hook_suffix ): void {
	if ( 'tools_page_with-site-tools' !== $hook_suffix ) {
		return;
	}

	$script_file = WITH_SITE_TOOLS_DIR . '/build/admin/index.js';
	$style_file  = WITH_SITE_TOOLS_DIR . '/build/admin/style.css';

	if ( ! file_exists( $script_file ) ) {
		return;
	}

	$asset  = with_site_tools_read_asset_file( WITH_SITE_TOOLS_DIR . '/build/admin/index.asset.php' );
	$handle = 'with-site-tools-admin';

	if ( wp_script_is( 'wp-dataviews', 'registered' ) ) {
		$asset['dependencies'][] = 'wp-dataviews';
	}

	wp_enqueue_style( 'wp-components' );

	if ( file_exists( $style_file ) ) {
		$style_asset = with_site_tools_read_asset_file( WITH_SITE_TOOLS_DIR . '/build/admin/style.asset.php' );
		wp_enqueue_style(
			$handle,
			plugins_url( 'build/admin/style.css', WITH_SITE_TOOLS_FILE ),
			array( 'wp-components' ),
			$style_asset['version']
		);
	}

	wp_enqueue_script(
		$handle,
		plugins_url( 'build/admin/index.js', WITH_SITE_TOOLS_FILE ),
		array_values( array_unique( $asset['dependencies'] ) ),
		$asset['version'],
		true
	);
	wp_set_script_translations( $handle, 'with-site-tools' );

	wp_add_inline_script(
		$handle,
		'window.withSiteToolsAdmin = ' . wp_json_encode(
			array(
				'features'   => with_site_tools_get_admin_features(),
				'optionName' => WITH_SITE_TOOLS_SETTINGS_OPTION,
			)
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'with_site_tools_enqueue_admin_page_assets' );

/**
 * Add a Settings link to the Plugins screen.
 *
 * @param array<int, string> $links Existing plugin action links.
 * @return array<int, string>
 */
function with_site_tools_add_plugin_action_links( array $links ): array {
	array_unshift(
		$links,
		sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'tools.php?page=with-site-tools' ) ),
			esc_html__( 'Settings', 'with-site-tools' )
		)
	);

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( WITH_SITE_TOOLS_FILE ), 'with_site_tools_add_plugin_action_links' );

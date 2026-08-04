<?php
/**
 * Plugin Name:       With Site Tools
 * Description:       Reusable, opt-in site and block features for WordPress projects.
 * Version:           0.2.0
 * Requires at least: 7.0
 * Requires PHP:      8.0
 * Author:            Studio Leismann
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/studioleismann/with-site-tools
 * Text Domain:       with-site-tools
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

const WITH_SITE_TOOLS_VERSION         = '0.2.0';
const WITH_SITE_TOOLS_SETTINGS_OPTION = 'with_site_tools_feature_settings';
const WITH_SITE_TOOLS_FILE            = __FILE__;
const WITH_SITE_TOOLS_DIR             = __DIR__;

require_once WITH_SITE_TOOLS_DIR . '/includes/registry.php';
require_once WITH_SITE_TOOLS_DIR . '/includes/settings.php';
require_once WITH_SITE_TOOLS_DIR . '/includes/assets.php';
require_once WITH_SITE_TOOLS_DIR . '/includes/admin-page.php';
require_once WITH_SITE_TOOLS_DIR . '/includes/admin-bar.php';
require_once WITH_SITE_TOOLS_DIR . '/includes/github-updates.php';

/**
 * Discover and load feature entrypoints before WordPress registers Core blocks.
 *
 * @return void
 */
function with_site_tools_load_features(): void {
	$patterns      = array(
		WITH_SITE_TOOLS_DIR . '/src/blocks/*/*/*/index.php',
		WITH_SITE_TOOLS_DIR . '/src/media/*/index.php',
		WITH_SITE_TOOLS_DIR . '/src/plugins/*/*/index.php',
		WITH_SITE_TOOLS_DIR . '/src/site/*/index.php',
	);
	$feature_files = array();

	foreach ( $patterns as $pattern ) {
		$matches = glob( $pattern );

		if ( is_array( $matches ) ) {
			$feature_files = array_merge( $feature_files, $matches );
		}
	}

	sort( $feature_files, SORT_NATURAL | SORT_FLAG_CASE );

	foreach ( $feature_files as $feature_file ) {
		require_once $feature_file;
	}
}
add_action( 'init', 'with_site_tools_load_features', 0 );

<?php
/**
 * Updates from public GitHub releases.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Supply WordPress with the latest stable With Site Tools release.
 *
 * GitHub responses are cached network-wide because plugin updates are managed
 * at network level on multisite installations. Failed requests use a shorter
 * cache lifetime so temporary API errors recover without repeated requests.
 *
 * @param array<string, mixed>|false $update      Update data from earlier callbacks.
 * @param array<string, string>      $plugin_data Installed plugin headers.
 * @param string                     $plugin_file Installed plugin basename.
 * @return array<string, mixed>|false
 */
function with_site_tools_filter_github_update( $update, array $plugin_data, string $plugin_file ) {
	if ( plugin_basename( WITH_SITE_TOOLS_FILE ) !== $plugin_file ) {
		return $update;
	}

	$release = get_site_transient( 'with_site_tools_github_release' );

	if ( false === $release ) {
		$response = wp_remote_get(
			'https://api.github.com/repos/studioleismann/with-site-tools/releases/latest',
			array(
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'With-Site-Tools/' . WITH_SITE_TOOLS_VERSION,
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( 'with_site_tools_github_release', array( 'error' => true ), HOUR_IN_SECONDS );

			return $update;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $release ) ) {
			set_site_transient( 'with_site_tools_github_release', array( 'error' => true ), HOUR_IN_SECONDS );

			return $update;
		}

		set_site_transient( 'with_site_tools_github_release', $release, 12 * HOUR_IN_SECONDS );
	}

	if ( ! is_array( $release ) || isset( $release['error'] ) || empty( $release['tag_name'] ) ) {
		return $update;
	}

	$tag_name = sanitize_text_field( (string) $release['tag_name'] );
	$version  = str_starts_with( $tag_name, 'v' ) ? substr( $tag_name, 1 ) : $tag_name;

	if ( ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
		return $update;
	}

	$installed_version = $plugin_data['Version'] ?? WITH_SITE_TOOLS_VERSION;

	if ( ! version_compare( (string) $installed_version, $version, '<' ) ) {
		return $update;
	}

	$package = '';

	if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
		foreach ( $release['assets'] as $asset ) {
			if (
				is_array( $asset )
				&& 'with-site-tools.zip' === ( $asset['name'] ?? '' )
				&& ! empty( $asset['browser_download_url'] )
			) {
				$package = esc_url_raw( (string) $asset['browser_download_url'] );
				break;
			}
		}
	}

	if ( '' === $package ) {
		return $update;
	}

	return array(
		'id'           => $plugin_data['UpdateURI'] ?? 'https://github.com/studioleismann/with-site-tools',
		'slug'         => 'with-site-tools',
		'version'      => $version,
		'url'          => esc_url_raw( (string) ( $release['html_url'] ?? 'https://github.com/studioleismann/with-site-tools/releases' ) ),
		'package'      => $package,
		'tested'       => '7.0',
		'requires_php' => '8.0',
	);
}
add_filter( 'update_plugins_github.com', 'with_site_tools_filter_github_update', 10, 3 );

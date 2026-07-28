<?php
/**
 * Feature settings.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Get enabled feature settings.
 *
 * Missing feature keys are intentionally disabled. Plugin updates therefore
 * never activate new behavior on existing sites.
 *
 * @return array<string, bool>
 */
function with_site_tools_get_feature_settings(): array {
	$settings = get_option( WITH_SITE_TOOLS_SETTINGS_OPTION, array() );

	if ( ! is_array( $settings ) ) {
		return array();
	}

	return array_filter(
		$settings,
		static fn( $enabled ): bool => true === $enabled
	);
}

/**
 * Check whether a feature is enabled.
 *
 * @param string $feature_slug Feature slug.
 * @return bool
 */
function with_site_tools_is_feature_enabled( string $feature_slug ): bool {
	$settings = with_site_tools_get_feature_settings();

	return true === ( $settings[ $feature_slug ] ?? false );
}

/**
 * Sanitize settings against the current feature registry.
 *
 * Only explicit boolean true values are stored. Missing and false values remain
 * disabled without growing the option when new features are introduced.
 *
 * @param mixed $value Raw option value.
 * @return array<string, bool>
 */
function with_site_tools_sanitize_feature_settings( $value ): array {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$available = array_fill_keys( array_keys( with_site_tools_get_registered_features() ), true );
	$sanitized = array();

	foreach ( $value as $slug => $enabled ) {
		$slug = sanitize_text_field( (string) $slug );

		if (
			isset( $available[ $slug ] )
			&& with_site_tools_is_feature_available( $slug )
			&& true === rest_sanitize_boolean( $enabled )
		) {
			$sanitized[ $slug ] = true;
		}
	}

	ksort( $sanitized, SORT_NATURAL | SORT_FLAG_CASE );

	return $sanitized;
}

/**
 * Register the feature option with the WordPress Settings and REST APIs.
 *
 * @return void
 */
function with_site_tools_register_settings(): void {
	$properties = array();

	foreach ( array_keys( with_site_tools_get_registered_features() ) as $slug ) {
		$properties[ $slug ] = array(
			'type'    => 'boolean',
			'default' => false,
		);
	}

	register_setting(
		'with_site_tools',
		WITH_SITE_TOOLS_SETTINGS_OPTION,
		array(
			'type'              => 'object',
			'default'           => array(),
			'sanitize_callback' => 'with_site_tools_sanitize_feature_settings',
			'show_in_rest'      => array(
				'schema' => array(
					'type'                 => 'object',
					'properties'           => $properties,
					'additionalProperties' => false,
				),
			),
		)
	);

	$settings  = get_option( WITH_SITE_TOOLS_SETTINGS_OPTION, array() );
	$sanitized = with_site_tools_sanitize_feature_settings( $settings );

	if ( is_array( $settings ) && $settings !== $sanitized ) {
		update_option( WITH_SITE_TOOLS_SETTINGS_OPTION, $sanitized );
	}
}
add_action( 'init', 'with_site_tools_register_settings', 5 );

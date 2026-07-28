<?php
/**
 * Disable WordPress emoji assets.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Disable emoji assets', 'with-site-tools' ),
	__( 'Removes WordPress emoji scripts, styles, conversions, and related DNS hints.', 'with-site-tools' )
);

if ( ! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug ) ) {
	return;
}

/**
 * Remove WordPress emoji integrations.
 *
 * @return void
 */
function with_site_tools_disable_emojis(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'with_site_tools_disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', 'with_site_tools_remove_emoji_dns_prefetch', 10, 2 );
}
add_action( 'init', 'with_site_tools_disable_emojis' );

/**
 * Remove the TinyMCE emoji plugin.
 *
 * @param mixed $plugins TinyMCE plugins.
 * @return array<int, mixed>
 */
function with_site_tools_disable_emojis_tinymce( $plugins ): array {
	return is_array( $plugins ) ? array_values( array_diff( $plugins, array( 'wpemoji' ) ) ) : array();
}

/**
 * Remove the emoji CDN from DNS prefetch hints.
 *
 * @param mixed  $urls          Resource hint URLs.
 * @param string $relation_type Resource hint relationship.
 * @return array<int, mixed>
 */
function with_site_tools_remove_emoji_dns_prefetch( $urls, string $relation_type ): array {
	if ( ! is_array( $urls ) || 'dns-prefetch' !== $relation_type ) {
		return is_array( $urls ) ? $urls : array();
	}

	$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );

	return array_values( array_diff( $urls, array( $emoji_svg_url ) ) );
}

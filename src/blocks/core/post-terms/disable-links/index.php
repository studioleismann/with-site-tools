<?php
/**
 * Allow Post Terms blocks to render terms without links.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Optional Post Terms links', 'with-site-tools' ),
	__( 'Adds a block setting that can render taxonomy terms as plain text.', 'with-site-tools' )
);

if ( ! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug ) ) {
	return;
}

/**
 * Replace rendered term anchors with plain text spans when links are disabled.
 *
 * @param string $block_content Rendered block markup.
 * @param array  $block         Parsed block data.
 * @return string
 */
function with_site_tools_render_unlinked_post_terms( string $block_content, array $block ): string {
	$link_terms = $block['attrs']['withSiteToolsLinkTerms']
		?? $block['attrs']['withBaseLinkTerms']
		?? true;

	if ( false !== $link_terms ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() ) {
		return $block_content;
	}

	$processor->add_class( 'has-unlinked-terms' );
	$block_content = $processor->get_updated_html();
	$block_content = preg_replace_callback(
		'/<a\b[^>]*>(.*?)<\/a>/is',
		static fn( array $matches ): string => '<span class="wp-block-post-terms__term">' . $matches[1] . '</span>',
		$block_content
	);

	return is_string( $block_content ) ? $block_content : '';
}
add_filter( 'render_block_core/post-terms', 'with_site_tools_render_unlinked_post_terms', 10, 2 );

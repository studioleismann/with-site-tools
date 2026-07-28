<?php
/**
 * Add FAQ Schema.org microdata to Accordion blocks.
 *
 * @package WithSiteTools
 * @link    https://developer.wordpress.org/news/snippets/schema-org-microdata-for-accordion-block-faqs/
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Accordion FAQ schema', 'with-site-tools' ),
	__( 'Adds optional FAQPage, Question, and Answer microdata to Core Accordion blocks.', 'with-site-tools' )
);

if ( ! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug ) ) {
	return;
}

/**
 * Add FAQPage microdata to Accordion output carrying the is-faqs class.
 *
 * @param string $block_content Rendered Accordion markup.
 * @return string
 */
function with_site_tools_render_accordion_faq_schema( string $block_content ): string {
	$processor = new WP_HTML_Tag_Processor( $block_content );

	if (
		! $processor->next_tag( array( 'class_name' => 'wp-block-accordion' ) )
		|| ! $processor->has_class( 'is-faqs' )
	) {
		return $block_content;
	}

	$processor->set_attribute( 'itemscope', true );
	$processor->set_attribute( 'itemtype', 'https://schema.org/FAQPage' );

	while ( $processor->next_tag( array( 'class_name' => 'wp-block-accordion-item' ) ) ) {
		$processor->set_attribute( 'itemscope', true );
		$processor->set_attribute( 'itemprop', 'mainEntity' );
		$processor->set_attribute( 'itemtype', 'https://schema.org/Question' );

		if ( $processor->next_tag( array( 'class_name' => 'wp-block-accordion-heading__toggle-title' ) ) ) {
			$processor->set_attribute( 'itemprop', 'name' );
		}

		if ( $processor->next_tag( array( 'class_name' => 'wp-block-accordion-panel' ) ) ) {
			$processor->set_attribute( 'itemscope', true );
			$processor->set_attribute( 'itemprop', 'acceptedAnswer' );
			$processor->set_attribute( 'itemtype', 'https://schema.org/Answer' );

			if ( $processor->next_tag( 'p' ) ) {
				$processor->set_attribute( 'itemprop', 'text' );
			}
		}
	}

	return $processor->get_updated_html();
}
add_filter( 'render_block_core/accordion', 'with_site_tools_render_accordion_faq_schema' );

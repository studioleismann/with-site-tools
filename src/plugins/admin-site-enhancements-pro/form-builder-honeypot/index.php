<?php
/**
 * Add honeypot protection to Admin Site Enhancements Pro Form Builder.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Form Builder honeypot', 'with-site-tools' ),
	__( 'Adds a signed honeypot and timing check to Admin Site Enhancements Pro forms.', 'with-site-tools' )
);

if (
	! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug )
	|| ! with_site_tools_is_feature_available( $with_site_tools_feature_slug )
) {
	return;
}

const WITH_SITE_TOOLS_ASE_HONEYPOT_FIELD = 'with_site_tools_contact_reference';
const WITH_SITE_TOOLS_ASE_TIME_FIELD     = 'with_site_tools_form_rendered_at';
const WITH_SITE_TOOLS_ASE_TOKEN_FIELD    = 'with_site_tools_form_render_token';
const WITH_SITE_TOOLS_ASE_MIN_AGE        = 2;
const WITH_SITE_TOOLS_ASE_MAX_AGE        = 86400;

/**
 * Inject protection into the Form Builder shortcode.
 *
 * @param string $output Shortcode output.
 * @param string $tag    Shortcode tag.
 * @return string
 */
function with_site_tools_ase_inject_honeypot_shortcode( string $output, string $tag ): string {
	return 'formbuilder' === $tag ? with_site_tools_ase_inject_honeypot_markup( $output ) : $output;
}
add_filter( 'do_shortcode_tag', 'with_site_tools_ase_inject_honeypot_shortcode', 20, 2 );

/**
 * Inject protection into the Form Builder block.
 *
 * @param string $block_content Rendered block markup.
 * @param array  $block         Parsed block data.
 * @return string
 */
function with_site_tools_ase_inject_honeypot_block( string $block_content, array $block ): string {
	if ( 'form-builder/form-selector' !== ( $block['blockName'] ?? '' ) ) {
		return $block_content;
	}

	return with_site_tools_ase_inject_honeypot_markup( $block_content );
}
add_filter( 'render_block', 'with_site_tools_ase_inject_honeypot_block', 20, 2 );

/**
 * Insert signed honeypot fields before the submit button.
 *
 * @param string $markup Form markup.
 * @return string
 */
function with_site_tools_ase_inject_honeypot_markup( string $markup ): string {
	if (
		! str_contains( $markup, 'formbuilder-form' )
		|| ! str_contains( $markup, 'fb-submit-wrap' )
		|| str_contains( $markup, WITH_SITE_TOOLS_ASE_HONEYPOT_FIELD )
	) {
		return $markup;
	}

	$rendered_at = (string) time();
	$token       = wp_hash( $rendered_at . '|' . WITH_SITE_TOOLS_ASE_TIME_FIELD );
	$honeypot    = sprintf(
		'<div class="with-site-tools-form-honeypot" aria-hidden="true"><label>%1$s<input type="text" name="%2$s" value="" tabindex="-1" autocomplete="off" data-lpignore="true"></label></div><input type="hidden" name="%3$s" value="%4$s"><input type="hidden" name="%5$s" value="%6$s">',
		esc_html__( 'Leave this field empty', 'with-site-tools' ),
		esc_attr( WITH_SITE_TOOLS_ASE_HONEYPOT_FIELD ),
		esc_attr( WITH_SITE_TOOLS_ASE_TIME_FIELD ),
		esc_attr( $rendered_at ),
		esc_attr( WITH_SITE_TOOLS_ASE_TOKEN_FIELD ),
		esc_attr( $token )
	);
	$updated     = preg_replace( '/<div class="fb-submit-wrap\b/', $honeypot . '<div class="fb-submit-wrap', $markup, 1 );

	return is_string( $updated ) ? $updated : $markup;
}

/**
 * Reject invalid Form Builder submissions before entries or email are created.
 *
 * @return void
 */
function with_site_tools_ase_reject_honeypot_spam(): void {
	$failure_message = __( 'Your message could not be sent. Please refresh the page and try again.', 'with-site-tools' );

	// This public form is protected by its signed timestamp and honeypot fields.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( empty( $_POST['data'] ) ) {
		wp_send_json(
			array(
				'status'  => 'failed',
				'message' => $failure_message,
			)
		);
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$data = wp_unslash( $_POST['data'] );

	if ( ! is_string( $data ) || '' === $data ) {
		wp_send_json(
			array(
				'status'  => 'failed',
				'message' => $failure_message,
			)
		);
	}

	parse_str( htmlspecialchars_decode( $data ), $parsed_data );

	if (
		! isset( $parsed_data[ WITH_SITE_TOOLS_ASE_HONEYPOT_FIELD ] )
		|| '' !== trim( (string) $parsed_data[ WITH_SITE_TOOLS_ASE_HONEYPOT_FIELD ] )
		|| empty( $parsed_data[ WITH_SITE_TOOLS_ASE_TIME_FIELD ] )
		|| empty( $parsed_data[ WITH_SITE_TOOLS_ASE_TOKEN_FIELD ] )
	) {
		wp_send_json(
			array(
				'status'  => 'failed',
				'message' => $failure_message,
			)
		);
	}

	$rendered_at    = absint( $parsed_data[ WITH_SITE_TOOLS_ASE_TIME_FIELD ] );
	$token          = sanitize_text_field( (string) $parsed_data[ WITH_SITE_TOOLS_ASE_TOKEN_FIELD ] );
	$expected_token = wp_hash( (string) $rendered_at . '|' . WITH_SITE_TOOLS_ASE_TIME_FIELD );
	$age            = time() - $rendered_at;

	if (
		$rendered_at <= 0
		|| ! hash_equals( $expected_token, $token )
		|| $age < WITH_SITE_TOOLS_ASE_MIN_AGE
		|| $age > WITH_SITE_TOOLS_ASE_MAX_AGE
	) {
		wp_send_json(
			array(
				'status'  => 'failed',
				'message' => $failure_message,
			)
		);
	}
}
add_action( 'wp_ajax_formbuilder_process_entry', 'with_site_tools_ase_reject_honeypot_spam', 1 );
add_action( 'wp_ajax_nopriv_formbuilder_process_entry', 'with_site_tools_ase_reject_honeypot_spam', 1 );

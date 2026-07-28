/**
 * Post Terms link control.
 */

import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Register the persisted link setting on Core Post Terms.
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 * @return {Object} Block settings.
 */
function addPostTermsLinkAttribute( settings, name ) {
	if ( name !== 'core/post-terms' ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			withSiteToolsLinkTerms: {
				type: 'boolean',
				default: true,
			},
			withBaseLinkTerms: {
				type: 'boolean',
			},
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'with-site-tools/post-terms-link-attribute',
	addPostTermsLinkAttribute
);

const withPostTermsLinkControl = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( props.name !== 'core/post-terms' ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, setAttributes } = props;
		const linkTerms =
			attributes.withSiteToolsLinkTerms ??
			attributes.withBaseLinkTerms ??
			true;

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody title={ __( 'Site Tools', 'with-site-tools' ) }>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Link terms', 'with-site-tools' ) }
							checked={ linkTerms }
							onChange={ ( withSiteToolsLinkTerms ) =>
								setAttributes( {
									withSiteToolsLinkTerms,
									withBaseLinkTerms: undefined,
								} )
							}
						/>
					</PanelBody>
				</InspectorControls>
			</>
		);
	};
}, 'withPostTermsLinkControl' );

addFilter(
	'editor.BlockEdit',
	'with-site-tools/post-terms-link-control',
	withPostTermsLinkControl
);

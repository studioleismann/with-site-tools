/**
 * Accordion FAQ schema control.
 */

import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Register the FAQ setting on Core Accordion.
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 * @return {Object} Block settings.
 */
function addFaqAttribute( settings, name ) {
	if ( name !== 'core/accordion' ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			isFaqs: {
				type: 'boolean',
				default: false,
			},
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'with-site-tools/accordion-faq-attribute',
	addFaqAttribute
);

const withFaqControl = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( props.name !== 'core/accordion' ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, setAttributes } = props;
		const isFaqs =
			attributes.isFaqs ??
			( attributes.className || '' ).split( ' ' ).includes( 'is-faqs' );

		const updateFaqSetting = ( enabled ) => {
			const classes = ( attributes.className || '' )
				.split( ' ' )
				.filter(
					( className ) => className && className !== 'is-faqs'
				);

			if ( enabled ) {
				classes.push( 'is-faqs' );
			}

			setAttributes( {
				isFaqs: enabled,
				className: classes.join( ' ' ) || undefined,
			} );
		};

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody title={ __( 'Site Tools', 'with-site-tools' ) }>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Enable FAQ schema',
								'with-site-tools'
							) }
							help={ __(
								'Use this only when every accordion item is a question with an answer.',
								'with-site-tools'
							) }
							checked={ isFaqs }
							onChange={ updateFaqSetting }
						/>
					</PanelBody>
				</InspectorControls>
			</>
		);
	};
}, 'withFaqControl' );

addFilter(
	'editor.BlockEdit',
	'with-site-tools/accordion-faq-control',
	withFaqControl
);

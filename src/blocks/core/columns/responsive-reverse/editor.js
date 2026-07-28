/**
 * Responsive Columns order controls.
 */

import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

const ATTRIBUTE = 'withSiteToolsReverseColumnsOn';
const LEGACY_ATTRIBUTE = 'withBaseReverseColumnsOn';

/**
 * Register responsive order settings on Core Columns.
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 * @return {Object} Block settings.
 */
function addResponsiveOrderAttribute( settings, name ) {
	if ( name !== 'core/columns' ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			[ ATTRIBUTE ]: {
				type: 'object',
				default: {},
			},
			[ LEGACY_ATTRIBUTE ]: {
				type: 'object',
			},
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'with-site-tools/columns-responsive-order-attribute',
	addResponsiveOrderAttribute
);

const withResponsiveOrderControls = createHigherOrderComponent(
	( BlockEdit ) => {
		return ( props ) => {
			if ( props.name !== 'core/columns' ) {
				return <BlockEdit { ...props } />;
			}

			const values =
				props.attributes[ ATTRIBUTE ] ||
				props.attributes[ LEGACY_ATTRIBUTE ] ||
				{};
			const setViewport = ( viewport, enabled ) => {
				props.setAttributes( {
					[ ATTRIBUTE ]: {
						...values,
						[ viewport ]: enabled,
					},
					[ LEGACY_ATTRIBUTE ]: undefined,
				} );
			};

			return (
				<>
					<BlockEdit { ...props } />
					<InspectorControls>
						<PanelBody
							title={ __(
								'Responsive order',
								'with-site-tools'
							) }
						>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Reverse on mobile',
									'with-site-tools'
								) }
								checked={ !! values.mobile }
								onChange={ ( enabled ) =>
									setViewport( 'mobile', enabled )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Reverse on tablet',
									'with-site-tools'
								) }
								checked={ !! values.tablet }
								onChange={ ( enabled ) =>
									setViewport( 'tablet', enabled )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Reverse on desktop',
									'with-site-tools'
								) }
								checked={ !! values.desktop }
								onChange={ ( enabled ) =>
									setViewport( 'desktop', enabled )
								}
							/>
						</PanelBody>
					</InspectorControls>
				</>
			);
		};
	},
	'withResponsiveOrderControls'
);

addFilter(
	'editor.BlockEdit',
	'with-site-tools/columns-responsive-order-controls',
	withResponsiveOrderControls
);

const withResponsiveOrderPreview = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => {
			if ( props.name !== 'core/columns' ) {
				return <BlockListBlock { ...props } />;
			}

			const values =
				props.attributes[ ATTRIBUTE ] ||
				props.attributes[ LEGACY_ATTRIBUTE ] ||
				{};
			const classes = [ 'mobile', 'tablet', 'desktop' ]
				.filter( ( viewport ) => values[ viewport ] )
				.map( ( viewport ) => `has-reversed-columns-${ viewport }` );

			return (
				<BlockListBlock
					{ ...props }
					className={ [ props.className, ...classes ]
						.filter( Boolean )
						.join( ' ' ) }
				/>
			);
		};
	},
	'withResponsiveOrderPreview'
);

addFilter(
	'editor.BlockListBlock',
	'with-site-tools/columns-responsive-order-preview',
	withResponsiveOrderPreview
);

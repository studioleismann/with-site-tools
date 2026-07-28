/**
 * Group link controls.
 */

import { BlockControls, LinkControl } from '@wordpress/block-editor';
import { Popover, TextControl, ToolbarButton } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { link as linkIcon } from '@wordpress/icons';

const NEW_TAB_REL = [ 'noopener', 'noreferrer' ];

/**
 * Normalize rel tokens when the new-tab state changes.
 *
 * @param {string|undefined} rel           Current rel value.
 * @param {boolean}          opensInNewTab New-tab state.
 * @return {string|undefined} Normalized rel value.
 */
function normalizeRel( rel, opensInNewTab ) {
	const tokens = ( rel || '' )
		.split( ' ' )
		.map( ( token ) => token.trim() )
		.filter( Boolean );

	NEW_TAB_REL.forEach( ( token ) => {
		const index = tokens.indexOf( token );

		if ( opensInNewTab && index === -1 ) {
			tokens.push( token );
		} else if ( ! opensInNewTab && index !== -1 ) {
			tokens.splice( index, 1 );
		}
	} );

	return tokens.length ? tokens.join( ' ' ) : undefined;
}

/**
 * Register persisted link attributes on Core Group.
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 * @return {Object} Block settings.
 */
function addGroupLinkAttributes( settings, name ) {
	if ( name !== 'core/group' ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			withSiteToolsGroupLinkUrl: { type: 'string' },
			withSiteToolsGroupLinkTarget: { type: 'string' },
			withSiteToolsGroupLinkRel: { type: 'string' },
			withSiteToolsGroupLinkLabel: { type: 'string' },
			withBaseGroupLinkUrl: { type: 'string' },
			withBaseGroupLinkTarget: { type: 'string' },
			withBaseGroupLinkRel: { type: 'string' },
			withBaseGroupLinkLabel: { type: 'string' },
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'with-site-tools/group-link-attributes',
	addGroupLinkAttributes
);

const withGroupLinkControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( props.name !== 'core/group' ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, isSelected, setAttributes } = props;
		const groupLinkLabel =
			attributes.withSiteToolsGroupLinkLabel ??
			attributes.withBaseGroupLinkLabel;
		const groupLinkRel =
			attributes.withSiteToolsGroupLinkRel ??
			attributes.withBaseGroupLinkRel;
		const groupLinkTarget =
			attributes.withSiteToolsGroupLinkTarget ??
			attributes.withBaseGroupLinkTarget;
		const groupLinkUrl =
			attributes.withSiteToolsGroupLinkUrl ??
			attributes.withBaseGroupLinkUrl;
		const [ isOpen, setIsOpen ] = useState( false );
		const [ anchor, setAnchor ] = useState( null );
		const linkValue = useMemo(
			() => ( {
				url: groupLinkUrl,
				opensInNewTab: groupLinkTarget === '_blank',
				rel: groupLinkRel,
			} ),
			[ groupLinkRel, groupLinkTarget, groupLinkUrl ]
		);

		useEffect( () => {
			if ( ! isSelected ) {
				setIsOpen( false );
			}
		}, [ isSelected ] );

		const removeLink = () => {
			setAttributes( {
				withSiteToolsGroupLinkUrl: undefined,
				withSiteToolsGroupLinkTarget: undefined,
				withSiteToolsGroupLinkRel: undefined,
				withSiteToolsGroupLinkLabel: undefined,
				withBaseGroupLinkUrl: undefined,
				withBaseGroupLinkTarget: undefined,
				withBaseGroupLinkRel: undefined,
				withBaseGroupLinkLabel: undefined,
			} );
			setIsOpen( false );
		};

		return (
			<>
				<BlockEdit { ...props } />
				<BlockControls group="block">
					<ToolbarButton
						icon={ linkIcon }
						label={
							groupLinkUrl
								? __( 'Edit link', 'with-site-tools' )
								: __( 'Link', 'with-site-tools' )
						}
						isActive={ !! groupLinkUrl }
						isPressed={ isOpen || !! groupLinkUrl }
						onClick={ ( event ) => {
							setAnchor( event.currentTarget );
							setIsOpen( ( open ) => ! open );
						} }
					/>
				</BlockControls>
				{ isOpen && (
					<Popover
						anchor={ anchor }
						placement="bottom"
						onClose={ () => setIsOpen( false ) }
						shift
						__unstableSlotName="__unstable-block-tools-after"
					>
						<div
							role="dialog"
							aria-label={ __(
								'Edit Group link',
								'with-site-tools'
							) }
						>
							<LinkControl
								hasRichPreviews
								showInitialSuggestions
								forceIsEditingLink={ ! groupLinkUrl }
								value={ linkValue }
								onChange={ ( { url, opensInNewTab, rel } ) =>
									setAttributes( {
										withSiteToolsGroupLinkUrl:
											url || undefined,
										withSiteToolsGroupLinkTarget:
											opensInNewTab
												? '_blank'
												: undefined,
										withSiteToolsGroupLinkRel: normalizeRel(
											rel,
											!! opensInNewTab
										),
										withBaseGroupLinkUrl: undefined,
										withBaseGroupLinkTarget: undefined,
										withBaseGroupLinkRel: undefined,
									} )
								}
								onRemove={ removeLink }
								renderControlBottom={ () => (
									<TextControl
										__next40pxDefaultSize
										__nextHasNoMarginBottom
										className="block-editor-link-control__field block-editor-link-control__text-content"
										label={ __(
											'Accessible label',
											'with-site-tools'
										) }
										help={ __(
											'Optional. If empty, text from the Group is used.',
											'with-site-tools'
										) }
										value={ groupLinkLabel || '' }
										onChange={ ( value ) =>
											setAttributes( {
												withSiteToolsGroupLinkLabel:
													value.trim() || undefined,
												withBaseGroupLinkLabel:
													undefined,
											} )
										}
									/>
								) }
							/>
						</div>
					</Popover>
				) }
			</>
		);
	};
}, 'withGroupLinkControls' );

addFilter(
	'editor.BlockEdit',
	'with-site-tools/group-link-controls',
	withGroupLinkControls
);

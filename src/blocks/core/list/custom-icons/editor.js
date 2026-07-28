/**
 * Custom List icon picker.
 */

import { BlockControls } from '@wordpress/block-editor';
import {
	Button,
	Dashicon,
	Popover,
	ToolbarButton,
} from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEffect, useState } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

const CUSTOM_ICON_CLASS = 'has-custom-list-icon';
const LIST_STYLE_CLASSES = [
	'is-style-checkmark',
	'is-style-crossmark',
	CUSTOM_ICON_CLASS,
];
const ICONS = [
	{ name: 'yes', code: '\\f147', label: __( 'Yes', 'with-site-tools' ) },
	{ name: 'no', code: '\\f158', label: __( 'No', 'with-site-tools' ) },
	{ name: 'saved', code: '\\f15e', label: __( 'Saved', 'with-site-tools' ) },
	{
		name: 'star-filled',
		code: '\\f155',
		label: __( 'Star', 'with-site-tools' ),
	},
	{ name: 'heart', code: '\\f487', label: __( 'Heart', 'with-site-tools' ) },
	{
		name: 'warning',
		code: '\\f534',
		label: __( 'Warning', 'with-site-tools' ),
	},
	{ name: 'info', code: '\\f348', label: __( 'Info', 'with-site-tools' ) },
	{
		name: 'lightbulb',
		code: '\\f339',
		label: __( 'Lightbulb', 'with-site-tools' ),
	},
	{
		name: 'arrow-right-alt',
		code: '\\f344',
		label: __( 'Arrow right', 'with-site-tools' ),
	},
	{ name: 'plus', code: '\\f132', label: __( 'Plus', 'with-site-tools' ) },
	{ name: 'minus', code: '\\f460', label: __( 'Minus', 'with-site-tools' ) },
	{ name: 'flag', code: '\\f227', label: __( 'Flag', 'with-site-tools' ) },
	{
		name: 'marker',
		code: '\\f159',
		label: __( 'Marker', 'with-site-tools' ),
	},
	{
		name: 'admin-users',
		code: '\\f110',
		label: __( 'Users', 'with-site-tools' ),
	},
];
const ICON_CODES = Object.fromEntries(
	ICONS.map( ( { name, code } ) => [ name, code ] )
);

/**
 * Register the selected icon on Core List.
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 * @return {Object} Block settings.
 */
function addListIconAttribute( settings, name ) {
	if ( name !== 'core/list' ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			withSiteToolsListIcon: {
				type: 'string',
			},
			withBaseListIcon: {
				type: 'string',
			},
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'with-site-tools/list-icon-attribute',
	addListIconAttribute
);

const withListIconControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( props.name !== 'core/list' ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, isSelected, setAttributes } = props;
		const selectedIcon =
			attributes.withSiteToolsListIcon ?? attributes.withBaseListIcon;
		const [ isOpen, setIsOpen ] = useState( false );
		const [ anchor, setAnchor ] = useState( null );

		useEffect( () => {
			if ( ! isSelected ) {
				setIsOpen( false );
			}
		}, [ isSelected ] );

		const setIcon = ( iconName ) => {
			const classes = ( attributes.className || '' )
				.split( ' ' )
				.filter(
					( className ) =>
						className && ! LIST_STYLE_CLASSES.includes( className )
				);

			if ( iconName ) {
				classes.push( CUSTOM_ICON_CLASS );
			}

			setAttributes( {
				withSiteToolsListIcon: iconName || undefined,
				withBaseListIcon: undefined,
				className: classes.join( ' ' ) || undefined,
			} );
			setIsOpen( false );
		};

		return (
			<>
				<BlockEdit { ...props } />
				<BlockControls group="block">
					<ToolbarButton
						icon={
							<Dashicon icon={ selectedIcon || 'editor-ul' } />
						}
						label={ __( 'Choose list icon', 'with-site-tools' ) }
						isPressed={ isOpen || !! selectedIcon }
						onClick={ ( event ) => {
							setAnchor( event.currentTarget );
							setIsOpen( ( open ) => ! open );
						} }
					/>
				</BlockControls>
				{ isOpen && (
					<Popover
						anchor={ anchor }
						className="with-site-tools-list-icon-popover"
						placement="bottom-start"
						onClose={ () => setIsOpen( false ) }
						shift
						__unstableSlotName="__unstable-block-tools-after"
					>
						<div className="with-site-tools-list-icon-picker">
							<div className="with-site-tools-list-icon-picker__grid">
								{ ICONS.map( ( icon ) => (
									<Button
										key={ icon.name }
										label={ icon.label }
										icon={ <Dashicon icon={ icon.name } /> }
										isPressed={ selectedIcon === icon.name }
										onClick={ () => setIcon( icon.name ) }
									/>
								) ) }
							</div>
							<Button
								variant="secondary"
								onClick={ () => setIcon( '' ) }
							>
								{ __( 'Clear', 'with-site-tools' ) }
							</Button>
						</div>
					</Popover>
				) }
			</>
		);
	};
}, 'withListIconControls' );

addFilter(
	'editor.BlockEdit',
	'with-site-tools/list-icon-controls',
	withListIconControls
);

const withListIconPreview = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {
		const iconName =
			props.attributes?.withSiteToolsListIcon ??
			props.attributes?.withBaseListIcon;
		const iconCode = ICON_CODES[ iconName ];

		if ( props.name !== 'core/list' || ! iconCode ) {
			return <BlockListBlock { ...props } />;
		}

		return (
			<BlockListBlock
				{ ...props }
				wrapperProps={ {
					...props.wrapperProps,
					style: {
						...props.wrapperProps?.style,
						'--with-site-tools-list-icon': `"${ iconCode }"`,
					},
				} }
			/>
		);
	};
}, 'withListIconPreview' );

addFilter(
	'editor.BlockListBlock',
	'with-site-tools/list-icon-preview',
	withListIconPreview
);

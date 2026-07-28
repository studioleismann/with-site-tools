/**
 * Site Tools settings application.
 */

import apiFetch from '@wordpress/api-fetch';
import {
	Card,
	CardBody,
	Notice,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import domReady from '@wordpress/dom-ready';
import { createRoot, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

const DataViews = window.wp.dataviews?.DataViews;
const config = window.withSiteToolsAdmin || {
	features: [],
	optionName: 'with_site_tools_feature_settings',
};

const groupDescriptions = {
	blocks: __(
		'Extend Core blocks with reusable editor controls and rendering behavior.',
		'with-site-tools'
	),
	media: __(
		'Control reusable media behavior independently from the active theme.',
		'with-site-tools'
	),
	plugins: __(
		'Enable integrations only when their required plugin is active.',
		'with-site-tools'
	),
	site: __(
		'Manage site-wide behavior that should survive a theme change.',
		'with-site-tools'
	),
};

/**
 * Render a feature's availability-aware toggle.
 *
 * @param {Object}   props          Component properties.
 * @param {Object}   props.feature  Feature data.
 * @param {boolean}  props.isSaving Whether a setting is being saved.
 * @param {Function} props.onToggle Feature toggle callback.
 * @return {Element} Feature toggle.
 */
function FeatureToggle( { feature, isSaving, onToggle } ) {
	const unavailable = feature.plugin && ! feature.available;
	let label = __( 'Disabled', 'with-site-tools' );

	if ( unavailable ) {
		label = __( 'Unavailable', 'with-site-tools' );
	} else if ( feature.enabled ) {
		label = __( 'Enabled', 'with-site-tools' );
	}

	return (
		<ToggleControl
			__nextHasNoMarginBottom
			label={ label }
			help={
				unavailable
					? sprintf(
							/* translators: %s: plugin directory. */
							__(
								'Requires the active plugin “%s”.',
								'with-site-tools'
							),
							feature.plugin
					  )
					: undefined
			}
			checked={ unavailable ? false : feature.enabled }
			disabled={ isSaving || unavailable }
			onChange={ ( enabled ) => onToggle( feature.slug, enabled ) }
		/>
	);
}

/**
 * Render the Core Card fallback used when DataViews is unavailable.
 *
 * @param {Object}   props          Component properties.
 * @param {Array}    props.features Features in this group.
 * @param {boolean}  props.isSaving Whether a setting is being saved.
 * @param {Function} props.onToggle Feature toggle callback.
 * @return {Element} Feature cards.
 */
function FeatureCards( { features, isSaving, onToggle } ) {
	return (
		<div className="with-site-tools-feature-list">
			{ features.map( ( feature ) => (
				<Card className="with-site-tools-card" key={ feature.slug }>
					<CardBody>
						<div className="with-site-tools-card-content">
							<div className="with-site-tools-feature">
								<strong>{ feature.label }</strong>
								{ feature.context && (
									<small>{ feature.context }</small>
								) }
								<p>{ feature.description }</p>
							</div>
							<FeatureToggle
								feature={ feature }
								isSaving={ isSaving }
								onToggle={ onToggle }
							/>
						</div>
					</CardBody>
				</Card>
			) ) }
		</div>
	);
}

/**
 * Render one automatically grouped feature table.
 *
 * @param {Object}   props          Component properties.
 * @param {Array}    props.features Features in this group.
 * @param {boolean}  props.isSaving Whether a setting is being saved.
 * @param {Function} props.onToggle Feature toggle callback.
 * @return {Element} Feature table.
 */
function FeatureTable( { features, isSaving, onToggle } ) {
	const fields = useMemo(
		() => [
			{
				id: 'label',
				label: __( 'Feature', 'with-site-tools' ),
				getValue: ( { item } ) => item.label,
				render: ( { item } ) => (
					<div className="with-site-tools-feature">
						<strong>{ item.label }</strong>
						{ item.context && <small>{ item.context }</small> }
						<p>{ item.description }</p>
					</div>
				),
			},
			{
				id: 'status',
				label: __( 'Status', 'with-site-tools' ),
				getValue: ( { item } ) => {
					if ( item.plugin && ! item.available ) {
						return __( 'Unavailable', 'with-site-tools' );
					}

					return item.enabled
						? __( 'Enabled', 'with-site-tools' )
						: __( 'Disabled', 'with-site-tools' );
				},
				render: ( { item } ) => (
					<FeatureToggle
						feature={ item }
						isSaving={ isSaving }
						onToggle={ onToggle }
					/>
				),
			},
		],
		[ isSaving, onToggle ]
	);
	const [ view, setView ] = useState( {
		type: 'table',
		fields: [ 'label', 'status' ],
		perPage: features.length,
		page: 1,
	} );

	if ( ! DataViews ) {
		return (
			<FeatureCards
				features={ features }
				isSaving={ isSaving }
				onToggle={ onToggle }
			/>
		);
	}

	return (
		<DataViews
			actions={ [] }
			data={ features }
			fields={ fields }
			getItemId={ ( item ) => item.slug }
			isLoading={ isSaving }
			onChangeView={ setView }
			paginationInfo={ {
				totalItems: features.length,
				totalPages: 1,
			} }
			view={ view }
		/>
	);
}

/**
 * Render the Site Tools feature settings.
 *
 * @return {Element} Settings application.
 */
function SiteToolsAdmin() {
	const [ features, setFeatures ] = useState( config.features );
	const [ savingSlug, setSavingSlug ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const groups = useMemo(
		() =>
			features.reduce( ( groupedFeatures, feature ) => {
				if ( ! groupedFeatures[ feature.groupSlug ] ) {
					groupedFeatures[ feature.groupSlug ] = {
						title: feature.group,
						features: [],
					};
				}

				groupedFeatures[ feature.groupSlug ].features.push( feature );

				return groupedFeatures;
			}, {} ),
		[ features ]
	);

	const updateFeature = async ( slug, enabled ) => {
		const previousFeatures = features;
		const nextFeatures = features.map( ( feature ) =>
			feature.slug === slug ? { ...feature, enabled } : feature
		);
		const settings = nextFeatures.reduce( ( enabledFeatures, feature ) => {
			if ( feature.enabled && feature.available ) {
				enabledFeatures[ feature.slug ] = true;
			}

			return enabledFeatures;
		}, {} );

		setFeatures( nextFeatures );
		setSavingSlug( slug );
		setNotice( null );

		try {
			await apiFetch( {
				path: '/wp/v2/settings',
				method: 'POST',
				data: {
					[ config.optionName ]: settings,
				},
			} );
			setNotice( {
				status: 'success',
				message: __( 'Site Tools settings saved.', 'with-site-tools' ),
			} );
		} catch ( error ) {
			setFeatures( previousFeatures );
			setNotice( {
				status: 'error',
				message:
					error?.message ||
					__( 'The settings could not be saved.', 'with-site-tools' ),
			} );
		} finally {
			setSavingSlug( '' );
		}
	};

	return (
		<>
			<div className="with-site-tools-page-header">
				<div>
					<h1>{ __( 'Site Tools', 'with-site-tools' ) }</h1>
					<p className="with-site-tools-page-intro">
						{ __(
							'Manage reusable site and block features independently from the active theme. Changes are saved automatically.',
							'with-site-tools'
						) }
					</p>
				</div>
				{ savingSlug && <Spinner /> }
			</div>

			{ notice && (
				<Notice
					status={ notice.status }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<div className="with-site-tools-sections">
				{ Object.entries( groups ).map( ( [ slug, group ] ) => (
					<section className="with-site-tools-section" key={ slug }>
						<div className="with-site-tools-section__header">
							<h2>{ group.title }</h2>
							<p>{ groupDescriptions[ slug ] || '' }</p>
						</div>
						<FeatureTable
							features={ group.features }
							isSaving={ !! savingSlug }
							onToggle={ updateFeature }
						/>
					</section>
				) ) }
			</div>

			{ features.length === 0 && (
				<Notice status="info" isDismissible={ false }>
					{ __( 'No features are registered.', 'with-site-tools' ) }
				</Notice>
			) }
		</>
	);
}

domReady( () => {
	const root = document.getElementById( 'with-site-tools-admin' );

	if ( root ) {
		createRoot( root ).render( <SiteToolsAdmin /> );
	}
} );

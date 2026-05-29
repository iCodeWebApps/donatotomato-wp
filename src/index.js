import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	RangeControl,
	Placeholder,
	ExternalLink,
	Notice,
	Button,
	Spinner,
} from '@wordpress/components';
import { useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import widgetMetadata from '../block.json';
import buttonMetadata from '../block-button.json';

// Catches the most common typo case for campaign IDs without a backend
// roundtrip: paste-cut accidents, accidentally pasting a tenant ID instead,
// numbers transposed, etc. UUIDs (any version) match this — the loose
// hex-dash pattern accepts v4 + v7 + future variants.
const CAMPAIGN_ID_PATTERN = /^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/;

// Default config injected by class-admin.php via wp_add_inline_script on the
// `wp-blocks` handle. window.donatotomatoBlockEditor is undefined if running
// before that inline script has loaded — handle gracefully.
const getEditorConfig = () =>
	( typeof window !== 'undefined' && window.donatotomatoBlockEditor ) || {};

const getDefaultSlug   = () => getEditorConfig().defaultSlug   || '';
const getAppUrl        = () => getEditorConfig().appUrl        || 'https://app.donatotomato.com';
const getSettingsUrl   = () => getEditorConfig().settingsUrl   || '';
const getSignupUrl     = () => getEditorConfig().signupUrl     || 'https://app.donatotomato.com/auth';
const getCampaignsUrl  = () => getEditorConfig().campaignsUrl  || 'https://app.donatotomato.com/campaigns';

function statusLabel( status ) {
	if ( 'active' === status ) return __( 'Active', 'donatotomato' );
	if ( 'draft' === status )  return __( 'Draft', 'donatotomato' );
	if ( 'paused' === status ) return __( 'Paused', 'donatotomato' );
	return status;
}

/**
 * Shared campaign picker for both block inspectors. Fetches via the existing
 * REST proxy (/wp-json/donatotomato/v1/campaigns) using @wordpress/api-fetch
 * so the WP-REST nonce is attached automatically — no direct fetch() to
 * external URLs from the editor.
 *
 * Props:
 *   value         — current campaignId attribute (string UUID or '')
 *   onChange      — setter; called with the selected UUID (or '')
 *   effectiveSlug — slug used for empty-state check (block override OR default)
 */
function CampaignPicker( { value, onChange, effectiveSlug } ) {
	const [ campaigns, setCampaigns ]       = useState( null ); // null = unloaded, [] = loaded-but-empty
	const [ status, setStatus ]             = useState( 'idle' ); // idle | loading | refreshing | error | tenant_not_found
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const [ showAdvanced, setShowAdvanced ] = useState( false );

	const hasSlug = !! effectiveSlug;

	const loadCampaigns = useCallback( ( opts = {} ) => {
		if ( ! hasSlug ) {
			return;
		}
		setStatus( opts.refresh ? 'refreshing' : 'loading' );
		setErrorMessage( '' );
		const path = '/donatotomato/v1/campaigns' + ( opts.refresh ? '?refresh=1' : '' );

		apiFetch( { path } )
			.then( ( response ) => {
				const list = ( response && Array.isArray( response.campaigns ) ) ? response.campaigns : [];
				setCampaigns( list );
				setStatus( 'idle' );
			} )
			.catch( ( err ) => {
				const data = err && err.data ? err.data : null;
				if ( data && 'tenant_not_found' === data.error ) {
					setStatus( 'tenant_not_found' );
				} else if ( data && 'missing_slug' === data.error ) {
					setStatus( 'error' );
					setErrorMessage( __( 'Set your Organization Slug in Settings → DonatoTomato first.', 'donatotomato' ) );
				} else {
					setStatus( 'error' );
					setErrorMessage(
						( err && err.message ) ||
							__( 'Could not reach DonatoTomato. Try again in a minute.', 'donatotomato' )
					);
				}
				setCampaigns( [] );
			} );
	}, [ hasSlug ] );

	useEffect( () => {
		if ( hasSlug ) {
			loadCampaigns();
		}
	}, [ hasSlug, loadCampaigns ] );

	// Empty-state: no slug configured. Block the picker entirely.
	if ( ! hasSlug ) {
		return (
			<>
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Set your Organization Slug in Settings → DonatoTomato first.',
						'donatotomato'
					) }
					{ getSettingsUrl() && (
						<>
							{ ' ' }
							<ExternalLink href={ getSettingsUrl() }>
								{ __( 'Open settings', 'donatotomato' ) }
							</ExternalLink>
						</>
					) }
				</Notice>
				<TextControl
					label={ __( 'Campaign ID', 'donatotomato' ) }
					value={ value }
					onChange={ onChange }
					disabled
					help={ __( 'Manual entry is disabled until your Organization Slug is set.', 'donatotomato' ) }
				/>
			</>
		);
	}

	const savedExists = !! ( campaigns && campaigns.find( ( c ) => c.id === value ) );

	// Build the SelectControl options. Saved-but-missing UUID is preserved at
	// the top so an already-pasted UUID doesn't silently get cleared when the
	// upstream campaign was deleted.
	const options = [
		{ value: '', label: __( 'Select a campaign…', 'donatotomato' ) },
	];
	if ( value && ! savedExists && campaigns ) {
		options.push( {
			value,
			label: sprintf(
				/* translators: %s: saved campaign UUID that no longer exists upstream */
				__( '%s (no longer available)', 'donatotomato' ),
				value
			),
		} );
	}
	if ( campaigns && campaigns.length ) {
		campaigns.forEach( ( c ) => {
			options.push( {
				value: c.id,
				label: c.name + ' (' + statusLabel( c.status ) + ')',
			} );
		} );
	}

	const campaignIdMalformed = !! ( value && ! CAMPAIGN_ID_PATTERN.test( value ) );
	const isBusy = 'loading' === status || 'refreshing' === status;

	return (
		<>
			<div style={ { marginBottom: '8px' } }>
				<SelectControl
					label={ __( 'Campaign', 'donatotomato' ) }
					value={ value || '' }
					options={ options }
					onChange={ onChange }
					disabled={ isBusy }
					help={
						'loading' === status
							? __( 'Loading campaigns…', 'donatotomato' )
							: 'refreshing' === status
								? __( 'Refreshing…', 'donatotomato' )
								: __( 'Pick a campaign from your DonatoTomato dashboard.', 'donatotomato' )
					}
				/>
				<div style={ { display: 'flex', alignItems: 'center', gap: '8px', marginTop: '4px' } }>
					<Button
						variant="secondary"
						isSmall
						onClick={ () => loadCampaigns( { refresh: true } ) }
						disabled={ isBusy }
					>
						{ __( 'Refresh', 'donatotomato' ) }
					</Button>
					{ isBusy && <Spinner /> }
				</div>
			</div>

			{ 'tenant_not_found' === status && (
				<Notice status="error" isDismissible={ false }>
					{ sprintf(
						/* translators: %s: organization slug configured in plugin settings */
						__( 'We can\'t find a DonatoTomato account for "%s".', 'donatotomato' ),
						effectiveSlug
					) }
					{ ' ' }
					<ExternalLink href={ getSignupUrl() }>
						{ __( 'Sign up free at donatotomato.com', 'donatotomato' ) }
					</ExternalLink>
				</Notice>
			) }

			{ 'error' === status && (
				<Notice status="error" isDismissible={ false }>
					{ errorMessage || __( 'Could not load campaigns. Try Refresh.', 'donatotomato' ) }
				</Notice>
			) }

			{ 'idle' === status && campaigns && campaigns.length === 0 && (
				<Notice status="warning" isDismissible={ false }>
					{ sprintf(
						/* translators: %s: organization slug configured in plugin settings */
						__( 'No campaigns found for "%s".', 'donatotomato' ),
						effectiveSlug
					) }
					{ ' ' }
					<ExternalLink href={ getCampaignsUrl() }>
						{ __( 'Open DonatoTomato dashboard', 'donatotomato' ) }
					</ExternalLink>
				</Notice>
			) }

			{ 'idle' === status && value && ! savedExists && campaigns && campaigns.length > 0 && (
				<Notice status="warning" isDismissible={ false }>
					{ __( 'Your saved campaign no longer exists — please pick another.', 'donatotomato' ) }
				</Notice>
			) }

			<details
				className="donatotomato-block-advanced"
				open={ showAdvanced }
				onToggle={ ( e ) => setShowAdvanced( e.target.open ) }
				style={ { marginTop: '8px' } }
			>
				<summary style={ { cursor: 'pointer', fontSize: '12px', color: '#1e1e1e' } }>
					{ __( 'Advanced', 'donatotomato' ) }
				</summary>
				<div style={ { marginTop: '8px' } }>
					<TextControl
						label={ __( 'Campaign ID (manual)', 'donatotomato' ) }
						value={ value }
						onChange={ onChange }
						help={ __(
							'Paste a campaign UUID directly if you know it. Found on the Campaign Detail page in your DonatoTomato dashboard.',
							'donatotomato'
						) }
					/>
					{ campaignIdMalformed && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'Campaign ID looks malformed. It should be a UUID like 1234abcd-5678-90ef-1234-567890abcdef.',
								'donatotomato'
							) }
						</Notice>
					) }
				</div>
			</details>
		</>
	);
}

// ─── Widget block: inline iframe embed ──────────────────────────────────────
registerBlockType( widgetMetadata.name, {
	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps();
		const { campaignId, orgSlug, width, height } = attributes;
		const effectiveSlug = orgSlug || getDefaultSlug();
		const configured = !! ( effectiveSlug && campaignId );
		const campaignIdMalformed = !! ( campaignId && ! CAMPAIGN_ID_PATTERN.test( campaignId ) );
		const previewUrl = configured && ! campaignIdMalformed
			? `${ getAppUrl() }/widget/${ encodeURIComponent( effectiveSlug ) }/${ encodeURIComponent( campaignId ) }`
			: null;

		const instructions = configured
			? __(
					'Widget configured. The live donation form renders on the published page — preview it there.',
					'donatotomato'
			  )
			: __(
					'Pick a campaign in the block settings panel on the right.',
					'donatotomato'
			  );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'DonatoTomato Settings', 'donatotomato' ) }>
						<CampaignPicker
							value={ campaignId }
							onChange={ ( v ) => setAttributes( { campaignId: v } ) }
							effectiveSlug={ effectiveSlug }
						/>
						<TextControl
							label={ __( 'Organization Slug (override)', 'donatotomato' ) }
							value={ orgSlug }
							onChange={ ( v ) => setAttributes( { orgSlug: v } ) }
							help={ __( 'Leave blank to use the slug from Settings → DonatoTomato. Set only if this widget should load a different org\'s campaign.', 'donatotomato' ) }
						/>
						{ previewUrl && (
							<p style={ { marginTop: '12px' } }>
								<ExternalLink href={ previewUrl }>
									{ __( 'View live preview', 'donatotomato' ) }
								</ExternalLink>
								<span style={ { display: 'block', fontSize: '12px', color: '#757575', marginTop: '4px' } }>
									{ __(
										'Opens the widget at the configured slug + campaign in a new tab. Confirm it loads cleanly before publishing.',
										'donatotomato'
									) }
								</span>
							</p>
						) }
						<RangeControl
							label={ __( 'Width (px)', 'donatotomato' ) }
							value={ width }
							onChange={ ( v ) => setAttributes( { width: v } ) }
							min={ 300 }
							max={ 1200 }
						/>
						<RangeControl
							label={ __( 'Height (px)', 'donatotomato' ) }
							value={ height }
							onChange={ ( v ) => setAttributes( { height: v } ) }
							min={ 400 }
							max={ 1200 }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<Placeholder
						icon="heart"
						label={ __( 'DonatoTomato Widget', 'donatotomato' ) }
						instructions={ instructions }
					>
						{ configured && (
							<div style={ { fontSize: '13px', lineHeight: 1.6 } }>
								<div>
									<strong>{ __( 'Organization:', 'donatotomato' ) }</strong>{ ' ' }
									<code>{ effectiveSlug }</code>
								</div>
								<div>
									<strong>{ __( 'Campaign:', 'donatotomato' ) }</strong>{ ' ' }
									<code>{ campaignId }</code>
								</div>
								<div>
									<strong>{ __( 'Size:', 'donatotomato' ) }</strong>{ ' ' }
									{ sprintf(
										/* translators: 1: width in pixels, 2: height in pixels */
										__( '%1$d × %2$d px', 'donatotomato' ),
										width,
										height
									) }
								</div>
							</div>
						) }
					</Placeholder>
				</div>
			</>
		);
	},
	save() {
		return null;
	},
} );

// ─── Button block: pop-up modal trigger ─────────────────────────────────────
registerBlockType( buttonMetadata.name, {
	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps();
		const { campaignId, orgSlug, label } = attributes;
		const effectiveSlug = orgSlug || getDefaultSlug();
		const configured = !! campaignId;
		const displayLabel = label || __( 'Donate', 'donatotomato' );
		const campaignIdMalformed = !! ( campaignId && ! CAMPAIGN_ID_PATTERN.test( campaignId ) );
		const previewUrl = configured && effectiveSlug && ! campaignIdMalformed
			? `${ getAppUrl() }/widget/${ encodeURIComponent( effectiveSlug ) }/${ encodeURIComponent( campaignId ) }?modal=1`
			: null;

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'DonatoTomato Button Settings', 'donatotomato' ) }>
						<CampaignPicker
							value={ campaignId }
							onChange={ ( v ) => setAttributes( { campaignId: v } ) }
							effectiveSlug={ effectiveSlug }
						/>
						<TextControl
							label={ __( 'Button Label', 'donatotomato' ) }
							value={ label }
							onChange={ ( v ) => setAttributes( { label: v } ) }
							help={ __( 'Text shown on the button. Defaults to "Donate".', 'donatotomato' ) }
						/>
						<TextControl
							label={ __( 'Organization Slug (override)', 'donatotomato' ) }
							value={ orgSlug }
							onChange={ ( v ) => setAttributes( { orgSlug: v } ) }
							help={ __( 'Leave blank to use the slug from Settings → DonatoTomato. Set only if this button should open a different org\'s campaign.', 'donatotomato' ) }
						/>
						{ previewUrl && (
							<p style={ { marginTop: '12px' } }>
								<ExternalLink href={ previewUrl }>
									{ __( 'View live preview', 'donatotomato' ) }
								</ExternalLink>
								<span style={ { display: 'block', fontSize: '12px', color: '#757575', marginTop: '4px' } }>
									{ __(
										'Opens the donation form in modal mode at the configured slug + campaign in a new tab.',
										'donatotomato'
									) }
								</span>
							</p>
						) }
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					{ configured ? (
						<button
							type="button"
							className="donatotomato-button"
							onClick={ ( e ) => e.preventDefault() }
						>
							{ displayLabel }
						</button>
					) : (
						<Placeholder
							icon="heart"
							label={ __( 'DonatoTomato Donate Button', 'donatotomato' ) }
							instructions={ __(
								'Pick a campaign in the block settings panel on the right. The button opens a donation pop-up when clicked on the published page.',
								'donatotomato'
							) }
						/>
					) }
				</div>
			</>
		);
	},
	save() {
		return null;
	},
} );

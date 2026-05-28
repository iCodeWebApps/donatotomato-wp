import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, Placeholder, ExternalLink, Notice } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import widgetMetadata from '../block.json';
import buttonMetadata from '../block-button.json';

// Catches the most common typo case for campaign IDs without a backend
// roundtrip: paste-cut accidents, accidentally pasting a tenant ID instead,
// numbers transposed, etc. UUIDs (any version) match this — the loose
// hex-dash pattern accepts v4 + v7 + future variants.
const CAMPAIGN_ID_PATTERN = /^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/;

// Default org slug from the plugin's Settings page, exposed by class-admin.php
// via wp_add_inline_script so we can fall back when a block's per-instance
// orgSlug is empty. (window.donatotomatoBlockEditor is undefined if running
// before that inline script has been loaded — handle gracefully.)
const getDefaultSlug = () =>
	( typeof window !== 'undefined' && window.donatotomatoBlockEditor && window.donatotomatoBlockEditor.defaultSlug ) || '';

const getAppUrl = () =>
	( typeof window !== 'undefined' && window.donatotomatoBlockEditor && window.donatotomatoBlockEditor.appUrl ) || 'https://app.donatotomato.com';

// ─── Widget block: inline iframe embed (existing) ─────────────────────────────
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
					'Enter your Organization Slug and Campaign ID in the block settings panel on the right.',
					'donatotomato'
			  );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'DonatoTomato Settings', 'donatotomato' ) }>
						<TextControl
							label={ __( 'Organization Slug', 'donatotomato' ) }
							value={ orgSlug }
							onChange={ ( v ) => setAttributes( { orgSlug: v } ) }
							help={ __( 'Found in your DonatoTomato dashboard under Settings > Embed Code.', 'donatotomato' ) }
						/>
						<TextControl
							label={ __( 'Campaign ID', 'donatotomato' ) }
							value={ campaignId }
							onChange={ ( v ) => setAttributes( { campaignId: v } ) }
							help={ __( 'Found on the Campaign Detail page in your DonatoTomato dashboard.', 'donatotomato' ) }
						/>
						{ campaignIdMalformed && (
							<Notice status="warning" isDismissible={ false }>
								{ __(
									'Campaign ID looks malformed. It should be a UUID like 1234abcd-5678-90ef-1234-567890abcdef. Double-check the value copied from your DonatoTomato dashboard.',
									'donatotomato'
								) }
							</Notice>
						) }
						{ previewUrl && (
							<p style={ { marginTop: '12px' } }>
								<ExternalLink href={ previewUrl }>
									{ __( 'View live preview', 'donatotomato' ) }
								</ExternalLink>
								<span style={ { display: 'block', fontSize: '12px', color: '#757575', marginTop: '4px' } }>
									{ __(
										'Opens the widget at the configured slug + campaign in a new tab. Confirm it loads cleanly before publishing — if the live preview shows a "Campaign not found" error, your slug and campaign ID don\'t match.',
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
									<code>{ orgSlug }</code>
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

// ─── Button block: pop-up modal trigger (new in 1.2.0) ────────────────────────
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
						<TextControl
							label={ __( 'Campaign ID', 'donatotomato' ) }
							value={ campaignId }
							onChange={ ( v ) => setAttributes( { campaignId: v } ) }
							help={ __( 'Found on the Campaign Detail page in your DonatoTomato dashboard.', 'donatotomato' ) }
						/>
						{ campaignIdMalformed && (
							<Notice status="warning" isDismissible={ false }>
								{ __(
									'Campaign ID looks malformed. It should be a UUID like 1234abcd-5678-90ef-1234-567890abcdef. Double-check the value copied from your DonatoTomato dashboard.',
									'donatotomato'
								) }
							</Notice>
						) }
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
										'Opens the donation form in modal mode at the configured slug + campaign in a new tab. Confirm it loads cleanly before publishing — if the preview shows a "Campaign not found" error, your slug and campaign ID don\'t match.',
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
								'Enter a Campaign ID in the block settings panel on the right. The button opens a donation pop-up when clicked on the published page.',
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

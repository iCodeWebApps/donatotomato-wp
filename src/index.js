import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from '../block.json';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps();
		const { campaignId, orgSlug, width, height } = attributes;

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
					{ orgSlug && campaignId ? (
						<div style={ { maxWidth: width, overflow: 'hidden', borderRadius: 12 } }>
							<iframe
								src={ `https://app.donatotomato.com/widget/${ encodeURIComponent( orgSlug ) }/${ encodeURIComponent( campaignId ) }?source=wordpress` }
								width={ width }
								height={ height }
								style={ { border: 0, display: 'block' } }
								title={ __( 'DonatoTomato Widget Preview', 'donatotomato' ) }
							/>
						</div>
					) : (
						<Placeholder
							icon="heart"
							label={ __( 'DonatoTomato Widget', 'donatotomato' ) }
							instructions={ __( 'Enter your Organization Slug and Campaign ID in the block settings panel on the right.', 'donatotomato' ) }
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

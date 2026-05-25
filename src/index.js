import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, Placeholder } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import metadata from '../block.json';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps();
		const { campaignId, orgSlug, width, height } = attributes;
		const configured = !! ( orgSlug && campaignId );

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

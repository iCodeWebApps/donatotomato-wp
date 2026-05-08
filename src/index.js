import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, Spinner } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import metadata from '../block.json';

const API_BASE = 'https://api.donatotomato.com/functions/v1';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const { campaignId, orgSlug, width, height } = attributes;
        const blockProps = useBlockProps();

        const [ siteSlug, setSiteSlug ] = useState( '' );
        const [ campaigns, setCampaigns ] = useState( null ); // null = not fetched yet
        const [ loadingCampaigns, setLoadingCampaigns ] = useState( false );
        const [ campaignError, setCampaignError ] = useState( '' );

        // Fetch the site-wide org slug from WP settings on mount
        useEffect( () => {
            apiFetch( { path: '/wp/v2/settings' } )
                .then( ( settings ) => setSiteSlug( settings.donatotomato_org_slug || '' ) )
                .catch( () => {} );
        }, [] );

        const effectiveSlug = orgSlug || siteSlug;

        // Fetch campaigns whenever the effective slug changes
        useEffect( () => {
            if ( ! effectiveSlug ) {
                setCampaigns( null );
                return;
            }
            setLoadingCampaigns( true );
            setCampaignError( '' );
            fetch( `${ API_BASE }/get-public-campaigns?slug=${ encodeURIComponent( effectiveSlug ) }` )
                .then( ( r ) => r.json() )
                .then( ( data ) => {
                    if ( Array.isArray( data ) ) {
                        setCampaigns( data );
                    } else {
                        setCampaignError( data.error || 'Could not load campaigns.' );
                        setCampaigns( [] );
                    }
                } )
                .catch( () => {
                    setCampaignError( 'Could not reach DonatoTomato.' );
                    setCampaigns( [] );
                } )
                .finally( () => setLoadingCampaigns( false ) );
        }, [ effectiveSlug ] );

        const campaignOptions = [
            { label: '— Select a campaign —', value: '' },
            ...( campaigns || [] ).map( ( c ) => ( { label: c.name, value: c.id } ) ),
        ];

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Widget Settings" initialOpen={ true }>
                        <TextControl
                            label="Org Slug (optional)"
                            help="Leave blank to use the site-wide slug from Settings → DonatoTomato."
                            value={ orgSlug }
                            onChange={ ( val ) => setAttributes( { orgSlug: val } ) }
                        />
                        { ! effectiveSlug && (
                            <p style={ { color: '#b91c1c', fontSize: '12px' } }>
                                Set an org slug above or in Settings → DonatoTomato.
                            </p>
                        ) }
                        { effectiveSlug && loadingCampaigns && <Spinner /> }
                        { effectiveSlug && ! loadingCampaigns && campaignError && (
                            <p style={ { color: '#b91c1c', fontSize: '12px' } }>{ campaignError }</p>
                        ) }
                        { effectiveSlug && ! loadingCampaigns && ! campaignError && campaigns !== null && (
                            <SelectControl
                                label="Campaign"
                                value={ campaignId }
                                options={ campaignOptions }
                                onChange={ ( val ) => setAttributes( { campaignId: val } ) }
                            />
                        ) }
                        { /* Fallback manual entry if API unavailable */ }
                        { ( ! effectiveSlug || ( campaigns !== null && campaignError ) ) && (
                            <TextControl
                                label="Campaign ID (manual)"
                                help="Found in your DonatoTomato dashboard."
                                value={ campaignId }
                                onChange={ ( val ) => setAttributes( { campaignId: val } ) }
                            />
                        ) }
                        <TextControl
                            label="Width (px)"
                            value={ String( width ) }
                            onChange={ ( val ) => setAttributes( { width: parseInt( val, 10 ) || 480 } ) }
                        />
                        <TextControl
                            label="Height (px)"
                            help="Leave at 600 — the widget auto-resizes to fit."
                            value={ String( height ) }
                            onChange={ ( val ) => setAttributes( { height: parseInt( val, 10 ) || 600 } ) }
                        />
                    </PanelBody>
                </InspectorControls>
                <div { ...blockProps }>
                    { campaignId ? (
                        <p className="donatotomato-editor-preview">
                            ✓ <strong>DonatoTomato widget configured.</strong> The donation form will appear here on the published page.
                        </p>
                    ) : (
                        <p className="donatotomato-editor-empty">
                            <strong>DonatoTomato</strong> — Select a campaign in the settings panel →
                        </p>
                    ) }
                </div>
            </>
        );
    },

    save: () => null,
} );

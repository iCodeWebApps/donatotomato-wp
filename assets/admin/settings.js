/* global jQuery, wp, donatotomatoAdmin */
( function ( $ ) {
    'use strict';

    if ( typeof donatotomatoAdmin === 'undefined' ) {
        return;
    }

    var s = donatotomatoAdmin.strings;

    function $on( root ) {
        return $( root );
    }

    // --- Color picker -------------------------------------------------------
    if ( $.fn.wpColorPicker ) {
        $( '.donatotomato-color-picker' ).wpColorPicker( {
            change: function () {
                // Defer to next tick so the input value reflects the new color.
                window.setTimeout( renderPreview, 0 );
            },
            clear: function () {
                window.setTimeout( renderPreview, 0 );
            },
        } );
    }

    // --- Segmented control (radio) ------------------------------------------
    function syncSegmentedActive() {
        $( '.donatotomato-segmented' ).each( function () {
            var $group = $( this );
            $group.find( '.donatotomato-segmented__option' ).removeClass( 'is-active' );
            $group.find( 'input:checked' ).closest( '.donatotomato-segmented__option' ).addClass( 'is-active' );
        } );
    }
    $( document ).on( 'change', '.donatotomato-segmented input[type="radio"]', function () {
        syncSegmentedActive();
        renderPreview();
    } );
    syncSegmentedActive();

    // --- Offset slider readout ---------------------------------------------
    $( document ).on( 'input change', '.donatotomato-offset-input', function () {
        $( '.donatotomato-offset-value' ).text( this.value + 'px' );
        renderPreview();
    } );

    // --- Label chips --------------------------------------------------------
    $( document ).on( 'click', '.donatotomato-label-chip', function () {
        var label = $( this ).data( 'label' );
        $( '#donatotomato_floating_label' ).val( label ).trigger( 'input' );
    } );
    $( document ).on( 'input', '#donatotomato_floating_label', renderPreview );

    // --- Other live-preview triggers ---------------------------------------
    $( document ).on( 'change', '[name="donatotomato_floating_show_heart"]', renderPreview );
    $( document ).on( 'change input', '[name="donatotomato_floating_color"]', renderPreview );

    // --- Campaign picker ----------------------------------------------------
    var $select   = $( '.donatotomato-campaign-select' );
    var $status   = $( '.donatotomato-picker-status' );
    var $refresh  = $( '.donatotomato-refresh-campaigns' );
    var hasSlug   = !! ( donatotomatoAdmin.orgSlug && donatotomatoAdmin.orgSlug.length );
    var primaryColorFromApi = '';

    function setStatus( message, level ) {
        $status.removeClass( 'is-error is-warning' );
        if ( level ) {
            $status.addClass( 'is-' + level );
        }
        $status.html( message || '' );
    }

    function escapeHtml( str ) {
        return String( str ).replace( /[&<>"']/g, function ( c ) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
        } );
    }

    function statusLabel( status ) {
        if ( 'active' === status ) return s.statusActive;
        if ( 'draft' === status )  return s.statusDraft;
        if ( 'paused' === status ) return s.statusPaused;
        return status;
    }

    function format( tmpl, value ) {
        return tmpl.replace( '%s', escapeHtml( value ) );
    }

    function loadCampaigns( opts ) {
        opts = opts || {};
        if ( ! hasSlug ) {
            setStatus( s.missingSlug + ' <a href="' + escapeHtml( donatotomatoAdmin.generalTabUrl ) + '">' + escapeHtml( s.missingSlugCta ) + '</a>', 'warning' );
            $select.prop( 'disabled', true );
            return;
        }

        setStatus( opts.refresh ? s.refreshing : s.loading );
        $select.prop( 'disabled', true );

        var url = donatotomatoAdmin.restRoot + '/campaigns';
        if ( opts.refresh ) {
            url += '?refresh=1';
        }

        $.ajax( {
            url: url,
            method: 'GET',
            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', donatotomatoAdmin.nonce );
            },
        } ).done( function ( response ) {
            renderCampaigns( response );
        } ).fail( function ( jqXHR ) {
            var resp = jqXHR.responseJSON;
            if ( resp && 'tenant_not_found' === resp.error ) {
                setStatus(
                    format( s.tenantNotFound, donatotomatoAdmin.orgSlug ) +
                        ' <a href="' + escapeHtml( donatotomatoAdmin.signupUrl ) + '" target="_blank" rel="noopener">' + escapeHtml( s.tenantNotFoundCta ) + '</a>',
                    'error'
                );
            } else if ( resp && 'missing_slug' === resp.error ) {
                setStatus( s.missingSlug, 'warning' );
            } else {
                setStatus( s.upstreamError, 'error' );
            }
            $select.prop( 'disabled', false );
        } );
    }

    function renderCampaigns( response ) {
        var campaigns = ( response && response.campaigns ) || [];
        var saved     = $select.attr( 'data-saved' ) || donatotomatoAdmin.savedCampaign || '';

        if ( ! campaigns.length ) {
            setStatus(
                format( s.noCampaigns, donatotomatoAdmin.orgSlug ) +
                    ' <a href="' + escapeHtml( donatotomatoAdmin.campaignsUrl ) + '" target="_blank" rel="noopener">' + escapeHtml( s.noCampaignsCta ) + '</a>',
                'warning'
            );
            $select.empty().append( $( '<option/>', { value: '', text: s.pickCampaign } ) );
            $select.prop( 'disabled', false );
            return;
        }

        primaryColorFromApi = campaigns[ 0 ].primary_color || '';

        $select.empty();
        $select.append( $( '<option/>', { value: '', text: s.pickCampaign } ) );

        var savedExists = false;
        campaigns.forEach( function ( c ) {
            var statusBadge = ' (' + statusLabel( c.status ) + ')';
            var $opt = $( '<option/>', {
                value: c.id,
                text:  c.name + statusBadge,
            } );
            if ( c.id === saved ) {
                $opt.attr( 'selected', 'selected' );
                savedExists = true;
            }
            $opt.attr( 'data-primary-color', c.primary_color || '' );
            $select.append( $opt );
        } );

        if ( saved && ! savedExists ) {
            // Saved campaign no longer exists upstream — surface a warning
            // but keep the value so we don't silently drop the configured
            // floating button.
            setStatus( s.staleCampaign, 'warning' );
            $select.prepend( $( '<option/>', {
                value:    saved,
                text:     saved + ' (' + s.staleCampaign + ')',
                selected: 'selected',
            } ) );
        } else {
            setStatus( '' );
        }

        $select.prop( 'disabled', false );
        renderPreview();
    }

    $refresh.on( 'click', function ( e ) {
        e.preventDefault();
        loadCampaigns( { refresh: true } );
    } );

    $select.on( 'change', function () {
        $select.attr( 'data-saved', $select.val() );
        renderPreview();
    } );

    // --- Live preview rendering --------------------------------------------
    function readCheckedRadio( name ) {
        var $el = $( '[name="' + name + '"]:checked' );
        return $el.length ? $el.val() : null;
    }

    function contrastTextColor( hex ) {
        hex = String( hex || '' ).replace( '#', '' );
        if ( hex.length === 3 ) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        if ( hex.length !== 6 ) return '#ffffff';
        var r = parseInt( hex.slice( 0, 2 ), 16 );
        var g = parseInt( hex.slice( 2, 4 ), 16 );
        var b = parseInt( hex.slice( 4, 6 ), 16 );
        var luma = ( 0.299 * r + 0.587 * g + 0.114 * b ) / 255;
        return luma > 0.6 ? '#111111' : '#ffffff';
    }

    function renderPreview() {
        var $preview = $( '.donatotomato-preview-button' );
        if ( ! $preview.length ) return;

        var size     = readCheckedRadio( 'donatotomato_floating_size' )     || 'medium';
        var shape    = readCheckedRadio( 'donatotomato_floating_shape' )    || 'pill';
        var position = readCheckedRadio( 'donatotomato_floating_position' ) || 'bottom-right';
        var offset   = parseInt( $( '.donatotomato-offset-input' ).val(), 10 ) || 24;
        var label    = $( '#donatotomato_floating_label' ).val() || s.donateDefault;
        var color    = $( '[name="donatotomato_floating_color"]' ).val() || primaryColorFromApi || '#10b981';
        var heart    = $( '[name="donatotomato_floating_show_heart"]' ).is( ':checked' );

        var padMap = { small: '10px 18px', medium: '14px 28px', large: '18px 36px' };
        var fontMap = { small: '13px',  medium: '15px',  large: '17px' };
        var radMap = { pill: '9999px', rounded: '8px', sharp: '0' };

        $preview.css( {
            padding: padMap[ size ],
            'font-size': fontMap[ size ],
            'border-radius': radMap[ shape ],
            background: color,
            color: contrastTextColor( color ),
            top: 'auto', bottom: 'auto', left: 'auto', right: 'auto',
        } );

        var o = Math.min( 48, Math.max( 12, offset ) );
        // Preview frame is 360x220 vs typical 1280x800 viewport; scale offset for visual parity.
        var scaled = Math.round( o * 0.7 ) + 'px';
        if ( position === 'bottom-right' ) { $preview.css( { bottom: scaled, right: scaled } ); }
        if ( position === 'bottom-left' )  { $preview.css( { bottom: scaled, left:  scaled } ); }
        if ( position === 'top-right' )    { $preview.css( { top:    scaled, right: scaled } ); }
        if ( position === 'top-left' )     { $preview.css( { top:    scaled, left:  scaled } ); }

        $preview.find( '.donatotomato-preview-button__label' ).text( label );
        $preview.toggleClass( 'has-heart', heart );
    }

    // --- Boot ---------------------------------------------------------------
    if ( hasSlug ) {
        loadCampaigns();
    } else {
        setStatus( s.missingSlug + ' <a href="' + escapeHtml( donatotomatoAdmin.generalTabUrl ) + '">' + escapeHtml( s.missingSlugCta ) + '</a>', 'warning' );
        $select.prop( 'disabled', true );
    }
    renderPreview();

} )( jQuery );

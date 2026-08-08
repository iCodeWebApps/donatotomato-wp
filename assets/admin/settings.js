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

    // Shared GET against the plugin's admin-only REST proxy. Used by both the
    // Floating Donate Button picker and the Shortcode Builder picker.
    function requestCampaigns( refresh ) {
        var url = donatotomatoAdmin.restRoot + '/campaigns';
        if ( refresh ) {
            url += '?refresh=1';
        }
        return $.ajax( {
            url: url,
            method: 'GET',
            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', donatotomatoAdmin.nonce );
            },
        } );
    }

    function missingSlugMessage() {
        return s.missingSlug + ' <a href="' + escapeHtml( donatotomatoAdmin.generalTabUrl ) + '">' + escapeHtml( s.missingSlugCta ) + '</a>';
    }

    function noCampaignsMessage() {
        return format( s.noCampaigns, donatotomatoAdmin.orgSlug ) +
            ' <a href="' + escapeHtml( donatotomatoAdmin.campaignsUrl ) + '" target="_blank" rel="noopener">' + escapeHtml( s.noCampaignsCta ) + '</a>';
    }

    function pickerFailureMessage( jqXHR ) {
        var resp = jqXHR.responseJSON;
        if ( resp && 'tenant_not_found' === resp.error ) {
            return {
                html: format( s.tenantNotFound, donatotomatoAdmin.orgSlug ) +
                    ' <a href="' + escapeHtml( donatotomatoAdmin.signupUrl ) + '" target="_blank" rel="noopener">' + escapeHtml( s.tenantNotFoundCta ) + '</a>',
                level: 'error',
            };
        }
        if ( resp && 'missing_slug' === resp.error ) {
            return { html: s.missingSlug, level: 'warning' };
        }
        return { html: s.upstreamError, level: 'error' };
    }

    function loadCampaigns( opts ) {
        opts = opts || {};
        if ( ! hasSlug ) {
            setStatus( missingSlugMessage(), 'warning' );
            $select.prop( 'disabled', true );
            return;
        }

        setStatus( opts.refresh ? s.refreshing : s.loading );
        $select.prop( 'disabled', true );

        requestCampaigns( opts.refresh ).done( function ( response ) {
            renderCampaigns( response );
        } ).fail( function ( jqXHR ) {
            var m = pickerFailureMessage( jqXHR );
            setStatus( m.html, m.level );
            $select.prop( 'disabled', false );
        } );
    }

    function renderCampaigns( response ) {
        var campaigns = ( response && response.campaigns ) || [];
        var saved     = $select.attr( 'data-saved' ) || donatotomatoAdmin.savedCampaign || '';

        if ( ! campaigns.length ) {
            setStatus( noCampaignsMessage(), 'warning' );
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

    // --- Shortcode builder ---------------------------------------------------
    // Client-side only: pick a campaign (same REST proxy as above), assemble a
    // [donatotomato] / [donatotomato_button] shortcode, copy it. Nothing is
    // saved server-side. Elements exist only on the Shortcode Builder tab.
    var $bSelect        = $( '.donatotomato-builder-select' );
    var $bRefresh       = $( '.donatotomato-builder-refresh' );
    var $bStatus        = $( '.donatotomato-builder-status' );
    var $bManualWrap    = $( '.donatotomato-builder-manual-wrap' );
    var $bManual        = $( '.donatotomato-builder-manual' );
    var $bManualWarning = $( '.donatotomato-builder-manual-warning' );
    var $bOutput        = $( '.donatotomato-builder-output' );
    var $bCopy          = $( '.donatotomato-builder-copy' );
    var $bCopyStatus    = $( '.donatotomato-builder-copy-status' );
    var $bTypeHelp      = $( '.donatotomato-builder-type-help' );

    // Loose UUID shape (any version) — same check the block editor uses to
    // catch paste-cut accidents in manually entered campaign IDs.
    var UUID_PATTERN = /^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/;

    function setBuilderStatus( message, level ) {
        $bStatus.removeClass( 'is-error is-warning' );
        if ( level ) {
            $bStatus.addClass( 'is-' + level );
        }
        $bStatus.html( message || '' );
    }

    function builderType() {
        var $checked = $( '[name="donatotomato_builder_type"]:checked' );
        return $checked.length ? $checked.val() : 'inline';
    }

    // Shortcode attribute values are double-quoted; strip characters that
    // would break out of the attribute or the shortcode itself.
    function cleanAttr( value ) {
        return String( value || '' ).replace( /["'\[\]]/g, '' ).trim();
    }

    function builderCampaign() {
        var manual = cleanAttr( $bManual.val() );
        if ( manual ) {
            return manual;
        }
        return cleanAttr( $bSelect.val() );
    }

    function composeShortcode() {
        // "Let donors choose" replaces the campaign entirely: the form opens on
        // a list of the org's active campaigns. No campaign is needed, and the
        // picker section of the UI is hidden while it is on.
        var choose   = $( '.donatotomato-builder-choose' ).is( ':checked' );
        var campaign = choose ? '' : builderCampaign();
        if ( ! choose && ! campaign ) {
            $bOutput.val( '' );
            $bCopy.prop( 'disabled', true );
            return;
        }

        // A group narrows the donor's list to one label. It only means anything
        // while the donor is choosing, so a leftover value in the box cannot
        // leak into a single-campaign shortcode after the checkbox is cleared.
        var group = choose ? cleanAttr( $( '.donatotomato-builder-group' ).val() ) : '';

        var shortcode;
        if ( 'button' === builderType() ) {
            shortcode = choose
                ? '[donatotomato_button choose="yes"'
                : '[donatotomato_button campaign="' + campaign + '"';
            if ( group ) {
                shortcode += ' group="' + group + '"';
            }
            var label = cleanAttr( $( '.donatotomato-builder-label' ).val() );
            if ( label ) {
                shortcode += ' label="' + label + '"';
            }
            shortcode += ']';
        } else {
            shortcode = choose
                ? '[donatotomato choose="yes"'
                : '[donatotomato campaign="' + campaign + '"';
            if ( group ) {
                shortcode += ' group="' + group + '"';
            }
            var width  = parseInt( $( '.donatotomato-builder-width' ).val(), 10 );
            var height = parseInt( $( '.donatotomato-builder-height' ).val(), 10 );
            // Only emit size attributes when they differ from the shortcode's
            // own defaults — keeps the common case short and readable.
            if ( width && 480 !== width ) {
                shortcode += ' width="' + width + '"';
            }
            if ( height && 600 !== height ) {
                shortcode += ' height="' + height + '"';
            }
            shortcode += ']';
        }

        $bOutput.val( shortcode );
        $bCopy.prop( 'disabled', false );
    }

    function syncBuilderRows() {
        var type = builderType();
        $( '.donatotomato-builder-row--inline' ).toggle( 'inline' === type );
        $( '.donatotomato-builder-row--button' ).toggle( 'button' === type );
        $bTypeHelp.text( $bTypeHelp.attr( 'button' === type ? 'data-button' : 'data-inline' ) || '' );
        // Picking a single campaign is meaningless while the donor is choosing,
        // so hide that whole section rather than leave a control that does
        // nothing. Width/height and label still apply and stay visible.
        var choose = $( '.donatotomato-builder-choose' ).is( ':checked' );
        $( '.donatotomato-builder-campaign-section' ).toggle( ! choose );
        // The group box narrows a list that only exists while donors choose.
        $( '.donatotomato-builder-group-wrap' ).toggle( choose );
    }

    // Offer the groups this organization has already used, so the name is typed
    // once and picked from then on. The campaigns endpoint does not carry the
    // group yet (DT-296); reading it defensively means the datalist starts
    // working the day it does, with no change here.
    function renderBuilderGroups( campaigns ) {
        var seen   = {};
        var groups = [];
        ( campaigns || [] ).forEach( function ( c ) {
            var g = c && c.picker_group ? String( c.picker_group ).trim() : '';
            if ( g && ! Object.prototype.hasOwnProperty.call( seen, g.toLowerCase() ) ) {
                seen[ g.toLowerCase() ] = true;
                groups.push( g );
            }
        } );

        var $list = $( '#donatotomato-builder-groups' );
        if ( ! $list.length ) {
            return;
        }
        $list.empty();
        groups.sort().forEach( function ( g ) {
            $list.append( $( '<option/>', { value: g } ) );
        } );
    }

    function renderBuilderCampaigns( response ) {
        var campaigns = ( response && response.campaigns ) || [];
        var previous  = $bSelect.val() || '';

        renderBuilderGroups( campaigns );

        if ( ! campaigns.length ) {
            setBuilderStatus( noCampaignsMessage(), 'warning' );
            $bSelect.empty().append( $( '<option/>', { value: '', text: s.pickCampaign } ) );
            $bSelect.prop( 'disabled', false );
            composeShortcode();
            return;
        }

        $bSelect.empty();
        $bSelect.append( $( '<option/>', { value: '', text: s.pickCampaign } ) );
        campaigns.forEach( function ( c ) {
            $bSelect.append( $( '<option/>', {
                value: c.id,
                text:  c.name + ' (' + statusLabel( c.status ) + ')',
            } ) );
        } );
        if ( previous ) {
            // Keep the user's selection across a Refresh when it still exists.
            $bSelect.val( previous );
            if ( $bSelect.val() !== previous ) {
                $bSelect.val( '' );
            }
        }

        setBuilderStatus( '' );
        $bSelect.prop( 'disabled', false );
        composeShortcode();
    }

    function loadBuilderCampaigns( opts ) {
        opts = opts || {};
        if ( ! hasSlug ) {
            setBuilderStatus(
                escapeHtml( s.missingSlugBuilder ) + ' <a href="' + escapeHtml( donatotomatoAdmin.generalTabUrl ) + '">' + escapeHtml( s.missingSlugCta ) + '</a>',
                'warning'
            );
            $bSelect.prop( 'disabled', true );
            return;
        }

        setBuilderStatus( opts.refresh ? s.refreshing : s.loading );
        $bSelect.prop( 'disabled', true );

        requestCampaigns( opts.refresh ).done( function ( response ) {
            renderBuilderCampaigns( response );
        } ).fail( function ( jqXHR ) {
            var m = pickerFailureMessage( jqXHR );
            setBuilderStatus( m.html, m.level );
            $bSelect.prop( 'disabled', false );
            // The picker is unavailable — surface the manual-entry fallback so
            // the user can still finish building their shortcode.
            $bManualWrap.attr( 'open', 'open' );
        } );
    }

    var copyResetTimer = null;
    function flashCopyButton( text ) {
        var original = s.copyShortcode;
        $bCopy.text( text );
        if ( copyResetTimer ) {
            window.clearTimeout( copyResetTimer );
        }
        copyResetTimer = window.setTimeout( function () {
            $bCopy.text( original );
        }, 2000 );
    }

    function copyBuilderShortcode() {
        var shortcode = $bOutput.val();
        if ( ! shortcode ) {
            return;
        }
        $bCopyStatus.text( '' );
        if ( navigator.clipboard && navigator.clipboard.writeText ) {
            navigator.clipboard.writeText( shortcode ).then( function () {
                flashCopyButton( s.copied );
            }, function () {
                legacyCopy( shortcode );
            } );
        } else {
            legacyCopy( shortcode );
        }
    }

    function legacyCopy( shortcode ) {
        // execCommand fallback for non-secure contexts (plain-http admin).
        var input = $bOutput.get( 0 );
        input.focus();
        input.select();
        var ok = false;
        try {
            ok = document.execCommand( 'copy' );
        } catch ( err ) {
            ok = false;
        }
        if ( ok ) {
            flashCopyButton( s.copied );
        } else {
            // Leave the text selected so a manual Ctrl+C finishes the job.
            $bCopyStatus.text( s.copyManually );
        }
    }

    if ( $bSelect.length ) {
        $bRefresh.on( 'click', function ( e ) {
            e.preventDefault();
            loadBuilderCampaigns( { refresh: true } );
        } );

        $bSelect.on( 'change', composeShortcode );

        $bManual.on( 'input', function () {
            var manual = cleanAttr( $bManual.val() );
            $bManualWarning.removeClass( 'is-warning' ).text( '' );
            if ( manual && ! UUID_PATTERN.test( manual ) ) {
                $bManualWarning.addClass( 'is-warning' ).text( s.malformedId );
            }
            composeShortcode();
        } );

        $( document ).on( 'change', '.donatotomato-builder-choose', function () {
            syncBuilderRows();
            composeShortcode();
        } );

        $( document ).on( 'change', '[name="donatotomato_builder_type"]', function () {
            syncBuilderRows();
            composeShortcode();
        } );

        $( document ).on( 'input change', '.donatotomato-builder-width, .donatotomato-builder-height, .donatotomato-builder-label, .donatotomato-builder-group', composeShortcode );

        $bCopy.on( 'click', function ( e ) {
            e.preventDefault();
            copyBuilderShortcode();
        } );

        syncBuilderRows();
        composeShortcode();
        loadBuilderCampaigns();
    }

    // --- Boot ---------------------------------------------------------------
    // The same script loads on every tab of the settings page; only boot the
    // floating-button picker when its tab (and its select) is rendered.
    if ( $select.length ) {
        if ( hasSlug ) {
            loadCampaigns();
        } else {
            setStatus( missingSlugMessage(), 'warning' );
            $select.prop( 'disabled', true );
        }
    }
    renderPreview();

} )( jQuery );

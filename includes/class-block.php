<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Block {

    public function __construct() {
        add_action( 'init', [ $this, 'register' ] );
    }

    public function register() {
        $build_dir = DONATOTOMATO_PLUGIN_DIR . 'build/index.js';
        if ( ! file_exists( $build_dir ) ) {
            return;
        }

        register_block_type( DONATOTOMATO_PLUGIN_DIR . 'block.json', [
            'render_callback' => [ $this, 'render' ],
        ] );
    }

    public function render( $attributes ) {
        $slug     = ! empty( $attributes['orgSlug'] ) ? sanitize_text_field( $attributes['orgSlug'] ) : get_option( 'donatotomato_org_slug', '' );
        $campaign = isset( $attributes['campaignId'] ) ? sanitize_text_field( $attributes['campaignId'] ) : '';
        $width    = isset( $attributes['width'] ) ? absint( $attributes['width'] ) : 480;
        $height   = isset( $attributes['height'] ) ? absint( $attributes['height'] ) : 600;

        if ( empty( $slug ) || empty( $campaign ) ) {
            return '';
        }

        wp_enqueue_style(
            'donatotomato',
            DONATOTOMATO_PLUGIN_URL . 'assets/css/donatotomato.css',
            [],
            DONATOTOMATO_VERSION
        );
        wp_enqueue_script(
            'donatotomato-resize',
            DONATOTOMATO_PLUGIN_URL . 'assets/js/resize.js',
            [],
            DONATOTOMATO_VERSION,
            true
        );

        return donatotomato_render_iframe( $slug, $campaign, $width, $height );
    }
}

/**
 * Shortcode attributes are strings, and people write booleans a dozen ways.
 * Accept the forms a nonprofit admin plausibly types: choose="yes", "true",
 * "1", "on".
 *
 * A BARE attribute (`[donatotomato choose]`) is deliberately NOT truthy. WordPress
 * parses bare words into numerically-indexed atts, so `choose` never arrives
 * under its own key — it is indistinguishable here from the attribute being
 * absent, which must stay falsy. `[donatotomato choose]` therefore falls through
 * to the shortcode's "add a campaign, or choose=yes" error, which tells the
 * author exactly what to write. Verified against WP 6.9 in the Test Lab.
 */
function donatotomato_is_truthy_att( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }
    $v = strtolower( trim( (string) $value ) );
    return in_array( $v, [ 'yes', 'true', '1', 'on' ], true );
}

/**
 * Render the donation iframe.
 *
 * An EMPTY $campaign is meaningful, not missing: it points at the campaign-less
 * widget route, which lets the donor choose a destination first. Orgs that give
 * per missionary, program or fund use that so one embed covers every fund
 * instead of one embed per fund. Callers are responsible for deciding that an
 * empty campaign was intended — the shortcodes require an explicit choose="yes".
 */
function donatotomato_render_iframe( $slug, $campaign, $width = 480, $height = 600 ) {
    $path = '/widget/' . rawurlencode( $slug );
    if ( '' !== $campaign ) {
        $path .= '/' . rawurlencode( $campaign );
    }
    $src = esc_url( DONATOTOMATO_APP_URL . $path . '?source=wordpress' );

    return sprintf(
        '<div class="donatotomato-wrapper" style="max-width:%dpx;">' .
        '<iframe src="%s" width="%d" height="%d" title="%s" frameborder="0" allow="payment" loading="lazy"></iframe>' .
        '</div>',
        $width,
        $src,
        $width,
        $height,
        esc_attr__( 'Donation form', 'donatotomato' )
    );
}

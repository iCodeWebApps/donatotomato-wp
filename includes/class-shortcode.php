<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Shortcode {

    public function __construct() {
        add_shortcode( 'donatotomato', [ $this, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets() {
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
    }

    public function render( $atts ) {
        $atts = shortcode_atts( [
            'slug'     => get_option( 'donatotomato_org_slug', '' ),
            'campaign' => '',
            'choose'   => '',
            'width'    => 480,
            'height'   => 600,
        ], $atts, 'donatotomato' );

        $slug     = sanitize_text_field( $atts['slug'] );
        $campaign = sanitize_text_field( $atts['campaign'] );
        $width    = absint( $atts['width'] ) ?: 480;
        $height   = absint( $atts['height'] ) ?: 600;

        // choose="yes" means "let the donor pick the destination" — the form
        // opens on a list of the org's active campaigns instead of one fund.
        // It is an EXPLICIT opt-in rather than "campaign is empty", because an
        // empty campaign has always been a mistake worth reporting: silently
        // showing a chooser to someone who mistyped the attribute would trade a
        // clear error for a confusing page.
        $choose = donatotomato_is_truthy_att( $atts['choose'] );

        if ( empty( $slug ) ) {
            return '<p style="color:#b91c1c;">' . sprintf(
                /* translators: %s: link to DonatoTomato settings page */
                esc_html__( 'DonatoTomato: Organization slug not set. Visit %s.', 'donatotomato' ),
                '<a href="' . esc_url( admin_url( 'options-general.php?page=donatotomato' ) ) . '">' . esc_html__( 'Settings → DonatoTomato', 'donatotomato' ) . '</a>'
            ) . '</p>';
        }

        // empty() rather than '' === so campaign="0" keeps erroring exactly as
        // it did before choose existed. '0' is never a valid campaign (IDs are
        // UUIDs), so the only thing at stake is which failure the author sees,
        // and changing that is not this PR's job. The button shortcode has
        // always used '' === here; that divergence predates this change.
        if ( ! $choose && empty( $campaign ) ) {
            return '<p style="color:#b91c1c;">' . esc_html__( 'DonatoTomato: add a campaign attribute, or choose="yes" to let donors pick a destination.', 'donatotomato' ) . '</p>';
        }

        // choose wins if both are given — the donor-facing chooser is the more
        // specific instruction, and silently ignoring it would be worse.
        return donatotomato_render_iframe( $slug, $choose ? '' : $campaign, $width, $height );
    }
}

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
            'width'    => 480,
            'height'   => 600,
        ], $atts, 'donatotomato' );

        $slug     = sanitize_text_field( $atts['slug'] );
        $campaign = sanitize_text_field( $atts['campaign'] );
        $width    = absint( $atts['width'] ) ?: 480;
        $height   = absint( $atts['height'] ) ?: 600;

        if ( empty( $slug ) ) {
            return '<p style="color:#b91c1c;">DonatoTomato: Organization slug not set. Visit <a href="' . esc_url( admin_url( 'options-general.php?page=donatotomato' ) ) . '">Settings → DonatoTomato</a>.</p>';
        }

        if ( empty( $campaign ) ) {
            return '<p style="color:#b91c1c;">DonatoTomato: <code>campaign</code> attribute is required.</p>';
        }

        return donatotomato_render_iframe( $slug, $campaign, $width, $height );
    }
}

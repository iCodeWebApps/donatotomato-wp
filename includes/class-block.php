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
        $slug     = ! empty( $attributes['orgSlug'] ) ? $attributes['orgSlug'] : get_option( 'donatotomato_org_slug', '' );
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

function donatotomato_render_iframe( $slug, $campaign, $width = 480, $height = 600 ) {
    $src = esc_url(
        DONATOTOMATO_APP_URL . '/widget/' . rawurlencode( $slug ) . '/' . rawurlencode( $campaign ) . '?source=wordpress'
    );

    return sprintf(
        '<div class="donatotomato-wrapper" style="max-width:%dpx;">' .
        '<iframe src="%s" width="%d" height="%d" frameborder="0" allow="payment" loading="lazy"></iframe>' .
        '</div>',
        $width,
        $src,
        $width,
        $height
    );
}

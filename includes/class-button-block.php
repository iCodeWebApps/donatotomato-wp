<?php
/**
 * Gutenberg block `donatotomato/button` — Donate Button modal trigger.
 *
 * Mirrors DonatoTomato_Button_Shortcode in rendered output; the block.json
 * sits in block-button.json and shares the build/index.js editor script
 * with the existing widget block.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Button_Block {

    public function __construct() {
        add_action( 'init', [ $this, 'register' ] );
    }

    public function register() {
        $build_dir = DONATOTOMATO_PLUGIN_DIR . 'build/index.js';
        if ( ! file_exists( $build_dir ) ) {
            return;
        }

        register_block_type( DONATOTOMATO_PLUGIN_DIR . 'block-button.json', [
            'render_callback' => [ $this, 'render' ],
        ] );
    }

    public function render( $attributes ) {
        $campaign = isset( $attributes['campaignId'] ) ? sanitize_text_field( $attributes['campaignId'] ) : '';
        $slug     = ! empty( $attributes['orgSlug'] ) ? sanitize_text_field( $attributes['orgSlug'] ) : '';
        $label    = isset( $attributes['label'] ) && '' !== $attributes['label']
            ? sanitize_text_field( $attributes['label'] )
            : __( 'Donate', 'donatotomato' );

        // className comes from Gutenberg's "Additional CSS class(es)" field —
        // space-separated. Split → sanitize each → rejoin (same pattern as the
        // shortcode) so multi-class strings survive sanitization.
        $class_raw   = isset( $attributes['className'] ) ? $attributes['className'] : '';
        $class_parts = array_filter( array_map( 'sanitize_html_class', explode( ' ', $class_raw ) ) );
        $class       = implode( ' ', $class_parts );

        if ( '' === $campaign ) {
            return '';
        }

        $default_slug = get_option( 'donatotomato_org_slug', '' );
        if ( '' === $slug && '' === $default_slug ) {
            return '';
        }

        DonatoTomato_Embed_Loader::enqueue();
        wp_enqueue_style(
            'donatotomato-button',
            DONATOTOMATO_PLUGIN_URL . 'assets/css/donatotomato-button.css',
            [],
            DONATOTOMATO_VERSION
        );

        $classes = trim( 'donatotomato-button ' . $class );

        $tenant_attr = '';
        if ( '' !== $slug ) {
            $tenant_attr = ' data-dt-tenant="' . esc_attr( $slug ) . '"';
        }

        return sprintf(
            '<button type="button" class="%s" data-dt-donate="%s"%s>%s</button>',
            esc_attr( $classes ),
            esc_attr( $campaign ),
            $tenant_attr,
            esc_html( $label )
        );
    }
}

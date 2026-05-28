<?php
/**
 * Gutenberg block `donatotomato/button` — Donate Button modal trigger.
 *
 * Mirrors DonatoTomato_Button_Shortcode in rendered output; the block.json
 * sits in block-button.json and shares the build/index.js editor script
 * with the existing widget block.
 *
 * @package DonatoTomato
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Button_Block {

    public function __construct() {
        add_action( 'init', [ $this, 'register' ] );
    }

    public function register() {
        $json_path  = DONATOTOMATO_PLUGIN_DIR . 'block-button.json';
        $build_path = DONATOTOMATO_PLUGIN_DIR . 'build/index.js';
        $asset_path = DONATOTOMATO_PLUGIN_DIR . 'build/index.asset.php';

        if ( ! file_exists( $build_path ) || ! file_exists( $json_path ) ) {
            return;
        }

        // We can't pass block-button.json directly to register_block_type():
        // WP's register_block_type_from_metadata() is hardcoded to look for
        // a file literally named `block.json` — anything else gets treated
        // as a folder and WP tries to find `block.json` inside it, then
        // fails silently with metadata = []. Either we'd need to move our
        // metadata to a subfolder named `donatotomato-button/block.json`
        // (with adjusted relative paths inside the JSON), or we do what
        // follows: decode the JSON ourselves, register the editor script
        // and style handles manually, and pass everything as $args.
        $metadata = json_decode( file_get_contents( $json_path ), true );
        if ( ! is_array( $metadata ) || empty( $metadata['name'] ) ) {
            return;
        }

        $script_handle = 'donatotomato-button-editor';
        if ( ! wp_script_is( $script_handle, 'registered' ) ) {
            $asset = file_exists( $asset_path )
                ? include $asset_path
                : array( 'dependencies' => array(), 'version' => DONATOTOMATO_VERSION );
            wp_register_script(
                $script_handle,
                DONATOTOMATO_PLUGIN_URL . 'build/index.js',
                $asset['dependencies'],
                $asset['version'],
                array( 'in_footer' => true )
            );
        }

        $style_handle = 'donatotomato-button-style';
        if ( ! wp_style_is( $style_handle, 'registered' ) ) {
            wp_register_style(
                $style_handle,
                DONATOTOMATO_PLUGIN_URL . 'assets/css/donatotomato-button.css',
                array(),
                DONATOTOMATO_VERSION
            );
        }

        $args                    = $metadata;
        $args['editor_script']   = $script_handle;
        $args['style']           = $style_handle;
        $args['render_callback'] = array( $this, 'render' );
        unset( $args['editorScript'], $args['$schema'] );
        // block.json uses camelCase keys (editorScript, etc); the args dict
        // for WP_Block_Type uses snake_case for most fields. WP_Block_Type's
        // constructor reads name + the keys we set above; other camelCase
        // keys from the JSON (apiVersion, attributes, supports, etc.) are
        // passed through to the constructor, which understands them.

        register_block_type( $metadata['name'], $args );
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

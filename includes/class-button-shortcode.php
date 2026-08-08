<?php
/**
 * [donatotomato_button] shortcode — renders a Donate trigger that opens
 * the donation widget in a focal modal (powered by embed.js).
 *
 *   [donatotomato_button campaign="abc-123"]
 *   [donatotomato_button campaign="abc-123" label="Give now"]
 *   [donatotomato_button campaign="abc-123" label="Donate" class="my-btn"]
 *   [donatotomato_button slug="other-org" campaign="abc-123"]
 *
 * @package DonatoTomato
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Button_Shortcode {

    public function __construct() {
        add_shortcode( 'donatotomato_button', [ $this, 'render' ] );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( [
            'slug'     => '', // Override the global org slug for this button.
            'campaign' => '',
            'choose'   => '',
            'group'    => '',
            'label'    => __( 'Donate', 'donatotomato' ),
            'class'    => '',
        ], $atts, 'donatotomato_button' );

        $slug     = sanitize_text_field( $atts['slug'] );
        $campaign = sanitize_text_field( $atts['campaign'] );
        $group    = sanitize_text_field( $atts['group'] );
        $label    = sanitize_text_field( $atts['label'] );

        // See class-shortcode.php: explicit opt-in, because an empty campaign
        // has always been a reportable mistake. embed.js treats a PRESENT but
        // empty data-dt-donate as "open the destination picker".
        $choose = donatotomato_is_truthy_att( $atts['choose'] );

        // sanitize_html_class() collapses spaces, so a multi-class string like
        // "btn-primary large" would be mangled. Split → sanitize each → rejoin
        // so customers can pass multiple CSS classes for theme integration.
        $class_parts = array_filter( array_map( 'sanitize_html_class', explode( ' ', $atts['class'] ) ) );
        $class       = implode( ' ', $class_parts );

        if ( ! $choose && '' === $campaign ) {
            return '<p style="color:#b91c1c;">' . esc_html__( 'DonatoTomato button: add a campaign attribute, or choose="yes" to let donors pick a destination.', 'donatotomato' ) . '</p>';
        }

        if ( $choose ) {
            $campaign = '';
        }

        // Need a tenant somewhere — either per-button override or the global
        // setting (which gets attached to the embed.js script tag).
        $default_slug = get_option( 'donatotomato_org_slug', '' );
        if ( '' === $slug && '' === $default_slug ) {
            return '<p style="color:#b91c1c;">' . sprintf(
                /* translators: %s: link to DonatoTomato settings page */
                esc_html__( 'DonatoTomato: Organization slug not set. Visit %s.', 'donatotomato' ),
                '<a href="' . esc_url( admin_url( 'options-general.php?page=donatotomato' ) ) . '">' . esc_html__( 'Settings → DonatoTomato', 'donatotomato' ) . '</a>'
            ) . '</p>';
        }

        // Enqueue embed.js + base button styles only when a button is on the page.
        DonatoTomato_Embed_Loader::enqueue();
        wp_enqueue_style(
            'donatotomato-button',
            DONATOTOMATO_PLUGIN_URL . 'assets/css/donatotomato-button.css',
            [],
            DONATOTOMATO_VERSION
        );

        $classes = trim( 'donatotomato-button ' . $class );

        // Per-button slug override: emit data-dt-tenant on the button itself
        // (embed.js prefers element-level over script-tag default).
        $tenant_attr = '';
        if ( '' !== $slug ) {
            $tenant_attr = ' data-dt-tenant="' . esc_attr( $slug ) . '"';
        }

        // embed.js reads data-dt-group and narrows the picker to that label,
        // but only alongside an empty data-dt-donate — it drops the group the
        // moment a campaign is present, exactly as the inline shortcode does.
        // Emitting the attribute only in picker mode keeps the rendered markup
        // of every button written before this attribute existed unchanged.
        $group_attr = '';
        if ( '' === $campaign && '' !== $group ) {
            $group_attr = ' data-dt-group="' . esc_attr( $group ) . '"';
        }

        return sprintf(
            '<button type="button" class="%s" data-dt-donate="%s"%s%s>%s</button>',
            esc_attr( $classes ),
            esc_attr( $campaign ),
            $tenant_attr,
            $group_attr,
            esc_html( $label )
        );
    }
}

<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_settings_page() {
        add_options_page(
            'DonatoTomato Settings',
            'DonatoTomato',
            'manage_options',
            'donatotomato',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'donatotomato_settings', 'donatotomato_org_slug', [
            'type'              => 'string',
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );

        add_settings_section(
            'donatotomato_main',
            '',
            null,
            'donatotomato'
        );

        add_settings_field(
            'donatotomato_org_slug',
            'Organization Slug',
            [ $this, 'render_slug_field' ],
            'donatotomato',
            'donatotomato_main'
        );
    }

    public function render_slug_field() {
        $slug = get_option( 'donatotomato_org_slug', '' );
        ?>
        <input
            type="text"
            name="donatotomato_org_slug"
            value="<?php echo esc_attr( $slug ); ?>"
            class="regular-text"
            placeholder="your-org-slug"
        />
        <p class="description">
            Found in your <a href="https://app.donatotomato.com" target="_blank">DonatoTomato dashboard</a>
            under Settings → Embed Code. Used as the default slug for all widgets on this site.
        </p>
        <?php
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>DonatoTomato</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'donatotomato_settings' );
                do_settings_sections( 'donatotomato' );
                submit_button( 'Save Settings' );
                ?>
            </form>
            <hr />
            <h2>Usage</h2>
            <p><strong>Shortcode:</strong></p>
            <code>[donatotomato campaign="your-campaign-id"]</code>
            <p>Override the org slug for a specific widget:</p>
            <code>[donatotomato slug="other-org" campaign="your-campaign-id" width="480" height="600"]</code>
        </div>
        <?php
    }
}

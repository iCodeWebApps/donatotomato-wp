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
            __( 'DonatoTomato Settings', 'donatotomato' ),
            __( 'DonatoTomato', 'donatotomato' ),
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
            __( 'Organization Slug', 'donatotomato' ),
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
            <?php
            printf(
                /* translators: %s: link to DonatoTomato dashboard */
                esc_html__( 'Found in your %s under Settings → Embed Code. Used as the default slug for all widgets on this site.', 'donatotomato' ),
                '<a href="' . esc_url( 'https://app.donatotomato.com' ) . '" target="_blank">' . esc_html__( 'DonatoTomato dashboard', 'donatotomato' ) . '</a>'
            );
            ?>
        </p>
        <?php
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'DonatoTomato', 'donatotomato' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'donatotomato_settings' );
                do_settings_sections( 'donatotomato' );
                submit_button( __( 'Save Settings', 'donatotomato' ) );
                ?>
            </form>
            <hr />
            <h2><?php esc_html_e( 'Usage', 'donatotomato' ); ?></h2>

            <h3><?php esc_html_e( 'Inline widget', 'donatotomato' ); ?></h3>
            <p><?php esc_html_e( 'Embeds the donation form directly on the page.', 'donatotomato' ); ?></p>
            <p><strong><?php esc_html_e( 'Shortcode:', 'donatotomato' ); ?></strong></p>
            <code>[donatotomato campaign="your-campaign-id"]</code>
            <p><?php esc_html_e( 'Override the org slug for a specific widget:', 'donatotomato' ); ?></p>
            <code>[donatotomato slug="other-org" campaign="your-campaign-id" width="480" height="600"]</code>
            <p><strong><?php esc_html_e( 'Gutenberg block:', 'donatotomato' ); ?></strong> <?php esc_html_e( 'Search for "DonatoTomato Widget" in the block inserter.', 'donatotomato' ); ?></p>

            <h3><?php esc_html_e( 'Donate button (pop-up)', 'donatotomato' ); ?></h3>
            <p><?php esc_html_e( 'Adds a button that opens the donation form in a focal-modal pop-up. Use it in your nav menu, hero CTA, or anywhere else.', 'donatotomato' ); ?></p>
            <p><strong><?php esc_html_e( 'Shortcode:', 'donatotomato' ); ?></strong></p>
            <code>[donatotomato_button campaign="your-campaign-id"]</code>
            <p><?php esc_html_e( 'With a custom label and CSS class:', 'donatotomato' ); ?></p>
            <code>[donatotomato_button campaign="your-campaign-id" label="Give now" class="my-custom-class"]</code>
            <p><strong><?php esc_html_e( 'Gutenberg block:', 'donatotomato' ); ?></strong> <?php esc_html_e( 'Search for "DonatoTomato Donate Button" in the block inserter.', 'donatotomato' ); ?></p>
        </div>
        <?php
    }
}

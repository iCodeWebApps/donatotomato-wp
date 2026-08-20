<?php
/**
 * Admin settings page for DonatoTomato. Three tabs:
 *   - General: Organization ID + usage docs.
 *   - Floating Donate Button: enable, campaign picker, style, placement,
 *     visibility, live preview.
 *   - Shortcode Builder: pick a campaign by name and copy a ready-to-paste
 *     shortcode — the no-Gutenberg path for Divi, Elementor, and other page
 *     builders where the plugin's blocks aren't available.
 *
 * @package DonatoTomato
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Admin {

    const PAGE_SLUG             = 'donatotomato';
    const OPTION_GROUP_GENERAL  = 'donatotomato_settings';
    const OPTION_GROUP_FLOATING = 'donatotomato_settings_floating';

    /**
     * App signup URL, tagged for the plugin hand-off:
     *   - from=wp    so the funnel attributes plugin-originated signups.
     *   - site=<wp-admin base> so the app can offer a one-click deep link back
     *     to this site's plugin settings once the account is created.
     *
     * @return string
     */
    public static function signup_url() {
        return add_query_arg(
            array(
                'from' => 'wp',
                'site' => admin_url(),
            ),
            DONATOTOMATO_APP_URL . '/auth'
        );
    }

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'expose_block_editor_config' ] );
    }

    /**
     * Inject config (slug, app URL, dashboard URLs) into window.donatotomatoBlockEditor
     * so the block editor JS can render the campaign picker + helpful CTAs in
     * the block inspector. The REST nonce + base URL are handled separately by
     * the WordPress api-fetch module (already enqueued site-wide in the editor).
     */
    public function expose_block_editor_config() {
        wp_add_inline_script(
            'wp-blocks',
            'window.donatotomatoBlockEditor = ' . wp_json_encode( [
                'defaultSlug'  => get_option( 'donatotomato_org_slug', '' ),
                'appUrl'       => untrailingslashit( DONATOTOMATO_APP_URL ),
                'settingsUrl'  => admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&tab=general' ),
                'signupUrl'    => esc_url_raw( self::signup_url() ),
                'campaignsUrl' => 'https://app.donatotomato.com/campaigns',
            ] ) . ';',
            'before'
        );
    }

    public function add_settings_page() {
        add_options_page(
            __( 'DonatoTomato Settings', 'donatotomato' ),
            __( 'DonatoTomato', 'donatotomato' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Enqueue tab CSS + the picker/live-preview JS on this plugin's settings
     * screen only.
     *
     * @param string $hook_suffix Current admin page hook suffix.
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_style(
            'donatotomato-admin',
            DONATOTOMATO_PLUGIN_URL . 'assets/admin/settings.css',
            [ 'wp-color-picker' ],
            DONATOTOMATO_VERSION
        );
        wp_enqueue_script(
            'donatotomato-admin',
            DONATOTOMATO_PLUGIN_URL . 'assets/admin/settings.js',
            [ 'jquery', 'wp-color-picker', 'wp-api-fetch' ],
            DONATOTOMATO_VERSION,
            true
        );

        wp_localize_script(
            'donatotomato-admin',
            'donatotomatoAdmin',
            [
                'restRoot'      => esc_url_raw( rest_url( 'donatotomato/v1' ) ),
                'nonce'         => wp_create_nonce( 'wp_rest' ),
                'orgSlug'       => get_option( 'donatotomato_org_slug', '' ),
                'savedCampaign' => get_option( 'donatotomato_floating_campaign', '' ),
                'generalTabUrl' => esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&tab=general' ) ),
                'signupUrl'     => esc_url_raw( self::signup_url() ),
                'campaignsUrl'  => 'https://app.donatotomato.com/campaigns',
                'strings'       => $this->get_admin_strings(),
            ]
        );
    }

    private function get_admin_strings() {
        return [
            'pickCampaign'        => __( 'Select a campaign…', 'donatotomato' ),
            'refresh'             => __( 'Refresh', 'donatotomato' ),
            'refreshing'          => __( 'Refreshing…', 'donatotomato' ),
            'loading'             => __( 'Loading campaigns…', 'donatotomato' ),
            'missingSlug'         => __( 'First, tell us who you are — set your Organization ID in the General tab to enable the floating Donate button.', 'donatotomato' ),
            'missingSlugCta'      => __( 'Open General tab', 'donatotomato' ),
            /* translators: %s: organization slug currently configured in plugin settings */
            'noCampaigns'         => __( 'No campaigns found for "%s" — log in to your DonatoTomato dashboard and create a campaign, then come back here.', 'donatotomato' ),
            'noCampaignsCta'      => __( 'Open DonatoTomato dashboard', 'donatotomato' ),
            /* translators: %s: organization slug currently configured in plugin settings */
            'tenantNotFound'      => __( 'We can\'t find a DonatoTomato account for "%s". Don\'t have one yet?', 'donatotomato' ),
            'tenantNotFoundCta'   => __( 'Sign up free at donatotomato.com', 'donatotomato' ),
            'staleCampaign'       => __( 'Your saved campaign no longer exists — please pick another.', 'donatotomato' ),
            'upstreamError'       => __( 'Could not reach DonatoTomato. Try again in a minute.', 'donatotomato' ),
            'statusActive'        => __( 'Active', 'donatotomato' ),
            'statusDraft'         => __( 'Draft', 'donatotomato' ),
            'statusPaused'        => __( 'Paused', 'donatotomato' ),
            'preview'             => __( 'Live preview', 'donatotomato' ),
            'donateDefault'       => __( 'Donate', 'donatotomato' ),
            'missingSlugBuilder'  => __( 'Set your Organization ID in the General tab to use the shortcode builder.', 'donatotomato' ),
            'copyShortcode'       => __( 'Copy shortcode', 'donatotomato' ),
            'copied'              => __( 'Copied!', 'donatotomato' ),
            'copyManually'        => __( 'Copy failed — select the shortcode text and press Ctrl+C / Cmd+C.', 'donatotomato' ),
            'builderPickFirst'    => __( 'Pick a campaign above to generate your shortcode.', 'donatotomato' ),
            'malformedId'         => __( 'Campaign ID looks malformed. It should be a UUID like 1234abcd-5678-90ef-1234-567890abcdef.', 'donatotomato' ),
        ];
    }

    public function register_settings() {
        // Organization Slug (existing General tab setting).
        register_setting( self::OPTION_GROUP_GENERAL, 'donatotomato_org_slug', [
            'type'              => 'string',
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );

        // Floating Donate Button settings (new tab). Each registered with a
        // dedicated sanitize_callback so options.php saves through the WP
        // Settings API safely.
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_enabled', [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_bool_string' ],
            'default'           => '0',
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_campaign', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_label', [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_label' ],
            'default'           => '',
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_size', [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_size' ],
            'default'           => 'medium',
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_shape', [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_shape' ],
            'default'           => 'pill',
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_color', [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_color' ],
            'default'           => '',
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_show_heart', [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_bool_string' ],
            'default'           => '0',
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_position', [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_position' ],
            'default'           => 'bottom-right',
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_offset', [
            'type'              => 'integer',
            'sanitize_callback' => [ $this, 'sanitize_offset' ],
            'default'           => 24,
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_zindex', [
            'type'              => 'integer',
            'sanitize_callback' => [ $this, 'sanitize_zindex' ],
            'default'           => 999999,
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_exclude_ids', [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_id_list' ],
            'default'           => [],
        ] );
        register_setting( self::OPTION_GROUP_FLOATING, 'donatotomato_floating_auto_hide_inline', [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_bool_string' ],
            'default'           => '1',
        ] );

        // The visible "sections" on each tab are rendered manually inside
        // render_tab_floating(), so we register a single hidden section just
        // to satisfy WP's Settings API for the General tab field.
        add_settings_section( 'donatotomato_main', '', null, self::PAGE_SLUG );
        add_settings_field(
            'donatotomato_org_slug',
            __( 'Organization ID', 'donatotomato' ),
            [ $this, 'render_slug_field' ],
            self::PAGE_SLUG,
            'donatotomato_main'
        );
    }

    public function sanitize_bool_string( $value ) {
        return ( '1' === (string) $value || 1 === $value || true === $value || 'on' === $value ) ? '1' : '0';
    }

    public function sanitize_label( $value ) {
        $value = sanitize_text_field( (string) $value );
        if ( strlen( $value ) > 30 ) {
            $value = substr( $value, 0, 30 );
        }
        return $value;
    }

    public function sanitize_size( $value ) {
        return in_array( $value, [ 'small', 'medium', 'large' ], true ) ? $value : 'medium';
    }

    public function sanitize_shape( $value ) {
        return in_array( $value, [ 'pill', 'rounded', 'sharp' ], true ) ? $value : 'pill';
    }

    public function sanitize_position( $value ) {
        return in_array( $value, [ 'bottom-right', 'bottom-left', 'top-right', 'top-left' ], true )
            ? $value
            : 'bottom-right';
    }

    public function sanitize_color( $value ) {
        $value = sanitize_hex_color( (string) $value );
        return $value ? $value : '';
    }

    public function sanitize_offset( $value ) {
        $value = (int) $value;
        if ( $value < 12 ) {
            return 12;
        }
        if ( $value > 48 ) {
            return 48;
        }
        return $value;
    }

    public function sanitize_zindex( $value ) {
        $value = (int) $value;
        if ( $value < 1 ) {
            return 999999;
        }
        if ( $value > 2147483647 ) {
            return 2147483647;
        }
        return $value;
    }

    public function sanitize_id_list( $value ) {
        if ( is_string( $value ) ) {
            // The hidden form input flattens the multi-select into a
            // comma-separated string when JS isn't applied; normalize it.
            $value = '' === $value ? [] : explode( ',', $value );
        }
        if ( ! is_array( $value ) ) {
            return [];
        }
        $clean = [];
        foreach ( $value as $id ) {
            $id = absint( $id );
            if ( $id > 0 && ! in_array( $id, $clean, true ) ) {
                $clean[] = $id;
            }
        }
        return $clean;
    }

    public function render_slug_field() {
        $slug = get_option( 'donatotomato_org_slug', '' );
        ?>
        <input
            type="text"
            name="donatotomato_org_slug"
            value="<?php echo esc_attr( $slug ); ?>"
            class="regular-text"
            placeholder="your-organization-id"
        />
        <p class="description">
            <?php
            printf(
                /* translators: 1: create-account link, 2: campaigns dashboard link */
                wp_kses_post( __( 'New to DonatoTomato? %1$s. Creating the account costs nothing and there is no monthly fee: DonatoTomato charges a flat 1%% platform fee per donation on top of Stripe\'s standard processing, and your donors are never asked for a tip. Already have an account? Open any campaign in your %2$s and copy the Organization ID shown in its "Add to your website" panel, then paste it here. Used as the default Organization ID for all widgets on this site.', 'donatotomato' ) ),
                '<a href="' . esc_url( self::signup_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Create a free account', 'donatotomato' ) . '</a>',
                '<a href="' . esc_url( 'https://app.donatotomato.com/campaigns' ) . '" target="_blank" rel="noopener">' . esc_html__( 'DonatoTomato dashboard', 'donatotomato' ) . '</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Top-level settings page renderer. Dispatches to the active tab.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tab nav is a read-only navigation parameter, not a state change.
        if ( ! in_array( $active_tab, [ 'general', 'floating', 'builder' ], true ) ) {
            $active_tab = 'general';
        }

        $base_url = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
        ?>
        <div class="wrap donatotomato-settings">
            <h1><?php esc_html_e( 'DonatoTomato', 'donatotomato' ); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'general', $base_url ) ); ?>"
                   class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'General', 'donatotomato' ); ?>
                </a>
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'floating', $base_url ) ); ?>"
                   class="nav-tab <?php echo 'floating' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Floating Donate Button', 'donatotomato' ); ?>
                </a>
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'builder', $base_url ) ); ?>"
                   class="nav-tab <?php echo 'builder' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Shortcode Builder', 'donatotomato' ); ?>
                </a>
            </h2>

            <?php
            if ( 'general' === $active_tab ) {
                $this->render_tab_general();
            } elseif ( 'builder' === $active_tab ) {
                $this->render_tab_builder();
            } else {
                $this->render_tab_floating();
            }
            ?>
        </div>
        <?php
    }

    private function render_tab_general() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( self::OPTION_GROUP_GENERAL );
            do_settings_sections( self::PAGE_SLUG );
            submit_button( __( 'Save Settings', 'donatotomato' ) );
            ?>
        </form>
        <hr />
        <h2><?php esc_html_e( 'Add donations to your site', 'donatotomato' ); ?></h2>

        <h3><?php esc_html_e( 'Floating Donate button — recommended, no code', 'donatotomato' ); ?></h3>
        <p><?php esc_html_e( 'The simplest path: a site-wide Donate button that appears on every page automatically. Pick a campaign, a position, and a style — no shortcodes, no HTML, no developer.', 'donatotomato' ); ?></p>
        <p>
            <a href="<?php echo esc_url( add_query_arg( 'tab', 'floating', admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Set up the floating Donate button', 'donatotomato' ); ?>
            </a>
        </p>

        <details class="donatotomato-advanced-usage">
            <summary><?php esc_html_e( 'Advanced / for developers — shortcodes & blocks', 'donatotomato' ); ?></summary>

            <p>
                <?php
                printf(
                    /* translators: %s: link to the Shortcode Builder tab */
                    esc_html__( 'Tip: you don\'t have to write these by hand — the %s picks a campaign by name and generates a ready-to-paste shortcode.', 'donatotomato' ),
                    '<a href="' . esc_url( add_query_arg( 'tab', 'builder', admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ) ) . '">' . esc_html__( 'Shortcode Builder', 'donatotomato' ) . '</a>'
                );
                ?>
            </p>

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
        </details>
        <?php
    }

    private function render_tab_floating() {
        $org_slug          = (string) get_option( 'donatotomato_org_slug', '' );
        $enabled           = '1' === (string) get_option( 'donatotomato_floating_enabled', '0' );
        $campaign          = (string) get_option( 'donatotomato_floating_campaign', '' );
        $label             = (string) get_option( 'donatotomato_floating_label', '' );
        $size              = (string) get_option( 'donatotomato_floating_size', 'medium' );
        $shape             = (string) get_option( 'donatotomato_floating_shape', 'pill' );
        $color             = (string) get_option( 'donatotomato_floating_color', '' );
        $show_heart        = '1' === (string) get_option( 'donatotomato_floating_show_heart', '0' );
        $position          = (string) get_option( 'donatotomato_floating_position', 'bottom-right' );
        $offset            = (int) get_option( 'donatotomato_floating_offset', 24 );
        $z_index           = (int) get_option( 'donatotomato_floating_zindex', 999999 );
        $exclude_ids       = get_option( 'donatotomato_floating_exclude_ids', [] );
        if ( ! is_array( $exclude_ids ) ) {
            $exclude_ids = [];
        }
        $auto_hide_inline  = '1' === (string) get_option( 'donatotomato_floating_auto_hide_inline', '1' );

        $excluded_posts = [];
        foreach ( $exclude_ids as $eid ) {
            $eid = absint( $eid );
            if ( $eid > 0 ) {
                $p = get_post( $eid );
                if ( $p ) {
                    $excluded_posts[] = $p;
                }
            }
        }
        ?>
        <div class="donatotomato-floating-tab" data-org-slug="<?php echo esc_attr( $org_slug ); ?>">

            <?php
            // After a successful Save on the floating tab, give a non-technical
            // user a clear "it's live" close-out with a link to the front-end,
            // so they know the button is actually on their site.
            if ( isset( $_GET['settings-updated'] ) && $enabled && '' !== $org_slug && '' !== $campaign ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- settings-updated is set by the Settings API redirect; read-only display.
                ?>
                <div class="notice notice-success inline donatotomato-live-notice">
                    <p>
                        <strong><?php esc_html_e( 'Your Donate button is live.', 'donatotomato' ); ?></strong>
                        <?php esc_html_e( 'It now appears on every page of your site.', 'donatotomato' ); ?>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e( 'View your site →', 'donatotomato' ); ?>
                        </a>
                    </p>
                </div>
                <?php
            endif;
            ?>

            <?php if ( '' === $org_slug ) : ?>
                <div class="notice notice-warning inline">
                    <p>
                        <strong><?php esc_html_e( 'First, connect your DonatoTomato account.', 'donatotomato' ); ?></strong>
                        <?php esc_html_e( 'The floating Donate button needs your free DonatoTomato account. If you don\'t have one yet, create it (about 2 minutes) — then add your Organization ID in the General tab.', 'donatotomato' ); ?>
                    </p>
                    <p>
                        <a href="<?php echo esc_url( self::signup_url() ); ?>" class="button button-primary" target="_blank" rel="noopener">
                            <?php esc_html_e( 'Create a free account', 'donatotomato' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&tab=general' ) ); ?>" class="button">
                            <?php esc_html_e( 'I have an account — add my Organization ID', 'donatotomato' ); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php" class="donatotomato-floating-form" <?php echo '' === $org_slug ? 'aria-disabled="true"' : ''; ?>>
                <?php settings_fields( self::OPTION_GROUP_FLOATING ); ?>

                <fieldset class="donatotomato-fieldset" <?php echo '' === $org_slug ? 'disabled="disabled"' : ''; ?>>

                    <section class="donatotomato-section">
                        <h2><?php esc_html_e( 'Content', 'donatotomato' ); ?></h2>

                        <p>
                            <label>
                                <input type="checkbox" name="donatotomato_floating_enabled" value="1" <?php checked( $enabled ); ?> />
                                <strong><?php esc_html_e( 'Enable floating Donate button', 'donatotomato' ); ?></strong>
                            </label>
                        </p>
                        <p class="description">
                            <?php esc_html_e( 'When on, a styled Donate button appears on every front-end page of your site.', 'donatotomato' ); ?>
                        </p>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="donatotomato_floating_campaign"><?php esc_html_e( 'Campaign', 'donatotomato' ); ?></label>
                                </th>
                                <td>
                                    <div class="donatotomato-campaign-picker">
                                        <select id="donatotomato_floating_campaign"
                                                name="donatotomato_floating_campaign"
                                                class="donatotomato-campaign-select"
                                                data-saved="<?php echo esc_attr( $campaign ); ?>">
                                            <?php if ( '' !== $campaign ) : ?>
                                                <option value="<?php echo esc_attr( $campaign ); ?>" selected>
                                                    <?php echo esc_html( $campaign ); ?>
                                                </option>
                                            <?php else : ?>
                                                <option value=""><?php esc_html_e( 'Loading campaigns…', 'donatotomato' ); ?></option>
                                            <?php endif; ?>
                                        </select>
                                        <button type="button" class="button donatotomato-refresh-campaigns">
                                            <?php esc_html_e( 'Refresh', 'donatotomato' ); ?>
                                        </button>
                                    </div>
                                    <p class="donatotomato-picker-status" role="status" aria-live="polite"></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="donatotomato_floating_label"><?php esc_html_e( 'Button label', 'donatotomato' ); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                           id="donatotomato_floating_label"
                                           name="donatotomato_floating_label"
                                           value="<?php echo esc_attr( $label ); ?>"
                                           maxlength="30"
                                           placeholder="<?php esc_attr_e( 'Donate', 'donatotomato' ); ?>"
                                           class="regular-text donatotomato-label-input" />
                                    <p class="donatotomato-label-chips">
                                        <?php
                                        $presets = [ 'Donate', 'Give Now', 'Support Us', 'Make a Gift' ];
                                        $preset_labels = [
                                            __( 'Donate', 'donatotomato' ),
                                            __( 'Give Now', 'donatotomato' ),
                                            __( 'Support Us', 'donatotomato' ),
                                            __( 'Make a Gift', 'donatotomato' ),
                                        ];
                                        foreach ( $presets as $i => $preset ) :
                                            ?>
                                            <button type="button"
                                                    class="button button-small donatotomato-label-chip"
                                                    data-label="<?php echo esc_attr( $preset ); ?>">
                                                <?php echo esc_html( $preset_labels[ $i ] ); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </p>
                                    <p class="description"><?php esc_html_e( 'Up to 30 characters. The label that donors see on the button.', 'donatotomato' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </section>

                    <section class="donatotomato-section">
                        <h2><?php esc_html_e( 'Style', 'donatotomato' ); ?></h2>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Size', 'donatotomato' ); ?></th>
                                <td>
                                    <?php
                                    $size_options = [
                                        'small'  => __( 'Small', 'donatotomato' ),
                                        'medium' => __( 'Medium', 'donatotomato' ),
                                        'large'  => __( 'Large', 'donatotomato' ),
                                    ];
                                    $this->render_segmented( 'donatotomato_floating_size', $size_options, $size );
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Shape', 'donatotomato' ); ?></th>
                                <td>
                                    <?php
                                    $shape_options = [
                                        'pill'    => __( 'Pill', 'donatotomato' ),
                                        'rounded' => __( 'Rounded', 'donatotomato' ),
                                        'sharp'   => __( 'Sharp', 'donatotomato' ),
                                    ];
                                    $this->render_segmented( 'donatotomato_floating_shape', $shape_options, $shape );
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="donatotomato_floating_color"><?php esc_html_e( 'Color', 'donatotomato' ); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                           id="donatotomato_floating_color"
                                           name="donatotomato_floating_color"
                                           value="<?php echo esc_attr( $color ); ?>"
                                           class="donatotomato-color-picker"
                                           data-default-color="" />
                                    <p class="description">
                                        <?php esc_html_e( 'Leave empty to match your campaign primary color automatically.', 'donatotomato' ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Icon', 'donatotomato' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="donatotomato_floating_show_heart"
                                               value="1"
                                               <?php checked( $show_heart ); ?> />
                                        <?php esc_html_e( 'Show a heart icon before the label', 'donatotomato' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </section>

                    <section class="donatotomato-section">
                        <h2><?php esc_html_e( 'Placement', 'donatotomato' ); ?></h2>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Position', 'donatotomato' ); ?></th>
                                <td>
                                    <?php
                                    $position_options = [
                                        'bottom-right' => __( 'Bottom right', 'donatotomato' ),
                                        'bottom-left'  => __( 'Bottom left', 'donatotomato' ),
                                        'top-right'    => __( 'Top right', 'donatotomato' ),
                                        'top-left'     => __( 'Top left', 'donatotomato' ),
                                    ];
                                    $this->render_segmented( 'donatotomato_floating_position', $position_options, $position );
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="donatotomato_floating_offset"><?php esc_html_e( 'Offset from edge', 'donatotomato' ); ?></label>
                                </th>
                                <td>
                                    <input type="range"
                                           id="donatotomato_floating_offset"
                                           name="donatotomato_floating_offset"
                                           min="12"
                                           max="48"
                                           step="1"
                                           value="<?php echo esc_attr( (string) $offset ); ?>"
                                           class="donatotomato-offset-input" />
                                    <span class="donatotomato-offset-value"><?php echo esc_html( $offset . 'px' ); ?></span>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="2">
                                    <details class="donatotomato-advanced">
                                        <summary><?php esc_html_e( 'Advanced', 'donatotomato' ); ?></summary>
                                        <p>
                                            <label for="donatotomato_floating_zindex">
                                                <?php esc_html_e( 'Stacking layer (z-index)', 'donatotomato' ); ?>
                                            </label><br />
                                            <input type="number"
                                                   id="donatotomato_floating_zindex"
                                                   name="donatotomato_floating_zindex"
                                                   value="<?php echo esc_attr( (string) $z_index ); ?>"
                                                   min="1"
                                                   step="1"
                                                   class="small-text" />
                                            <span class="description">
                                                <?php esc_html_e( 'Default 999999 covers virtually all themes. Increase only if a theme element renders above the button.', 'donatotomato' ); ?>
                                            </span>
                                        </p>
                                    </details>
                                </td>
                            </tr>
                        </table>
                    </section>

                    <section class="donatotomato-section">
                        <h2><?php esc_html_e( 'Visibility', 'donatotomato' ); ?></h2>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Hide on these pages', 'donatotomato' ); ?></th>
                                <td>
                                    <select multiple
                                            name="donatotomato_floating_exclude_ids[]"
                                            class="donatotomato-exclude-select"
                                            size="6"
                                            style="min-width:320px;">
                                        <?php
                                        $pages = get_pages(
                                            [
                                                'sort_column' => 'post_title',
                                                'number'      => 200,
                                            ]
                                        );
                                        $exclude_ids_int = array_map( 'intval', $exclude_ids );
                                        foreach ( $pages as $page ) :
                                            ?>
                                            <option value="<?php echo esc_attr( (string) $page->ID ); ?>" <?php selected( in_array( (int) $page->ID, $exclude_ids_int, true ) ); ?>>
                                                <?php echo esc_html( $page->post_title . ' (#' . $page->ID . ')' ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php esc_html_e( 'Hold Ctrl / Cmd to select multiple pages. The floating button will not render on these pages.', 'donatotomato' ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Auto-hide', 'donatotomato' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="donatotomato_floating_auto_hide_inline"
                                               value="1"
                                               <?php checked( $auto_hide_inline ); ?> />
                                        <?php esc_html_e( 'Hide on pages that already contain the inline donation widget', 'donatotomato' ); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e( 'Avoids showing two donate UIs on the same page.', 'donatotomato' ); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </section>

                    <section class="donatotomato-section">
                        <h2><?php esc_html_e( 'Live preview', 'donatotomato' ); ?></h2>
                        <p class="description">
                            <?php esc_html_e( 'A scaled mock of how your button will appear on a page.', 'donatotomato' ); ?>
                        </p>
                        <div class="donatotomato-preview" aria-hidden="true">
                            <div class="donatotomato-preview-frame">
                                <span class="donatotomato-preview-bar"></span>
                                <span class="donatotomato-preview-line donatotomato-preview-line--1"></span>
                                <span class="donatotomato-preview-line donatotomato-preview-line--2"></span>
                                <span class="donatotomato-preview-line donatotomato-preview-line--3"></span>
                                <button type="button" class="donatotomato-preview-button">
                                    <span class="donatotomato-preview-button__heart" aria-hidden="true">&#9829;</span>
                                    <span class="donatotomato-preview-button__label"><?php esc_html_e( 'Donate', 'donatotomato' ); ?></span>
                                </button>
                            </div>
                        </div>
                    </section>

                </fieldset>

                <?php submit_button( __( 'Save Settings', 'donatotomato' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Shortcode Builder tab. Pure client-side generator — nothing here is
     * saved to the database; the campaign list comes from the same admin-only
     * REST proxy the Floating Donate Button tab uses, and the assembled
     * shortcode is copied to the clipboard by assets/admin/settings.js.
     *
     * Exists for page builders: Divi, Elementor, and friends replace the
     * block editor, so the plugin's Gutenberg blocks (and their campaign
     * picker) are unreachable there. This tab gives those users the same
     * pick-by-name experience and hands them a finished shortcode to paste
     * into the builder's Code/Shortcode module.
     */
    private function render_tab_builder() {
        $org_slug = (string) get_option( 'donatotomato_org_slug', '' );
        ?>
        <div class="donatotomato-builder-tab" data-org-slug="<?php echo esc_attr( $org_slug ); ?>">

            <p class="donatotomato-builder-intro">
                <?php esc_html_e( 'Using Divi, Elementor, or another page builder? Those editors replace the WordPress block editor, so the DonatoTomato blocks aren\'t available inside them. Build your shortcode here instead — pick a campaign by name, copy the result, and paste it into your builder.', 'donatotomato' ); ?>
            </p>

            <?php if ( '' === $org_slug ) : ?>
                <div class="notice notice-warning inline">
                    <p>
                        <strong><?php esc_html_e( 'First, connect your DonatoTomato account.', 'donatotomato' ); ?></strong>
                        <?php esc_html_e( 'The shortcode builder needs your free DonatoTomato account. If you don\'t have one yet, create it (about 2 minutes) — then add your Organization ID in the General tab.', 'donatotomato' ); ?>
                    </p>
                    <p>
                        <a href="<?php echo esc_url( self::signup_url() ); ?>" class="button button-primary" target="_blank" rel="noopener">
                            <?php esc_html_e( 'Create a free account', 'donatotomato' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&tab=general' ) ); ?>" class="button">
                            <?php esc_html_e( 'I have an account — add my Organization ID', 'donatotomato' ); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <fieldset class="donatotomato-fieldset" <?php echo '' === $org_slug ? 'disabled="disabled"' : ''; ?>>

                <section class="donatotomato-section">
                    <h2><?php esc_html_e( 'What are you adding?', 'donatotomato' ); ?></h2>
                    <?php
                    $type_options = [
                        'inline' => __( 'Inline donation form', 'donatotomato' ),
                        'button' => __( 'Donate button (pop-up)', 'donatotomato' ),
                    ];
                    $this->render_segmented( 'donatotomato_builder_type', $type_options, 'inline' );
                    ?>
                    <p class="description donatotomato-builder-type-help" data-inline="<?php esc_attr_e( 'Embeds the full donation form directly on the page — best for a dedicated Donate page.', 'donatotomato' ); ?>" data-button="<?php esc_attr_e( 'Adds a Donate button that opens the donation form in a pop-up over the page — great for heroes and CTAs.', 'donatotomato' ); ?>">
                        <?php esc_html_e( 'Embeds the full donation form directly on the page — best for a dedicated Donate page.', 'donatotomato' ); ?>
                    </p>
                </section>

                <section class="donatotomato-section">
                    <h2><?php esc_html_e( 'Where the money goes', 'donatotomato' ); ?></h2>
                    <p class="donatotomato-builder-choose-wrap">
                        <label>
                            <input type="checkbox" class="donatotomato-builder-choose" />
                            <strong><?php esc_html_e( 'Let donors choose the destination', 'donatotomato' ); ?></strong>
                        </label>
                        <span class="description">
                            <?php esc_html_e( 'The form opens on a list of your active campaigns, so one embed covers every fund. Use this if you give per missionary, program, or project. Leave it off to embed a single campaign.', 'donatotomato' ); ?>
                        </span>
                    </p>

                    <div class="donatotomato-builder-group-wrap" style="display:none;">
                        <p>
                            <label for="donatotomato_builder_group">
                                <strong><?php esc_html_e( 'Narrow the list to one group (optional)', 'donatotomato' ); ?></strong>
                            </label>
                            <br />
                            <input type="text"
                                   id="donatotomato_builder_group"
                                   class="regular-text donatotomato-builder-group"
                                   list="donatotomato-builder-groups"
                                   maxlength="60"
                                   placeholder="<?php esc_attr_e( 'All active campaigns', 'donatotomato' ); ?>" />
                            <datalist id="donatotomato-builder-groups"></datalist>
                        </p>
                        <p class="description">
                            <?php esc_html_e( 'Leave this empty and the list offers every active campaign. Enter a group name and it offers only the campaigns you put in that group, so one page can offer your missionaries and another your programs.', 'donatotomato' ); ?>
                        </p>
                        <p class="description">
                            <?php esc_html_e( 'Groups are set per campaign in your DonatoTomato dashboard, and the name here has to match the one you typed there. Capitalisation and extra spaces do not matter. A group is a way to shorten a long list, not a way to hide a campaign — anyone can still reach every active campaign through an embed with no group.', 'donatotomato' ); ?>
                        </p>
                    </div>
                </section>

                <section class="donatotomato-section donatotomato-builder-campaign-section">
                    <h2><?php esc_html_e( 'Campaign', 'donatotomato' ); ?></h2>
                    <div class="donatotomato-campaign-picker">
                        <select class="donatotomato-builder-select">
                            <option value=""><?php esc_html_e( 'Loading campaigns…', 'donatotomato' ); ?></option>
                        </select>
                        <button type="button" class="button donatotomato-builder-refresh">
                            <?php esc_html_e( 'Refresh', 'donatotomato' ); ?>
                        </button>
                    </div>
                    <p class="donatotomato-picker-status donatotomato-builder-status" role="status" aria-live="polite"></p>

                    <details class="donatotomato-advanced donatotomato-builder-manual-wrap">
                        <summary><?php esc_html_e( 'Or paste a campaign ID manually', 'donatotomato' ); ?></summary>
                        <p>
                            <input type="text"
                                   class="regular-text code donatotomato-builder-manual"
                                   placeholder="1234abcd-5678-90ef-1234-567890abcdef" />
                        </p>
                        <p class="description">
                            <?php esc_html_e( 'Found on the Campaign Detail page in your DonatoTomato dashboard. When filled in, this overrides the dropdown above.', 'donatotomato' ); ?>
                        </p>
                        <p class="donatotomato-picker-status donatotomato-builder-manual-warning" role="status" aria-live="polite"></p>
                    </details>
                </section>

                <section class="donatotomato-section">
                    <h2><?php esc_html_e( 'Options', 'donatotomato' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr class="donatotomato-builder-row donatotomato-builder-row--inline">
                            <th scope="row">
                                <label for="donatotomato_builder_width"><?php esc_html_e( 'Width (px)', 'donatotomato' ); ?></label>
                            </th>
                            <td>
                                <input type="number"
                                       id="donatotomato_builder_width"
                                       class="small-text donatotomato-builder-width"
                                       value="480"
                                       min="300"
                                       max="1200"
                                       step="10" />
                                <p class="description"><?php esc_html_e( 'Maximum width of the form. Default 480.', 'donatotomato' ); ?></p>
                            </td>
                        </tr>
                        <tr class="donatotomato-builder-row donatotomato-builder-row--inline">
                            <th scope="row">
                                <label for="donatotomato_builder_height"><?php esc_html_e( 'Height (px)', 'donatotomato' ); ?></label>
                            </th>
                            <td>
                                <input type="number"
                                       id="donatotomato_builder_height"
                                       class="small-text donatotomato-builder-height"
                                       value="600"
                                       min="400"
                                       max="1200"
                                       step="10" />
                                <p class="description"><?php esc_html_e( 'Initial height. The form auto-resizes to fit its content after it loads. Default 600.', 'donatotomato' ); ?></p>
                            </td>
                        </tr>
                        <tr class="donatotomato-builder-row donatotomato-builder-row--button" style="display:none;">
                            <th scope="row">
                                <label for="donatotomato_builder_label"><?php esc_html_e( 'Button label', 'donatotomato' ); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                       id="donatotomato_builder_label"
                                       class="regular-text donatotomato-builder-label"
                                       maxlength="30"
                                       placeholder="<?php esc_attr_e( 'Donate', 'donatotomato' ); ?>" />
                                <p class="description"><?php esc_html_e( 'The text donors see on the button. Leave empty for "Donate".', 'donatotomato' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </section>

                <section class="donatotomato-section">
                    <h2><?php esc_html_e( 'Your shortcode', 'donatotomato' ); ?></h2>
                    <div class="donatotomato-builder-output-row">
                        <input type="text"
                               readonly
                               class="large-text code donatotomato-builder-output"
                               value=""
                               placeholder="<?php esc_attr_e( 'Pick a campaign above to generate your shortcode.', 'donatotomato' ); ?>"
                               aria-label="<?php esc_attr_e( 'Generated shortcode', 'donatotomato' ); ?>" />
                        <button type="button" class="button button-primary donatotomato-builder-copy" disabled="disabled">
                            <?php esc_html_e( 'Copy shortcode', 'donatotomato' ); ?>
                        </button>
                    </div>
                    <p class="donatotomato-picker-status donatotomato-builder-copy-status" role="status" aria-live="polite"></p>

                    <h3><?php esc_html_e( 'Where to paste it', 'donatotomato' ); ?></h3>
                    <ul class="donatotomato-builder-where">
                        <li><strong><?php esc_html_e( 'Divi:', 'donatotomato' ); ?></strong> <?php esc_html_e( 'add a Code module (or a Text module) where you want the donation form and paste the shortcode inside.', 'donatotomato' ); ?></li>
                        <li><strong><?php esc_html_e( 'Elementor:', 'donatotomato' ); ?></strong> <?php esc_html_e( 'add a Shortcode widget (or an HTML widget) and paste the shortcode into it.', 'donatotomato' ); ?></li>
                        <li><strong><?php esc_html_e( 'Block editor:', 'donatotomato' ); ?></strong> <?php esc_html_e( 'you don\'t need a shortcode — use the DonatoTomato blocks from the inserter. (A Shortcode block works too.)', 'donatotomato' ); ?></li>
                        <li><strong><?php esc_html_e( 'Classic editor / widgets:', 'donatotomato' ); ?></strong> <?php esc_html_e( 'paste the shortcode directly into the content.', 'donatotomato' ); ?></li>
                    </ul>
                </section>

            </fieldset>
        </div>
        <?php
    }

    /**
     * Render a radio-as-segmented-control. Each option becomes a labeled
     * radio with the same name so submit posts the chosen value.
     *
     * @param string $name    Field name.
     * @param array  $options value => label map.
     * @param string $current Currently selected value.
     */
    private function render_segmented( $name, array $options, $current ) {
        echo '<div class="donatotomato-segmented" role="radiogroup">';
        foreach ( $options as $value => $label ) {
            $id = $name . '_' . $value;
            printf(
                '<label class="donatotomato-segmented__option" for="%1$s"><input type="radio" id="%1$s" name="%2$s" value="%3$s" %4$s /><span>%5$s</span></label>',
                esc_attr( $id ),
                esc_attr( $name ),
                esc_attr( $value ),
                checked( $current, $value, false ),
                esc_html( $label )
            );
        }
        echo '</div>';
    }
}

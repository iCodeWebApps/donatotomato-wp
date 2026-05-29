<?php
/**
 * First-activation onboarding notice.
 *
 * Drops a dismissible admin notice on the first admin page load after the
 * plugin is activated, pointing new installers at the Floating Donate Button
 * settings tab. Non-technical site admins don't reliably discover the tab
 * by browsing Settings → DonatoTomato on their own.
 *
 * Guard rails:
 *   - Only `manage_options` users see the notice (admins).
 *   - If the floating button is already enabled at activation time, no
 *     notice is set (existing users on upgrade path don't need it).
 *   - Dismissal is persisted per-user, so re-activating the plugin does
 *     NOT re-show the notice to a user who has already dismissed it.
 *
 * @package DonatoTomato
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Activation_Notice {

    const TRANSIENT_KEY = 'donatotomato_show_activation_notice';
    const USER_META_KEY = 'donatotomato_dismissed_activation_notice';
    const AJAX_ACTION   = 'donatotomato_dismiss_activation_notice';
    const NONCE_ACTION  = 'donatotomato_dismiss_activation_notice';

    public function __construct() {
        add_action( 'admin_notices', [ $this, 'maybe_render_notice' ] );
        add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'handle_dismiss_ajax' ] );
    }

    /**
     * Activation hook callback (registered against the plugin file from
     * donatotomato.php — activation hooks must be registered against the
     * plugin file path, which is awkward from inside an included class).
     *
     * Sets a 30-day transient that signals "show the onboarding notice on
     * the next admin page load." Skipped entirely if the floating button
     * is already enabled (upgrade-from-1.3.x flow).
     */
    public static function on_activate() {
        if ( '1' === (string) get_option( 'donatotomato_floating_enabled', '0' ) ) {
            return;
        }
        set_transient( self::TRANSIENT_KEY, 1, 30 * DAY_IN_SECONDS );
    }

    /**
     * Render the admin notice if all gates pass:
     *   - transient is set (recent activation)
     *   - current user can manage_options
     *   - current user has not dismissed it before
     */
    public function maybe_render_notice() {
        if ( ! get_transient( self::TRANSIENT_KEY ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( get_user_meta( get_current_user_id(), self::USER_META_KEY, true ) ) {
            return;
        }

        $settings_url = admin_url( 'options-general.php?page=' . DonatoTomato_Admin::PAGE_SLUG . '&tab=floating' );
        $docs_url     = 'https://www.donatotomato.com/embed-guide';
        $nonce        = wp_create_nonce( self::NONCE_ACTION );
        ?>
        <div class="notice notice-success is-dismissible donatotomato-activation-notice" data-nonce="<?php echo esc_attr( $nonce ); ?>">
            <p>
                <strong><?php esc_html_e( 'DonatoTomato is ready.', 'donatotomato' ); ?></strong>
                <?php esc_html_e( 'Set up the floating Donate button in 60 seconds — it appears on every page of your site automatically.', 'donatotomato' ); ?>
            </p>
            <p>
                <a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Quick setup', 'donatotomato' ); ?>
                </a>
                <a href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer" class="button-link" style="margin-left:8px;">
                    <?php esc_html_e( 'Read the docs', 'donatotomato' ); ?>
                </a>
            </p>
        </div>
        <script>
        ( function() {
            var notice = document.querySelector( '.donatotomato-activation-notice' );
            if ( ! notice ) {
                return;
            }
            notice.addEventListener( 'click', function( event ) {
                if ( ! event.target.classList.contains( 'notice-dismiss' ) ) {
                    return;
                }
                var data = new FormData();
                data.append( 'action', '<?php echo esc_js( self::AJAX_ACTION ); ?>' );
                data.append( 'nonce', notice.getAttribute( 'data-nonce' ) || '' );
                fetch( ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                } );
            } );
        } )();
        </script>
        <?php
    }

    /**
     * AJAX handler. Persists the dismissal to user_meta so the notice
     * doesn't re-show on subsequent admin loads, even across plugin
     * deactivate/reactivate cycles.
     */
    public function handle_dismiss_ajax() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
        }

        update_user_meta( get_current_user_id(), self::USER_META_KEY, 1 );
        // Also clear the transient so other admins don't see a stale notice
        // after one admin has dismissed (best effort — per-user dismissal
        // still wins via the user_meta check).
        delete_transient( self::TRANSIENT_KEY );

        wp_send_json_success();
    }
}

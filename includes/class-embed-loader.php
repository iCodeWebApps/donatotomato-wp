<?php
/**
 * Embed.js loader — registers the focal-modal script once and attaches the
 * site's org slug as the data-dt-tenant attribute on the rendered <script>
 * tag. Both DonatoTomato_Button_Shortcode and DonatoTomato_Button_Block
 * call DonatoTomato_Embed_Loader::enqueue() inside their render paths so
 * embed.js only loads on pages that actually contain a Donate button.
 *
 * @package DonatoTomato
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Embed_Loader {

    const HANDLE = 'donatotomato-embed';

    /**
     * Register the script on init so the script_loader_tag filter has a
     * stable handle to match against.
     */
    public static function bootstrap() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register' ] );
        add_filter( 'script_loader_tag', [ __CLASS__, 'add_tenant_attribute' ], 10, 2 );
    }

    public static function register() {
        // 'strategy' => 'async' is the modern (WP 6.3+) way to mark the
        // script as async. On WP < 6.3 the strategy arg is silently ignored
        // — add_tenant_attribute() below also injects `async` via the tag
        // filter (idempotent if 6.3+ already rendered it), so async load
        // works on all supported WP versions.
        wp_register_script(
            self::HANDLE,
            DONATOTOMATO_APP_URL . '/embed.js',
            [],
            // Version intentionally null — embed.js is server-managed at
            // app.donatotomato.com; cache-busting is handled upstream and
            // we never append a ?ver= query string here.
            null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotSetVersion
            [
                'strategy'  => 'async',
                'in_footer' => true,
            ]
        );
    }

    /**
     * Called from each button render callback to mark embed.js as needed
     * on this page. Safe to call multiple times — wp_enqueue_script is
     * idempotent on the handle.
     */
    public static function enqueue() {
        wp_enqueue_script( self::HANDLE );
    }

    /**
     * Inject data-dt-tenant onto the script tag so embed.js can pick up
     * the configured org slug at load time. Also force-add `async` for
     * WP < 6.3 (where the 'strategy' register_script arg is ignored).
     *
     * @param string $tag    The full <script> tag rendered by WP.
     * @param string $handle The script handle the tag was rendered for.
     * @return string Possibly-modified tag.
     */
    public static function add_tenant_attribute( $tag, $handle ) {
        if ( self::HANDLE !== $handle ) {
            return $tag;
        }

        // Belt-and-suspenders async (no-op on WP 6.3+ where the strategy
        // arg already added it). Single substring check is sufficient — WP's
        // script_loader_tag renders boolean attrs as bare tokens.
        if ( false === strpos( $tag, ' async' ) ) {
            $tag = str_replace( '<script ', '<script async ', $tag );
        }

        $slug = get_option( 'donatotomato_org_slug', '' );
        if ( '' === $slug ) {
            return $tag;
        }
        return str_replace(
            ' src=',
            ' data-dt-tenant="' . esc_attr( $slug ) . '" src=',
            $tag
        );
    }
}

<?php
/**
 * REST proxy for the DonatoTomato public-campaigns endpoint. Admin-only
 * (nonce + manage_options), cached via a 5-minute transient keyed on the
 * configured organization slug. Refreshable via ?refresh=1.
 *
 * @package DonatoTomato
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Campaign_Picker {

    const REST_NAMESPACE  = 'donatotomato/v1';
    const REST_ROUTE      = '/campaigns';
    const TRANSIENT_PREFIX = 'donatotomato_campaigns_';
    const TRANSIENT_TTL   = 300;
    const API_URL         = 'https://api.donatotomato.com/functions/v1/get-public-campaigns';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_route' ] );
    }

    public function register_route() {
        register_rest_route(
            self::REST_NAMESPACE,
            self::REST_ROUTE,
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'handle_request' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'refresh' => [
                        'type'              => 'boolean',
                        'required'          => false,
                        'sanitize_callback' => 'rest_sanitize_boolean',
                    ],
                ],
            ]
        );
    }

    /**
     * Admin-only access. The WP REST cookie auth path automatically validates
     * the X-WP-Nonce header against the current user; combined with the
     * manage_options capability check, only logged-in admins reach the
     * handler.
     */
    public function permission_check() {
        return current_user_can( 'manage_options' );
    }

    public function handle_request( WP_REST_Request $request ) {
        $slug = get_option( 'donatotomato_org_slug', '' );
        $slug = sanitize_text_field( $slug );

        if ( '' === $slug ) {
            return new WP_REST_Response(
                [
                    'error'     => 'missing_slug',
                    'campaigns' => [],
                ],
                400
            );
        }

        $refresh        = (bool) $request->get_param( 'refresh' );
        $transient_key  = self::TRANSIENT_PREFIX . md5( $slug );

        if ( ! $refresh ) {
            $cached = get_transient( $transient_key );
            if ( false !== $cached && is_array( $cached ) ) {
                return new WP_REST_Response(
                    [
                        'slug'      => $slug,
                        'cached'    => true,
                        'campaigns' => $cached,
                    ],
                    200
                );
            }
        }

        $response = wp_remote_get(
            add_query_arg( 'slug', rawurlencode( $slug ), self::API_URL ),
            [
                'timeout' => 8,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response(
                [
                    'error'     => 'upstream_unreachable',
                    'message'   => $response->get_error_message(),
                    'campaigns' => [],
                ],
                502
            );
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        $body   = wp_remote_retrieve_body( $response );

        if ( 404 === $status ) {
            return new WP_REST_Response(
                [
                    'error'     => 'tenant_not_found',
                    'slug'      => $slug,
                    'campaigns' => [],
                ],
                404
            );
        }

        if ( 200 !== $status ) {
            return new WP_REST_Response(
                [
                    'error'     => 'upstream_error',
                    'status'    => $status,
                    'campaigns' => [],
                ],
                502
            );
        }

        $decoded = json_decode( $body, true );
        if ( ! is_array( $decoded ) ) {
            return new WP_REST_Response(
                [
                    'error'     => 'invalid_response',
                    'campaigns' => [],
                ],
                502
            );
        }

        set_transient( $transient_key, $decoded, self::TRANSIENT_TTL );

        return new WP_REST_Response(
            [
                'slug'      => $slug,
                'cached'    => false,
                'campaigns' => $decoded,
            ],
            200
        );
    }
}

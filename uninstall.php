<?php
/**
 * Uninstall cleanup. Runs only when the user deletes the plugin from the
 * Plugins screen. Removes every option, transient, and meta value the plugin
 * creates so nothing is left orphaned in the database.
 *
 * Single-site scope: the plugin stores all of its settings as per-site
 * options, so this mirrors that model. (A network-wide multisite sweep is not
 * needed because the plugin never writes network options.)
 *
 * @package DonatoTomato
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Best-effort: clear the per-slug campaign cache transient before the slug
// option is deleted (the campaign-picker caches the upstream response keyed on
// md5 of the configured slug, 5-minute TTL).
$donatotomato_slug = get_option( 'donatotomato_org_slug', '' );
if ( '' !== (string) $donatotomato_slug ) {
    delete_transient( 'donatotomato_campaigns_' . md5( (string) $donatotomato_slug ) );
}

// Every option the plugin registers (General + Floating Donate Button tabs).
$donatotomato_options = array(
    'donatotomato_org_slug',
    'donatotomato_floating_enabled',
    'donatotomato_floating_campaign',
    'donatotomato_floating_label',
    'donatotomato_floating_size',
    'donatotomato_floating_shape',
    'donatotomato_floating_color',
    'donatotomato_floating_color_resolved',
    'donatotomato_floating_color_resolved_for',
    'donatotomato_floating_show_heart',
    'donatotomato_floating_position',
    'donatotomato_floating_offset',
    'donatotomato_floating_zindex',
    'donatotomato_floating_exclude_ids',
    'donatotomato_floating_auto_hide_inline',
);
foreach ( $donatotomato_options as $donatotomato_option ) {
    delete_option( $donatotomato_option );
}

// Activation-notice signal transient.
delete_transient( 'donatotomato_show_activation_notice' );

// Throttle for the campaign-color lookup that keeps the floating button's
// resolved color current.
delete_transient( 'donatotomato_color_resolved_check' );

// Per-user "dismissed the activation notice" flag (all users).
delete_metadata( 'user', 0, 'donatotomato_dismissed_activation_notice', '', true );

// Per-post "has an inline donation widget" flag written on save_post (all posts).
delete_metadata( 'post', 0, '_dt_has_inline_widget', '', true );

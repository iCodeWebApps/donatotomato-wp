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
$dt_slug = get_option( 'donatotomato_org_slug', '' );
if ( '' !== (string) $dt_slug ) {
    delete_transient( 'donatotomato_campaigns_' . md5( (string) $dt_slug ) );
}

// Every option the plugin registers (General + Floating Donate Button tabs).
$dt_options = array(
    'donatotomato_org_slug',
    'donatotomato_floating_enabled',
    'donatotomato_floating_campaign',
    'donatotomato_floating_label',
    'donatotomato_floating_size',
    'donatotomato_floating_shape',
    'donatotomato_floating_color',
    'donatotomato_floating_show_heart',
    'donatotomato_floating_position',
    'donatotomato_floating_offset',
    'donatotomato_floating_zindex',
    'donatotomato_floating_exclude_ids',
    'donatotomato_floating_auto_hide_inline',
);
foreach ( $dt_options as $dt_option ) {
    delete_option( $dt_option );
}

// Activation-notice signal transient.
delete_transient( 'donatotomato_show_activation_notice' );

// Per-user "dismissed the activation notice" flag (all users).
delete_metadata( 'user', 0, 'donatotomato_dismissed_activation_notice', '', true );

// Per-post "has an inline donation widget" flag written on save_post (all posts).
delete_metadata( 'post', 0, '_dt_has_inline_widget', '', true );

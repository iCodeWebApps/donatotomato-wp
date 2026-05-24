<?php
/**
 * Plugin Name: DonatoTomato
 * Plugin URI:  https://donatotomato.com
 * Description: Embed a DonatoTomato donation widget on any page or post.
 * Version:     1.1.0
 * Author:      DonatoTomato
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: donatotomato
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DONATOTOMATO_VERSION', '1.1.0' );
define( 'DONATOTOMATO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DONATOTOMATO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DONATOTOMATO_APP_URL', 'https://app.donatotomato.com' );

require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-admin.php';
require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-block.php';

new DonatoTomato_Admin();
new DonatoTomato_Shortcode();
new DonatoTomato_Block();


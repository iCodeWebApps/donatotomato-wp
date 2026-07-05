<?php
/**
 * Plugin Name: DonatoTomato - Nonprofit Donation Forms, Recurring Giving & Tax Receipts
 * Plugin URI:  https://donatotomato.com
 * Description: Embed a DonatoTomato donation widget on any page or post.
 * Version:     1.4.6
 * Author:      DonatoTomato
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: donatotomato
 *
 * @package DonatoTomato
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DONATOTOMATO_VERSION', '1.4.5' );
define( 'DONATOTOMATO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DONATOTOMATO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DONATOTOMATO_APP_URL', 'https://app.donatotomato.com' );

require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-admin.php';
require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-block.php';
require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-embed-loader.php';
require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-button-shortcode.php';
require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-button-block.php';
require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-campaign-picker.php';
require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-floating-button.php';
require_once DONATOTOMATO_PLUGIN_DIR . 'includes/class-activation-notice.php';

new DonatoTomato_Admin();
new DonatoTomato_Shortcode();
new DonatoTomato_Block();
DonatoTomato_Embed_Loader::bootstrap();
new DonatoTomato_Button_Shortcode();
new DonatoTomato_Button_Block();
new DonatoTomato_Campaign_Picker();
new DonatoTomato_Floating_Button();
new DonatoTomato_Activation_Notice();

register_activation_hook( __FILE__, [ 'DonatoTomato_Activation_Notice', 'on_activate' ] );

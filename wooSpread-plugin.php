<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Woo Spreadshirt
 * Description:       Import items and categories from Spreadshirt into Woocommmerce
 * Version:           1.0.0
 * Author:            Greenwich design
 * Author URI:        www.greenwich-design.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-spreadshirt
 * Domain Path:       /languages
 */

 $currentPage = $_SERVER['REQUEST_URI'];

 



echo $currentPage;


if ( ! defined( 'WPINC' ) ) {
	die;
}

class wooSpreadPlugin
{
  public $plugin;

  function __construct() {
    $this->plugin = plugin_basename(__FILE__);
  }

  function register() {
    add_action('admin_menu', array($this, 'add_admin_page'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    add_filter("plugin_action_links_$this->plugin", array($this, 'settings_link'));
  }

  public function settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=wooSpread_plugin">Settings</a>';
    array_push($links, $settings_link);
    return $links;
  }

  function enqueue_assets() {
    wp_enqueue_style( "$this->plugin-css", plugins_url('/public/styles.css', __FILE__) );
    wp_enqueue_script( "$this->plugin-js", plugins_url('/public/scripts.js', __FILE__), null, null, true );
    wp_localize_script("$this->plugin-js", 'myVueObj', array(
      "rest_url" => get_rest_url()
    ));
  }

  public function add_admin_page() {
    add_menu_page("Woo Spreadshirt", 'Woo Spreadshirt', 'manage_options', 'wooSpread_plugin', array($this, 'admin_index'), '');
  }

  public function admin_index() {
    require_once plugin_dir_path(__FILE__) . 'templates/admin/index.php';
  }
}

if ( class_exists('wooSpreadPlugin') ) {
  $wooSpreadPlugin = new wooSpreadPlugin();
  $wooSpreadPlugin->register();
}

// Activation
require_once plugin_dir_path(__FILE__)  . 'inc/wooSpread-plugin-activate.php';
register_activation_hook( __FILE__, array( 'wooSpreadPluginActivate', 'activate' ) );

// Deactivation
require_once plugin_dir_path(__FILE__)  . 'inc/wooSpread-plugin-deactivate.php';
register_deactivation_hook( __FILE__, array( 'wooSpreadPluginDeactivate', 'deactivate' ) );

// Connect to Spreadshirt
require_once plugin_dir_path(__FILE__)  . 'inc/spread-api.php';

// Import
require_once plugin_dir_path(__FILE__)  . 'inc/import.php';

// Delete
require_once plugin_dir_path(__FILE__)  . 'inc/delete.php';

// Rest API
require_once plugin_dir_path(__FILE__)  . 'inc/rest-api.php';

// Convert Woo cart to Spreadshirt cart
require_once plugin_dir_path(__FILE__)  . 'inc/cart.php';





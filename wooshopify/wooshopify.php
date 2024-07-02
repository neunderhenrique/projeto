<?php
/*
 * Plugin Name: WooShopify
 * Plugin URI: http://example.com/wooshopify-uri/
 * Description: The goal of WooShopify is to create a Live View tab that works exclusively with the WooCommerce plugin to give you the best stats from your shop.
 * Version: 1.0.0
 * Author: Your Name or Your Company
 * Author URI: http://example.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wooshopify
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Start a session if one hasn't already been started
if (!session_id()) {
    session_start();
}

// Load the core plugin class and other necessary files
require plugin_dir_path( __FILE__ ) . 'includes/class-wooshopify-activator.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-wooshopify-deactivator.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-wooshopify.php';

// Activation and deactivation hooks
register_activation_hook( __FILE__, array('WooShopify_Activator', 'activate') );
register_deactivation_hook( __FILE__, array('WooShopify_Deactivator', 'deactivate') );

// Run the plugin
function run_wooshopify() {
    $plugin = new WooShopify();
    $plugin->run();
}
run_wooshopify();

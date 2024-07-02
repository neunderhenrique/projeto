<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           WooShopify
 *
 * @wordpress-plugin
 * Plugin Name:       WooShopify
 * Plugin URI:        http://example.com/wooshopify-uri/
 * Description:       The goal of WooShopify is to create a Live View tab that works exclusively with the WooCommerce plugin to give you the best stats from your shop.
 * Version:           1.0.0
 * Author:            Your Name or Your Company
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wooshopify
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOSHOPIFY_VERSION', '1.0.0' );

// Start a session if one hasn't already been started
if (!session_id()) {
    session_start();
}

// Load the necessary files
require plugin_dir_path( __FILE__ ) . 'includes/class-wooshopify-activator.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-wooshopify-deactivator.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-wooshopify.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wooshopify-activator.php
 */
function activate_wooshopify() {
    WooShopify_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wooshopify-deactivator.php
 */
function deactivate_wooshopify() {
    WooShopify_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wooshopify' );
register_deactivation_hook( __FILE__, 'deactivate_wooshopify' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wooshopify() {
    $plugin = new WooShopify();
    $plugin->run();
}
run_wooshopify();

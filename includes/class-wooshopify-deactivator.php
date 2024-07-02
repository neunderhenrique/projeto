<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    WooShopify
 * @subpackage WooShopify/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WooShopify
 * @subpackage WooShopify/includes
 * @author     Your Name <email@example.com>
 */
class WooShopify_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Perform tasks on plugin deactivation
    }

    // Function to delete the necessary table
    public static function ava_delete_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'active_visitors';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}

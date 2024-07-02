<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    WooShopify
 * @subpackage WooShopify/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooShopify
 * @subpackage WooShopify/admin
 * @author     Your Name <email@example.com>
 */
class WooShopify_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $wooshopify    The ID of this plugin.
     */
    private $wooshopify;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $wooshopify       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $wooshopify, $version ) {
        $this->wooshopify = $wooshopify;
        $this->version = $version;

        // Add admin menu
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_post_wooshopify_uninstall', array($this, 'handle_uninstall'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->wooshopify, plugin_dir_url( __FILE__ ) . 'css/wooshopify-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->wooshopify, plugin_dir_url( __FILE__ ) . 'js/wooshopify-admin.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Add the Live View submenu under WooCommerce.
     */
    public function add_menu() {
        add_submenu_page(
            'woocommerce',
            'Live View',
            'Live View',
            'manage_options',
            'active-visitors-live-view',
            array($this, 'render_live_view_page')
        );

        add_submenu_page(
            null,
            'Uninstall WooShopify',
            'Uninstall WooShopify',
            'manage_options',
            'wooshopify-uninstall',
            array($this, 'render_uninstall_page')
        );
    }

    /**
     * Render the Live View page.
     */
    public function render_live_view_page() {
        $plugin = new WooShopify();
        $active_visitors = $plugin->count_active_visitors();
        $total_sessions = $plugin->count_unique_sessions_today();
        $total_sales = $plugin->get_total_sales_for_today();
        $top_products = $plugin->get_top_products_for_today();
        $orders = $plugin->get_first_and_return_orders_for_today();

        echo '<h1>Live View</h1>';
        echo '<p>Visitors right now: <span id="active-visitors-count">' . $active_visitors . '</span></p>';
        echo '<p>Total Unique Sessions for the day: <span id="total-sessions">' . $total_sessions . '</span></p>';
        echo '<p>Total Sales for Today: <span id="total-sales">' . wc_price($total_sales) . '</span></p>';
        echo '<h2>Top 3 Products Sold Today</h2>';
        echo '<ul id="top-products">';
        foreach ($top_products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            echo '<li>' . $product->get_name() . ': ' . $quantity . '</li>';
        }
        echo '</ul>';
        echo '<h2>Order Types</h2>';
        echo '<p>First-time Orders: <span id="first-time-orders">' . count($orders['first_time_orders']) . '</span></p>';
        echo '<p>Return Orders: <span id="return-orders">' . count($orders['return_orders']) . '</span></p>';
    }

    /**
     * Render the uninstall confirmation page.
     */
    public function render_uninstall_page() {
        ?>
        <div class="wrap">
            <h1>Uninstall WooShopify</h1>
            <p>Are you sure you want to uninstall the WooShopify plugin?</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="wooshopify_uninstall">
                <?php wp_nonce_field('wooshopify_uninstall_nonce', 'wooshopify_uninstall_nonce_field'); ?>
                <input type="submit" name="keep_data" class="button button-primary" value="Yes, keep my data">
                <input type="submit" name="delete_data" class="button button-secondary" value="No, delete my data">
            </form>
        </div>
        <?php
    }

    /**
     * Handle the uninstall process.
     */
    public function handle_uninstall() {
        if (!isset($_POST['wooshopify_uninstall_nonce_field']) || !wp_verify_nonce($_POST['wooshopify_uninstall_nonce_field'], 'wooshopify_uninstall_nonce')) {
            wp_die('Security check failed');
        }

        if (isset($_POST['delete_data'])) {
            update_option('wooshopify_uninstall_data', 'delete');
        } else {
            update_option('wooshopify_uninstall_data', 'keep');
        }

        // Redirect to plugins page
        wp_redirect(admin_url('plugins.php'));
        exit;
    }
}

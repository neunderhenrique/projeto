<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    WooShopify
 * @subpackage WooShopify/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WooShopify
 * @subpackage WooShopify/includes
 * @author     Your Name <email@example.com>
 */
class WooShopify {

    protected $loader;
    protected $wooshopify;
    protected $version;

    public function __construct() {
        if ( defined( 'WOOSHOPIFY_VERSION' ) ) {
            $this->version = WOOSHOPIFY_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->wooshopify = 'wooshopify';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

        // Additional hooks
        add_action('template_redirect', array($this, 'track_user_activity'));
        add_action('wp_ajax_ava_get_active_visitors_count', array($this, 'get_active_visitors_count'));
        add_action('wp_ajax_nopriv_ava_get_active_visitors_count', array($this, 'get_active_visitors_count'));
        add_action('admin_menu', array($this, 'add_live_view_subtab'));
    }

    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wooshopify-loader.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wooshopify-i18n.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wooshopify-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wooshopify-public.php';

        $this->loader = new WooShopify_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new WooShopify_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    private function define_admin_hooks() {
        $plugin_admin = new WooShopify_Admin( $this->get_wooshopify(), $this->get_version() );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
    }

    private function define_public_hooks() {
        $plugin_public = new WooShopify_Public( $this->get_wooshopify(), $this->get_version() );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
    }

    public function run() {
        $this->loader->run();
    }

    public function get_wooshopify() {
        return $this->wooshopify;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }

    public function track_user_activity() {
        if ($this->is_frontend_request()) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'active_visitors';
            $user_session_id = session_id();
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $wpdb->replace($table_name, array('session_id' => $user_session_id, 'ip_address' => $ip_address), array('%s', '%s'));
        }
    }

    public function is_frontend_request() {
        return !is_admin() && !wp_doing_ajax();
    }

    public function count_active_visitors() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'active_visitors';
        $result = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 SECOND)");
        return $result;
    }

    public function get_active_visitors_count() {
        echo $this->count_active_visitors();
        wp_die();
    }

    public function count_unique_sessions_today() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'active_visitors';

        $today_start_local = strtotime('today midnight');
        $today_end_local = strtotime('tomorrow midnight') - 1;
        $today_start_utc = gmdate('Y-m-d H:i:s', $today_start_local);
        $today_end_utc = gmdate('Y-m-d H:i:s', $today_end_local);

        $result = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE timestamp >= '$today_start_utc' AND timestamp <= '$today_end_utc'");

        return $result;
    }

    public function get_total_sales_for_today() {
        $total_sales = 0;

        if (class_exists('WooCommerce')) {
            $today_start = strtotime('today midnight');
            $today_end = strtotime('tomorrow midnight') - 1;

            $orders = wc_get_orders(array(
                'date_created' => '>=' . $today_start,
                'date_created' => '<=' . $today_end,
                'status' => 'completed',
                'type' => 'shop_order',
            ));

            foreach ($orders as $order) {
                $total_sales += floatval($order->get_total());
            }
        } else {
            $total_sales = 'WooCommerce not installed or active';
        }

        return $total_sales;
    }

    public function get_top_products_for_today() {
        $today_start = strtotime('today midnight');
        $today_end = strtotime('tomorrow midnight') - 1;

        $orders = wc_get_orders(array(
            'date_created' => '>=' . $today_start,
            'date_created' => '<=' . $today_end,
            'status' => 'completed',
            'type' => 'shop_order',
        ));

        $product_counts = array();

        foreach ($orders as $order) {
            $items = $order->get_items();

            foreach ($items as $item) {
                $product_id = $item->get_product_id();
                $quantity = $item->get_quantity();

                if (!isset($product_counts[$product_id])) {
                    $product_counts[$product_id] = 0;
                }

                $product_counts[$product_id] += $quantity;
            }
        }

        arsort($product_counts);

        $top_products = array_slice($product_counts, 0, 3, true);

        return $top_products;
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wooshopify-public-script', plugin_dir_url(__FILE__) . '../public/js/wooshopify-public.js', array('jquery'), $this->version, true);
        wp_localize_script('wooshopify-public-script', 'ava_ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function add_live_view_subtab() {
        add_submenu_page(
            'woocommerce',
            'Live View',
            'Live View',
            'manage_options',
            'active-visitors-live-view',
            array($this, 'render_live_view_page')
        );
    }

    public function render_live_view_page() {
        $active_visitors = $this->count_active_visitors();
        $total_sessions = $this->count_unique_sessions_today();
        $total_sales = $this->get_total_sales_for_today();
        $top_products = $this->get_top_products_for_today();

        echo '<h1>Live View</h1>';
        echo '<p>Visitors right now: <span id="active-visitors-count">' . $active_visitors . '</span></p>';
        echo '<p>Total Unique Sessions for the day: <span id="total-sessions">' . $total_sessions . '</span></p>';
        echo '<p>Total Sales for Today: <span id="total-sales">' . wc_price($total_sales) . '</span></p>';
        echo '<h2>Top 3 Products Sold Today</h2>';
        echo '<ul>';
        foreach ($top_products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            echo '<li>' . $product->get_name() . ': ' . $quantity . '</li>';
        }
        echo '</ul>';
    }
}
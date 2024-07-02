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
        add_action('wp_ajax_ava_get_live_view_data', array($this, 'get_live_view_data'));
        add_action('wp_ajax_nopriv_ava_get_live_view_data', array($this, 'get_live_view_data'));
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

            $wpdb->replace($table_name, array(
                'session_id' => $user_session_id,
                'ip_address' => $ip_address
            ), array('%s', '%s'));
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

    public function is_first_order($customer_id, $order_id) {
        if (!$customer_id) {
            return false; // Guest order
        }

        $customer_orders = wc_get_orders(array(
            'customer_id' => $customer_id,
            'status' => 'completed',
            'exclude' => array($order_id) // Exclude current order
        ));

        return empty($customer_orders);
    }

    public function get_first_and_return_orders_for_today() {
        $today_start = strtotime('today midnight');
        $today_end = strtotime('tomorrow midnight') - 1;

        $orders = wc_get_orders(array(
            'date_created' => '>=' . $today_start,
            'date_created' => '<=' . $today_end,
            'status' => 'completed',
            'type' => 'shop_order',
        ));

        $first_time_orders = array();
        $return_orders = array();

        foreach ($orders as $order) {
            $customer_id = $order->get_customer_id();
            if ($this->is_first_order($customer_id, $order->get_id())) {
                $first_time_orders[] = $order;
            } else {
                $return_orders[] = $order;
            }
        }

        return array(
            'first_time_orders' => $first_time_orders,
            'return_orders' => $return_orders
        );
    }

    public function get_live_view_data() {
        $active_visitors = $this->count_active_visitors();
        $total_sessions = $this->count_unique_sessions_today();
        $total_sales = $this->get_total_sales_for_today();
        $top_products = $this->get_top_products_for_today();
        $orders = $this->get_first_and_return_orders_for_today();

        // Get product names
        $product_names = array();
        foreach ($top_products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if ($product) {
                $product_names[$product_id] = $product->get_name();
            }
        }

        wp_send_json_success(array(
            'active_visitors' => $active_visitors,
            'total_sessions' => $total_sessions,
            'total_sales' => wc_price($total_sales),
            'top_products' => $top_products,
            'product_names' => $product_names,
            'first_time_orders' => count($orders['first_time_orders']),
            'return_orders' => count($orders['return_orders'])
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wooshopify-public-script', plugin_dir_url(__FILE__) . '../public/js/wooshopify-public.js', array('jquery'), $this->version, true);
        wp_localize_script('wooshopify-public-script', 'ava_ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));
    }
}

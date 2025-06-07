<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://saiakashneela.com/
 * @since      1.0.0
 *
 * @package    Food_Subscription_Engine
 * @subpackage Food_Subscription_Engine/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Food_Subscription_Engine {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      FSE_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'FSE_VERSION' ) ) {
            $this->version = FSE_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'food-subscription-engine';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - FSE_Loader. Orchestrates the hooks of the plugin.
     * - FSE_i18n. Defines internationalization functionality.
     * - FSE_Admin. Defines all hooks for the admin area.
     * - FSE_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once FSE_PLUGIN_PATH . 'includes/class-fse-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once FSE_PLUGIN_PATH . 'includes/class-fse-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once FSE_PLUGIN_PATH . 'admin/class-fse-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once FSE_PLUGIN_PATH . 'public/class-fse-public.php';

        $this->loader = new FSE_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the FSE_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new FSE_i18n();

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new FSE_Admin( $this->get_plugin_name(), $this->get_version() );

        // Add meta box for subscription options
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_subscription_meta_box' );
        // Save meta box data
        $this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'save_subscription_meta_box_data' );
        
        // Display selected dates in admin order details
        // $this->loader->add_action( 'woocommerce_admin_order_item_headers', $plugin_admin, 'add_subscription_dates_order_item_header' );
        // $this->loader->add_action( 'woocommerce_admin_order_item_values', $plugin_admin, 'display_subscription_dates_order_item_values', 10, 3 );
        // Note: The above hooks for admin display might not be needed if using $item->add_meta_data correctly.
        // WooCommerce typically handles displaying meta data added this way automatically.

        // Declare HPOS compatibility
        $this->loader->add_action( 'before_woocommerce_init', $plugin_admin, 'declare_hpos_compatibility' );

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new FSE_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        // Display "Subscribe" button on single product page
        $this->loader->add_action( 'woocommerce_after_add_to_cart_form', $plugin_public, 'display_subscribe_button' );

        // Display subscription modal in footer
        $this->loader->add_action( 'wp_footer', $plugin_public, 'display_subscription_modal' );

        // Handle variable product selection for subscribe button
        // $this->loader->add_action( 'wp_ajax_fse_check_variation_selection', $plugin_public, 'ajax_check_variation_selection' );
        // $this->loader->add_action( 'wp_ajax_nopriv_fse_check_variation_selection', $plugin_public, 'ajax_check_variation_selection' );

        // AJAX handler for getting product/variation price
        $this->loader->add_action( 'wp_ajax_fse_get_product_price', $plugin_public, 'ajax_get_product_price' );
        $this->loader->add_action( 'wp_ajax_nopriv_fse_get_product_price', $plugin_public, 'ajax_get_product_price' );

        // Calculate subscription price
        $this->loader->add_action( 'woocommerce_before_calculate_totals', $plugin_public, 'calculate_subscription_price', 20, 1 ); // Priority 20 to run after other price adjustments

        // Save subscription dates to cart item data
        $this->loader->add_filter( 'woocommerce_add_cart_item_data', $plugin_public, 'save_subscription_dates_to_cart_item', 10, 3 );

        // Display subscription dates in cart and checkout
        $this->loader->add_filter( 'woocommerce_get_item_data', $plugin_public, 'display_subscription_dates_in_cart', 10, 2 );

        // Add subscription dates to order item meta
        $this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $plugin_admin, 'add_subscription_dates_to_order_item_meta', 10, 4 );
        // Note: The hook above uses $plugin_admin because the method was added to FSE_Admin class.
        // If this logic is more public-facing, it could be moved to FSE_Public and hooked there.

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    FSE_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
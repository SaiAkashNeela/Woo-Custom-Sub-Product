<?php
/**
 * Plugin Name:       Food Subscription Engine
 * Plugin URI:        https://saiakashneela.com/plugins/food-subscription-engine/
 * Description:       Adds a flexible subscription feature to WooCommerce products, allowing customers to select multiple delivery dates with dynamic pricing.
 * Version:           1.0.0
 * Author:            Sai Akash Neela
 * Author URI:        https://saiakashneela.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       food-subscription-engine
 * Domain Path:       /languages
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define FSE_PLUGIN_FILE.
if ( ! defined( 'FSE_PLUGIN_FILE' ) ) {
    define( 'FSE_PLUGIN_FILE', __FILE__ );
}

// Define FSE_PLUGIN_PATH.
if ( ! defined( 'FSE_PLUGIN_PATH' ) ) {
    define( 'FSE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

// Define FSE_PLUGIN_URL.
if ( ! defined( 'FSE_PLUGIN_URL' ) ) {
    define( 'FSE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Define FSE_VERSION.
if ( ! defined( 'FSE_VERSION' ) ) {
    define( 'FSE_VERSION', '1.0.0' );
}

/**
 * The code that runs during plugin activation.
 */
function activate_food_subscription_engine() {
    // Activation code here.
}
register_activation_hook( __FILE__, 'activate_food_subscription_engine' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_food_subscription_engine() {
    // Deactivation code here.
}
register_deactivation_hook( __FILE__, 'deactivate_food_subscription_engine' );

/**
 * Begins execution of the plugin.
 */
function run_food_subscription_engine() {

    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'fse_woocommerce_not_active_notice' );
        return;
    }

    // HPOS Compatibility
    add_action( 'before_woocommerce_init', function() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    } );

    // Include main plugin class
    require_once FSE_PLUGIN_PATH . 'includes/class-food-subscription-engine.php';

    // Get an instance of the plugin class and run it.
    $plugin = new Food_Subscription_Engine();
    $plugin->run();

}
add_action( 'plugins_loaded', 'run_food_subscription_engine' );

/**
 * Admin notice if WooCommerce is not active.
 */
function fse_woocommerce_not_active_notice() {
    ?>
    <div class="error">
        <p><?php _e( 'Food Subscription Engine requires WooCommerce to be activated to function. Please install and activate WooCommerce.', 'food-subscription-engine' ); ?></p>
    </div>
    <?php
}

// TODO: Add further includes for admin, public, and includes functionalities.
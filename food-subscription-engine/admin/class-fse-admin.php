<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://saiakashneela.com/
 * @since      1.0.0
 *
 * @package    Food_Subscription_Engine
 * @subpackage Food_Subscription_Engine/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class FSE_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action( 'add_meta_boxes', array( $this, 'add_subscription_meta_box' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_subscription_meta_box_data' ) );
    }

    /**
     * Adds the meta box to the product edit screen.
     *
     * @since    1.0.0
     */
    public function add_subscription_meta_box() {
        add_meta_box(
            'fse_subscription_options',
            __( 'Food Subscription Options', 'food-subscription-engine' ),
            array( $this, 'render_subscription_meta_box' ),
            'product',
            'side',
            'default'
        );
    }

    /**
     * Renders the content of the meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The product object.
     */
    public function render_subscription_meta_box( $post ) {
        // Add a nonce field so we can check for it later.
        wp_nonce_field( 'fse_save_subscription_meta_box_data', 'fse_subscription_meta_box_nonce' );

        $is_subscription_enabled = get_post_meta( $post->ID, '_fse_subscription_enabled', true );

        echo '<p>';
        echo '<label for="fse_subscription_enabled">';
        echo '<input type="checkbox" id="fse_subscription_enabled" name="fse_subscription_enabled" value="yes" ' . checked( $is_subscription_enabled, 'yes', false ) . ' />';
        echo ' ' . __( 'Enable Subscription for this product', 'food-subscription-engine' );
        echo '</label>';
        echo '</p>';
    }

    /**
     * Saves the meta box data when the product is saved.
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the product being saved.
     */
    public function save_subscription_meta_box_data( $post_id ) {
        // Check if our nonce is set.
        if ( ! isset( $_POST['fse_subscription_meta_box_nonce'] ) ) {
            return;
        }

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['fse_subscription_meta_box_nonce'], 'fse_save_subscription_meta_box_data' ) ) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions.
        if ( isset( $_POST['post_type'] ) && 'product' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_product', $post_id ) ) {
                return;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }

        // Sanitize user input.
        $subscription_enabled = isset( $_POST['fse_subscription_enabled'] ) ? 'yes' : 'no';

        // Update the meta field in the database.
        update_post_meta( $post_id, '_fse_subscription_enabled', $subscription_enabled );
    }

    /**
     * Add selected subscription dates to order item meta data.
     * This hook is called when an order is created during checkout.
     *
     * @since 1.0.0
     * @param WC_Order_Item_Product $item          Order item object.
     * @param string                $cart_item_key Cart item key.
     * @param array                 $values        Values of the cart item.
     * @param WC_Order              $order         Order object.
     */
    public function add_subscription_dates_to_order_item_meta( $item, $cart_item_key, $values, $order ) {
        if ( isset( $values['fse_is_subscription'] ) && $values['fse_is_subscription'] === true && isset( $values['fse_selected_dates'] ) ) {
            $selected_dates = $values['fse_selected_dates'];
            $formatted_dates = array();
            foreach ( $selected_dates as $date_str ) {
                $date_obj = date_create( $date_str );
                if ( $date_obj ) {
                    $formatted_dates[] = date_format( $date_obj, 'D - jS M Y' ); // e.g., Tue - 3rd Jun 2024
                }
            }
            if ( ! empty( $formatted_dates ) ) {
                $item->add_meta_data( __( 'Delivery Dates', 'food-subscription-engine' ), implode( ', ', $formatted_dates ), true );
                // Optionally, store the raw dates array if needed for other processing
                // $item->add_meta_data( '_fse_selected_dates_raw', $selected_dates, true );
            }
        }
    }

    /**
     * Display subscription dates in the admin order details item meta.
     * WooCommerce 3.0+ uses a different way to display item meta, so this might not be strictly needed
     * if the meta is added correctly using add_meta_data. However, it can be used for custom formatting if required.
     *
     * This function is kept for potential future use or for older WC versions if compatibility becomes an issue.
     * For modern WC, `woocommerce_order_item_get_formatted_meta_data` filter is more appropriate for modifying display.
     */
    // public function display_subscription_dates_in_admin_order_meta( $item_id, $item, $_product ) {
    //     if ( is_admin() && $item->is_type('line_item') ) {
    //         $selected_dates_raw = $item->get_meta('_fse_selected_dates_raw'); // Assuming raw dates are stored
    //         if ( $selected_dates_raw && is_array($selected_dates_raw) ) {
    //             $formatted_dates = array();
    //             foreach ( $selected_dates_raw as $date_str ) {
    //                 $date_obj = date_create( $date_str );
    //                 if($date_obj){
    //                     $formatted_dates[] = date_format( $date_obj, 'D - jS M Y' );
    //                 }
    //             }
    //             if ( ! empty( $formatted_dates ) ) {
    //                 echo '<div class="wc-order-item-custom-meta"><strong>' . esc_html__( 'Delivery Dates:', 'food-subscription-engine' ) . '</strong><br>' . implode( '<br>', $formatted_dates ) . '</div>';
    //             }
    //         }
    //     }
    // }

    // HPOS Compatibility
    /**
     * Declare compatibility with High-Performance Order Storage (HPOS)
     *
     * @since 1.0.0
     */
    public function declare_hpos_compatibility() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', FSE_PLUGIN_FILE, true );
        }
    }
}
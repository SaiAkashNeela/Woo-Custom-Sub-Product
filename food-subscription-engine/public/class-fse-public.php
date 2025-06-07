<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://saiakashneela.com/
 * @since      1.0.0
 *
 * @package    Food_Subscription_Engine
 * @subpackage Food_Subscription_Engine/public
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class FSE_Public {

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
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, FSE_PLUGIN_URL . 'assets/css/food-subscription-engine-public.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, FSE_PLUGIN_URL . 'assets/js/food-subscription-engine-public.js', array( 'jquery' ), $this->version, true );
        wp_localize_script( $this->plugin_name, 'fse_params', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'fse_public_nonce' ),
            'i18n'     => array(
                'select_option_before_subscribe' => __( 'Please choose a product option (e.g., size) before customizing your subscription.', 'food-subscription-engine' ),
                'weekly' => __( 'Weekly', 'food-subscription-engine' ),
                'monthly' => __( 'Monthly', 'food-subscription-engine' ),
                'selected_dates' => __( 'Selected Dates:', 'food-subscription-engine' ),
                'total_price' => __( 'Total Price:', 'food-subscription-engine' ),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'no_dates_selected' => __( 'No dates selected', 'food-subscription-engine' ),
            )
        ) );
    }

    // Additional public methods will be added here for:
    // - Displaying the "Subscribe" button
    // - Handling variable product selection logic
    // - Rendering the subscription modal (weekly/monthly views)
    // - Dynamic price calculation
    // - Adding subscription data to cart item
    // - Displaying subscription data in cart/checkout
    // - Saving subscription data to order item meta

    /**
     * Displays the "Subscribe" button on the single product page if enabled for the product.
     *
     * @since    1.0.0
     */
    public function display_subscribe_button() {
        global $product;

        if ( ! $product ) {
            return;
        }

        $is_subscription_enabled = get_post_meta( $product->get_id(), '_fse_subscription_enabled', true );

        if ( 'yes' === $is_subscription_enabled ) {
            echo '<div class="fse-subscribe-button-container">';
            echo '<button type="button" class="button alt fse-subscribe-button" id="fse-subscribe-button-' . esc_attr( $product->get_id() ) . '" data-product-id="' . esc_attr( $product->get_id() ) . '">' . esc_html__( 'Subscribe', 'food-subscription-engine' ) . '</button>';
            echo '</div>';
        }
    }

    /**
     * Renders the subscription modal HTML structure.
     * This will be initially hidden and shown when the subscribe button is clicked.
     *
     * @since 1.0.0
     */
    public function display_subscription_modal() {
        // Only output modal if we are on a product page or if it's needed for other contexts
        // For now, let's assume it's always outputted and controlled by JS
        // A more optimized approach would be to only print this on relevant pages.
        ?>
        <div id="fse-subscription-modal" class="fse-modal" style="display:none;">
            <div class="fse-modal-content">
                <span class="fse-modal-close">&times;</span>
                <div class="fse-modal-header">
                    <h2 id="fse-modal-product-title"><?php esc_html_e( 'Customize Subscription', 'food-subscription-engine' ); ?></h2>
                </div>
                <div class="fse-modal-body">
                    <div class="fse-tabs">
                        <button class="fse-tab-link active" data-tab="weekly"><?php esc_html_e( 'Weekly', 'food-subscription-engine' ); ?></button>
                        <button class="fse-tab-link" data-tab="monthly"><?php esc_html_e( 'Monthly', 'food-subscription-engine' ); ?></button>
                    </div>

                    <div id="fse-weekly-tab" class="fse-tab-content active">
                        <!-- Weekly calendar will be rendered here by JS -->
                        <p><?php esc_html_e( 'Weekly selection placeholder.', 'food-subscription-engine' ); ?></p>
                    </div>

                    <div id="fse-monthly-tab" class="fse-tab-content">
                        <!-- Monthly calendar will be rendered here by JS -->
                        <p><?php esc_html_e( 'Monthly selection placeholder.', 'food-subscription-engine' ); ?></p>
                    </div>

                    <div class="fse-modal-summary">
                        <h3><?php esc_html_e( 'Summary', 'food-subscription-engine' ); ?></h3>
                        <div id="fse-selected-dates-summary">
                            <p><?php esc_html_e( 'No dates selected.', 'food-subscription-engine' ); ?></p>
                        </div>
                        <div id="fse-total-price-summary">
                            <strong><?php esc_html_e( 'Total Price:', 'food-subscription-engine' ); ?></strong> <span id="fse-calculated-price">--</span>
                        </div>
                    </div>
                </div>
                <div class="fse-modal-footer">
                    <button type="button" class="button alt" id="fse-confirm-subscription-button"><?php esc_html_e( 'Confirm Subscription', 'food-subscription-engine' ); ?></button>
                </div>
            </div>
        </div>

        <div id="fse-alert-modal" class="fse-modal fse-alert-modal" style="display:none;">
            <div class="fse-modal-content">
                <span class="fse-modal-close fse-alert-close">&times;</span>
                <div class="fse-modal-body">
                    <p id="fse-alert-message"></p>
                </div>
                 <div class="fse-modal-footer">
                    <button type="button" class="button alt fse-alert-close"><?php esc_html_e( 'OK', 'food-subscription-engine' ); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save selected subscription dates to cart item data.
     *
     * @since 1.0.0
     * @param array $cart_item_data
     * @param int   $product_id
     * @param int   $variation_id
     * @return array
     */
    public function save_subscription_dates_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
        if ( isset( $_POST['fse_is_subscription'] ) && $_POST['fse_is_subscription'] === 'true' && isset( $_POST['fse_selected_dates'] ) ) {
            $selected_dates_json = stripslashes( $_POST['fse_selected_dates'] );
            $selected_dates = json_decode( $selected_dates_json, true );

            if ( json_last_error() === JSON_ERROR_NONE && is_array( $selected_dates ) && ! empty( $selected_dates ) ) {
                $cart_item_data['fse_selected_dates'] = $selected_dates;
                // Mark this item as a subscription to differentiate if needed later
                $cart_item_data['fse_is_subscription'] = true; 
            }
        }
        return $cart_item_data;
    }

    /**
     * Recalculate product price based on selected subscription dates.
     *
     * @since 1.0.0
     * @param WC_Cart $cart
     */
    public function calculate_subscription_price( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item['fse_is_subscription'] ) && $cart_item['fse_is_subscription'] === true && isset( $cart_item['fse_selected_dates'] ) ) {
                $selected_dates = $cart_item['fse_selected_dates'];
                $num_selected_dates = count( $selected_dates );

                if ( $num_selected_dates > 0 ) {
                    // Get the base price of the product/variation
                    $_product = $cart_item['data'];
                    $base_price = $_product->get_price( 'edit' ); // Get price without previous filters
                    $new_price = $base_price * $num_selected_dates;
                    $cart_item['data']->set_price( $new_price );
                }
            }
        }
    }

    /**
     * AJAX handler to get product price (simple or variation).
     *
     * @since 1.0.0
     */
    public function ajax_get_product_price() {
        check_ajax_referer( 'fse_public_nonce', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $variation_id = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Product ID is missing.', 'food-subscription-engine' ) ) );
        }

        $product = wc_get_product( $variation_id ? $variation_id : $product_id );

        if ( ! $product ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'food-subscription-engine' ) ) );
        }

        wp_send_json_success( array( 
            'price' => $product->get_price(),
            'price_html' => $product->get_price_html()
        ) );
    }

    /**
     * Display selected subscription dates in cart and checkout.
     *
     * @since 1.0.0
     * @param array $item_data
     * @param array $cart_item
     * @return array
     */
    public function display_subscription_dates_in_cart( $item_data, $cart_item ) {
        if ( isset( $cart_item['fse_is_subscription'] ) && $cart_item['fse_is_subscription'] === true && isset( $cart_item['fse_selected_dates'] ) ) {
            $selected_dates = $cart_item['fse_selected_dates'];
            $formatted_dates = array();
            foreach ( $selected_dates as $date_str ) {
                // Ensure date string is handled correctly, might need timezone adjustment if critical
                $date_obj = date_create( $date_str ); 
                if($date_obj){
                    $formatted_dates[] = date_format( $date_obj, 'D - jS M' ); // e.g., Tue - 3rd Jun
                }
            }
            if ( ! empty( $formatted_dates ) ) {
                $item_data[] = array(
                    'key'     => __( 'Delivery Dates', 'food-subscription-engine' ),
                    'value'   => implode( '<br>', $formatted_dates ),
                    'display' => '',
                );
            }
        }
        return $item_data;
    }

}
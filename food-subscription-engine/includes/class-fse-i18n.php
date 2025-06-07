<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
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

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Food_Subscription_Engine
 * @subpackage Food_Subscription_Engine/includes
 * @author     Sai Akash Neela <contact@saiakashneela.com>
 */
class FSE_i18n {


    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain(
            'food-subscription-engine',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );

    }

}
<?php
/**
 * NEPB settings class file.
 *
 * @package NEPB/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * NEPB_Settings class.
 */
class NEPB_Settings {
	/**
	 * Class constructor.
	 */
	public function __construct() {
        add_filter( 'dibs_easy_settings', array( $this, 'extend_settings' ) );
	}

	/**
	 * Extends the settings for the Nets Easy plugin.
	 *
	 * @param array $settings The plugin settings.
	 * @return array $settings
	 */
	public function extend_settings( $settings ) {
		$settings['nepb_title']                      = array(
			'title'       => 'Nets Easy Pay Button Settings',
			'type'        => 'title',
			'description' => __( '', 'nets-easy-pay-button' ),
		);
		$settings['nepb_product_page_display']      = array(
			'title'   => __( 'Button on product page', 'nets-easy-pay-button' ),
			'type'    => 'checkbox',
			'label'   => __( 'If checked, Nets Easy Pay button will be displayed on all WooCommerce single product pages.', 'nets-easy-pay-button' ),
			'default' => 'no',
        );
        $settings['nepb_product_page_location']     = array(
			'title'   => __( 'Product page button placement', 'nets-easy-pay-button' ),
			'desc'    => __( 'Select where to display the button in your product pages', 'nets-easy-pay-button' ),
			'id'      => '',
			'default' => '25',
			'type'    => 'select',
			'options' => array(
				'4'  => __( 'Above Title', 'nets-easy-pay-button' ),
				'7'  => __( 'Between Title and Price', 'nets-easy-pay-button' ),
				'15' => __( 'Between Price and Excerpt', 'nets-easy-pay-button' ),
				'25' => __( 'Between Excerpt and Add to cart button', 'nets-easy-pay-button' ),
				'35' => __( 'Between Add to cart button and Product meta', 'nets-easy-pay-button' ),
				'45' => __( 'Between Product meta and Product sharing buttons', 'nets-easy-pay-button' ),
				'55' => __( 'After Product sharing-buttons', 'nets-easy-pay-button' ),
            ),
        );
		return $settings;
	}
}

new NEPB_Settings();

<?php
/**
 * NEPB helper class file.
 *
 * @package NEPB/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * NEPB_Helper class.
 */
class NEPB_Helper {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->nets_easy_settings      = get_option( 'woocommerce_dibs_easy_settings' );
		$this->payment_method          = 'dibs_easy';
		$this->payment_method_title    = $this->nets_easy_settings['title'];
		$this->testmode                = $this->nets_easy_settings['test_mode'];
		$this->wc_product_page_display = ( isset( $this->nets_easy_settings['nepb_product_page_display'] ) ) ? $this->nets_easy_settings['nepb_product_page_display'] : 'no';
	}

	/**
	 * Gets customer country.
	 *
	 * @return string
	 */
	public function get_customer_country() {
		// Try to use customer country if available.
		if ( ! empty( WC()->customer->get_shipping_country() ) && strlen( WC()->customer->get_shipping_country() ) === 2 ) {
			return WC()->customer->get_shipping_country( 'edit' );
		}
		$base_location = wc_get_base_location();
		$country       = $base_location['country'];
		return $country;
	}

	/**
	 * Get shipping methods for amount.
	 *
	 * @param object $product WooCommerce product.
	 * @param string $country Customer country.
	 * @param string $postcode Customer postcode.
	 *
	 * @return array
	 */
	public function get_shipping_methods_for_product( $product, $country, $postcode ) {
		$amount         = wc_get_price_including_tax( $product );
		$active_methods = array();
		$values         = array(
			'country'  => $country,
			'postcode' => $postcode,
			'amount'   => $amount,
		);
		// Fake product number to get a filled card....
		if ( method_exists( WC()->cart, 'get_cart' ) ) {
			WC()->cart->add_to_cart( '1' );
			WC()->shipping->calculate_shipping( $this->get_shipping_packages( $values, $product ) );
		}
		$shipping_methods = WC()->shipping->packages;
		if ( ! empty( $shipping_methods ) ) {
			foreach ( $shipping_methods[0]['rates'] as $id => $shipping_method ) {
				$active_methods[] = array(
					'id'       => $shipping_method->get_instance_id(),
					'type'     => $shipping_method->method_id,
					'provider' => $shipping_method->method_id,
					'name'     => $shipping_method->label,
					'price'    => number_format( $shipping_method->cost, 2, '.', '' ),
				);
			}
		}

		return $active_methods;
	}

	/**
	 * Get shipping packages.
	 *
	 * @param array  $value Product and customer values.
	 * @param object $product WooCommerce product.
	 *
	 * @return array
	 */
	public function get_shipping_packages( $value, $product ) {
		// We simulate the cart structure to calculate price for packages.
		$packages                                = array();
		$packages[0]['contents']                 = array(
			$product->get_id() => array(
				'key'               => $product->get_id(),
				'product_id'        => $product->get_id(),
				'variation_id'      => 0,
				'variation'         =>
				array(),
				'quantity'          => 1,
				'data_hash'         => $product->get_id(),
				'line_tax_data'     =>
				array(
					'subtotal' =>
					array(
						1 => $value['amount'],
					),
					'total'    =>
					array(
						1 => $value['amount'],
					),
				),
				'line_subtotal'     => $value['amount'],
				'line_subtotal_tax' => 0,
				'line_total'        => $value['amount'],
				'line_tax'          => 0,
				'data'              => $product,
			),
		);
		$packages[0]['contents_cost']            = $value['amount'];
		$packages[0]['applied_coupons']          = WC()->session->applied_coupon;
		$packages[0]['destination']['country']   = $value['country'];
		$packages[0]['destination']['state']     = '';
		$packages[0]['destination']['postcode']  = $value['postcode'];
		$packages[0]['destination']['city']      = '';
		$packages[0]['destination']['address']   = '';
		$packages[0]['destination']['address_2'] = '';

		return apply_filters( 'woocommerce_cart_shipping_packages', $packages );
	}


	/**
	 * Get product item tax rate.
	 *
	 * @param object $product WooCommerce product.
	 *
	 * @return string
	 */
	public function get_item_tax_rate( $product ) {
		if ( $product->is_taxable() ) {
			// Calculate tax rate.
			$_tax      = new WC_Tax();
			$tmp_rates = $_tax->get_rates( $product->get_tax_class() );
			$vat       = array_shift( $tmp_rates );
			if ( isset( $vat['rate'] ) ) {
				$item_tax_rate = round( $vat['rate'] * 100 );
			} else {
				$item_tax_rate = 0;
			}
		} else {
			$item_tax_rate = 0;
		}
		return intval( $item_tax_rate );
	}

	/**
	 * Gets payment method.
	 *
	 * @return string
	 */
	public function get_payment_method() {
		return $this->payment_method;
	}
	/**
	 * Gets payment method title.
	 *
	 * @return string
	 */
	public function get_payment_method_title() {
		return $this->payment_method_title;
	}

	/**
	 * Gets testmode.
	 *
	 * @return string
	 */
	public function get_testmode() {
		return $this->testmode;
	}

	/**
	 * Gets WooCommerce Product Page Display.
	 * Wether or not to displey the button on product pages.
	 *
	 * @return string
	 */
	public function get_wc_product_page_display() {
		return $this->wc_product_page_display;
	}
}

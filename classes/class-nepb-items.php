<?php
/**
 * NEPB items class file.
 *
 * @package NEPB/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * NEPB_Items class.
 */
class NEPB_Items {

	public static function get_items( $product, $quantity ) {
		$items = array();

		
		$items[] = self::get_item( $product, $quantity );
		

        // Get cart shipping
        /*
		if ( WC()->cart->needs_shipping() ) {
			$shipping = self::get_shipping();
			if ( null !== $shipping ) {
				$items[] = $shipping;
			}
		}
        */
		return $items;
	}

	public static function get_item( $product, $quantity ) {

        $country               = Krokedil_Easy_Payments()->helper->get_customer_country();
        $price_incl_tax        = intval( round( wc_get_price_including_tax( $product ) * 100, 2 ) );
        $price_excl_tax        = intval( round( wc_get_price_excluding_tax( $product ) * 100, 2 ) );
        $tax_amount            = $price_incl_tax - $price_excl_tax;
        $tax_rate              = Krokedil_Easy_Payments()->helper->get_item_tax_rate( $product );
        $wc_prices_include_tax = ( true === wc_prices_include_tax() ) ? 'yes' : 'no';
        $sku                   = empty( $product->get_sku() ) ? $product->get_id() : $product->get_sku();

		return array(
			'reference'        => self::get_sku( $product, $product->get_id() ),
			'name'             => wc_dibs_clean_name( $product->get_name() ),
			'quantity'         => $quantity ,
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => $price_excl_tax,
			'taxRate'          => $tax_rate,
			'taxAmount'        => $tax_amount,
			'grossTotalAmount' => $price_incl_tax * $quantity,
			'netTotalAmount'   => $price_excl_tax * $quantity,
		);
	}

	public static function get_fees( $fee ) {
		return array(
			'reference'        => 'fee|' . $fee->id,
			'name'             => wc_dibs_clean_name( $fee->name ),
			'quantity'         => 1,
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => intval( round( $fee->amount * 100, 2 ) ),
			'taxRate'          => intval( round( ( $fee->tax / $fee->amount ) * 10000, 2 ) ),
			'taxAmount'        => intval( round( $fee->tax * 100, 2 ) ),
			'grossTotalAmount' => intval( round( ( $fee->amount + $fee->tax ) * 100 ) ),
			'netTotalAmount'   => intval( round( $fee->amount * 100 ) ),
		);
	}

	public static function get_shipping() {
		WC()->cart->calculate_shipping();
		$packages        = WC()->shipping->get_packages();
		$chosen_methods  = WC()->session->get( 'chosen_shipping_methods' );
		$chosen_shipping = $chosen_methods[0];
		foreach ( $packages as $i => $package ) {
			foreach ( $package['rates'] as $method ) {
				if ( $chosen_shipping === $method->id ) {
					if ( $method->cost > 0 ) {
						return array(
							'reference'        => 'shipping|' . $method->id,
							'name'             => wc_dibs_clean_name( $method->label ),
							'quantity'         => 1,
							'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
							'unitPrice'        => intval( round( $method->cost * 100 ) ),
							'taxRate'          => intval( round( ( array_sum( $method->taxes ) / $method->cost ) * 10000, 2 ) ),
							'taxAmount'        => intval( round( array_sum( $method->taxes ) * 100 ) ),
							'grossTotalAmount' => intval( round( ( $method->cost + array_sum( $method->taxes ) ) * 100 ) ),
							'netTotalAmount'   => intval( round( $method->cost * 100 ) ),
						);
					} else {
						return array(
							'reference'        => 'shipping|' . $method->id,
							'name'             => wc_dibs_clean_name( $method->label ),
							'quantity'         => 1,
							'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
							'unitPrice'        => 0,
							'taxRate'          => 0,
							'taxAmount'        => 0,
							'grossTotalAmount' => 0,
							'netTotalAmount'   => 0,
						);
					}
				}
			}
		}
	}

	public static function get_sku( $product, $product_id ) {
		if ( get_post_meta( $product_id, '_sku', true ) !== '' ) {
			$part_number = $product->get_sku();
		} else {
			$part_number = $product->get_id();
		}
		return substr( $part_number, 0, 32 );
	}
}
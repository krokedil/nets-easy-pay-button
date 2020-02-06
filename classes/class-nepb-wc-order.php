<?php
/**
 * Handles order creation logic.
 *
 * @package NEPB/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Order class.
 */
class NEPB_WC_Order {

	/**
	 * Order is valid flag.
	 *
	 * @var boolean
	 */
	public $order_is_valid = true;
	/**
	 * Validation messages.
	 *
	 * @var array
	 */
	public $validation_messages = array();


	/**
	 * Handles WooCommerce checkout error, after Klarna order has already been created.
	 */
	public static function create_nepb_order(  $nets_payment_id, $product_id, $quantity ) {
		if ( ! empty( $nets_payment_id ) ) {
			$result = array();
			
			// Check if Woo order with Klarna order ID already exist.
			$query_args = array(
				'fields'      => 'ids',
				'post_type'   => wc_get_order_types(),
				'post_status' => array_keys( wc_get_order_statuses() ),
				'meta_key'    => '_dibs_payment_id',
				'meta_value'  => $nets_payment_id,
			);
			$orders     = get_posts( $query_args );
			if ( empty( $orders ) ) {

				$request    = new DIBS_Requests_Get_DIBS_Order( $nets_payment_id );
				$nets_order = $request->request();
				
				if ( ! is_wp_error( $nets_order ) ) {

					$order        = self::create_wc_order( $nets_order, $product_id, $quantity );
					NEPB()->logger->log( 'Created WC order ' . $order->get_id() );

					if ( is_object( $order ) ) {

                        /* translators: %s: Klarna order ID */
						$note = sprintf( __( 'Payment via Nets Easy Payment Button. Nets payment ID: %s', 'klarna-instant-shopping-for-wordpress' ), sanitize_key( $nets_payment_id ) );
                        $order->add_order_note( $note );
                        $result['redirect'] = $order->get_checkout_order_received_url();
						$result['result'] = 'success';
						
						update_post_meta( $order->get_id(), '_transaction_id', $nets_payment_id );
						update_post_meta( $order->get_id(), '_dibs_payment_id', $nets_payment_id );

                        if ( isset( $nets_order->payment->summary->reservedAmount ) || isset( $nets_order->payment->summary->chargedAmount ) || isset( $nets_order->payment->subscription->id ) ) {
							$order->payment_complete();
						}
						/*
                        if ( (int) round( $order->get_total() * 100 ) !== (int) $nets_order['data']['order']['amount']['amount'] ) {
                            $order->update_status( 'on-hold', sprintf( __( 'Order needs manual review, WooCommerce total and Nets total do not match. Nets order total: %s.', 'dibs-easy-for-woocommerce' ), $nets_order['data']['order']['amount']['amount'] ) );
						}
						*/
						
					} else {
                        NEPB()->logger->log( 'ERROR Tried to create WC order in create_nepb_order for Nets payment ID ' . $nets_payment_id . ' but something went wrong.' );
						$redirect_url = wc_get_endpoint_url( 'order-received', '', wc_get_page_permalink( 'checkout' ) );
                        $redirect_url = add_query_arg( 'nets_checkout_error', 'true', $redirect_url );
                        $result['redirect'] = $redirect_url;
                        $result['result'] = 'error';
					}
				}
			} else {
				// Order already exist in Woo. Redirect customer to the corresponding order received page.
				$order_id     = $orders[0];
				$order        = wc_get_order( $order_id );
                $result['redirect'] = $order->get_checkout_order_received_url();
                $result['result'] = 'success';
			}
			return $result;
		}
	}

	/**
	 * Create WooCommerce order.
	 *
	 * @param object $klarna_order Order data returned from Klarna.
	 *
	 * @return object
	 */
	public static function create_wc_order( $nets_order, $product_id, $quantity ) {
		$address = self::get_woo_adress_from_nets_order( $nets_order );
		NEPB()->logger->log( 'Got address from nets object ' );
		NEPB()->logger->log( wp_json_encode( $address ) );
		// Now we create the order.
		try {
			$order = wc_create_order();
			NEPB()->logger->log( 'Created WC order' );
			$order->set_address( $address, 'billing' );
			$order->set_address( $address, 'shipping' ); // TODO: Seperate shipping/billing?
			$order->set_created_via( 'nets_easy_payment_button' );

			$order->set_currency( sanitize_text_field( $nets_order->payment->orderDetails->currency ) );
			$order->set_payment_method( NEPB()->helper->get_payment_method() );
			$order->set_payment_method_title( NEPB()->helper->get_payment_method_title() );

			self::process_order_lines( $nets_order, $order, $product_id, $quantity );
			$order->calculate_totals();

			// Tie this order to a user if we have one.
			if ( email_exists( $address['email'] ) ) {
				$user = get_user_by( 'email', $address['email'] );
				$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', $user->ID ) );
			}

			/**
			 * Added to simulate WCs own order creation.
			 *
			 * TODO: Add the order content into a $data variable and pass as second parameter to the hook.
			 */
			do_action( 'woocommerce_checkout_create_order', $order, array() );

			$order_id = $order->save();

			/**
			 * Added to simulate WCs own order creation.
			 *
			 * TODO: Add the order content into a $data variable and pass as second parameter to the hook.
			 */
			do_action( 'woocommerce_checkout_update_order_meta', $order_id, array() );

		} catch ( Exception $e ) {
			NEPB()->logger->log( 'Unable to create order' );
			NEPB()->logger->log( 'Caught exception: ', $e->getMessage() );
		}
		return $order;
	}
	/**
	 * Process Woocommerce order lines.
	 *
	 * @param object $klarna_order Order data returned from Klarna.
	 * @param object $order WooCommerce order.
	 *
	 * @return void
	 */
	private static function process_order_lines( $nets_order, $order, $product_id, $quantity ) {

		$product  = ( wc_get_product( esc_attr( wp_unslash( $product_id ) ) ) );

		$cart_items = NEPB_Items::get_items( $product, $quantity );

		foreach ( $cart_items  as $cart_item ) {
			if ( strpos( $cart_item['reference'], 'shipping|' ) !== false ) {
				// Shipping
				$trimmed_cart_item_reference = str_replace( 'shipping|', '', $cart_item['reference'] );
				$method_id                   = substr( $trimmed_cart_item_reference, 0, strpos( $trimmed_cart_item_reference, ':' ) );
				$instance_id                 = substr( $trimmed_cart_item_reference, strpos( $trimmed_cart_item_reference, ':' ) + 1 );
				$rate                        = new WC_Shipping_Rate( $trimmed_cart_item_reference, $cart_item['name'], $cart_item['netTotalAmount'] / 100, array(), $method_id, $instance_id );
				$item                        = new WC_Order_Item_Shipping();
				$item->set_props(
					array(
						'method_title' => $rate->label,
						'method_id'    => $rate->id,
						'total'        => wc_format_decimal( $rate->cost ),
						'taxes'        => $rate->taxes,
						'meta_data'    => $rate->get_meta_data(),
					)
				);
				$order->add_item( $item );

			} elseif ( strpos( $cart_item['reference'], 'fee|' ) !== false ) {
				// Fee
				$trimmed_cart_item_id = str_replace( 'fee|', '', $cart_item['reference'] );
				$tax_class            = '';

				try {
					$args = array(
						'name'      => $cart_item['name'],
						'tax_class' => $tax_class,
						'subtotal'  => $cart_item['netTotalAmount'] / 100,
						'total'     => $cart_item['netTotalAmount'] / 100,
						'quantity'  => $cart_item['quantity'],
					);
					$fee  = new WC_Order_Item_Fee();
					$fee->set_props( $args );
					$order->add_item( $fee );
				} catch ( Exception $e ) {
					NEPB()->logger->log( 'Process order lines error - add fee error: ' . $e->getCode() . ' - ' . $e->getMessage() );
				}
			} else {
				// Product items
				if ( wc_get_product_id_by_sku( $cart_item['reference'] ) ) {
					$id = wc_get_product_id_by_sku( $cart_item['reference'] );
				} else {
					$id = $cart_item['reference'];
				}

				try {
					$product = wc_get_product( $id );

					$args = array(
						'name'         => $product->get_name(),
						'tax_class'    => $product->get_tax_class(),
						'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
						'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
						'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
						'subtotal'     => ( $cart_item['netTotalAmount'] ) / 100,
						'total'        => ( $cart_item['netTotalAmount'] ) / 100,
						'quantity'     => $cart_item['quantity'],
					);
					$item = new WC_Order_Item_Product();
					$item->set_props( $args );
					$item->set_backorder_meta();
					$item->set_order_id( $order->get_id() );
					$item->calculate_taxes();
					$item->save();
					$order->add_item( $item );
				} catch ( Exception $e ) {
					NEPB()->logger->log( 'Process order lines error - add to cart error: ' . $e->getCode() . ' - ' . $e->getMessage() );
				}
			}
		}
	}

	
	/**
	 * Get customer address formatted for WooCommerce from Klarna order.
	 *
	 * @param object $klarna_order Order data returned from Klarna.
	 *
	 * @return array
	 */
	public static function get_woo_adress_from_nets_order( $nets_order ) {

        if ( array_key_exists( 'name', $nets_order->payment->consumer->company ) ) {
			$type     = 'company';
			$customer = $nets_order->payment->consumer->company;
		} else {
			$type     = 'person';
			$customer = $nets_order->payment->consumer->privatePerson;
        }
        
		$adress = array(
			'first_name' => ( 'person' === $type ) ? $customer->firstName : $customer->contactDetails->firstName,
			'last_name'  => ( 'person' === $type ) ? $customer->lastName : $customer->contactDetails->lastName,
			'email'      => ( 'person' === $type ) ? $customer->email : $customer->contactDetails->email,
			'phone'      => ( 'person' === $type ) ? $customer->phoneNumber->number : $customer->contactDetails->phoneNumber->number,
			'address_1'  => $nets_order->payment->consumer->shippingAddress->addressLine1,
			'city'       => $nets_order->payment->consumer->shippingAddress->city,
			'postcode'   => $nets_order->payment->consumer->shippingAddress->postalCode,
			'country'    => dibs_get_iso_2_country( $nets_order->payment->consumer->shippingAddress->country ),
        );
        if ( 'company' === $type ) {
            $adress['company'] = $customer->name;
        }
		return $adress;
	}

	
}
new NEPB_WC_Order();

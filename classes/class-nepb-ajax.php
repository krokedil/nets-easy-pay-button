<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class NEPB_Ajax extends WC_AJAX {
	public $private_key;

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		$ajax_events = array(
			'get_checkout_session'    => true,
			'customer_adress_updated' => true,
			'process_woo_order'    => true,
		);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
				// WC AJAX can be used for frontend ajax requests.
				add_action( 'wc_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Create button ID.
	 */
	public static function get_checkout_session() {

		// Nonce check.
		// check_ajax_referer( 'kiswp-admin', 'security' );
		if ( isset( $_POST['nepb_session_id'] ) && ! empty( $_POST['nepb_session_id'] ) ) {

			$request = new DIBS_Requests_Update_DIBS_Order( $_POST['nepb_session_id'] );
			
			$request = $request->request();
			if( 'SUCCESS' === $request ) {
				$request = new stdClass;
				$request->paymentId = $_POST['nepb_session_id'];
			}
		} else {
			$request = new DIBS_Requests_Create_DIBS_Order();
			$request = json_decode( $request->request() );
		}

	

		// Check if we got errors. Return the error object if something is wrong.
		if ( is_wp_error( $request ) ) {
			$data = array(
				'result'           => 'error',
				'message'          => $request->get_error_message(),
				'nepb_session_id'  => '',
				'checkout_snippet' => '',
			);

		} elseif ( isset( $request->errors ) ) {
			foreach ( $request->errors as $error ) {
				$error_message = $error[0];
			}
			$data = array(
				'result'           => 'error',
				'message'          => $error_message,
				'nepb_session_id'  => $_POST['nepb_session_id'],
				'checkout_snippet' => '',
			);
		} else {
			// All good.
			$data = array(
				'result'          => 'success',
				'message'         => 'Checkout created',
				'nepb_session_id' => $request->paymentId,
			);
		}

		wp_send_json_success( $data );
		wp_die();

	}


	/**
	 * Create button ID.
	 */
	public static function process_woo_order() {

		$payment_id = $_REQUEST['payment_id'];
		$product_id  = ( esc_attr( wp_unslash( $_POST['product_id'] ) ) );
		$quantity = intval( esc_attr( wp_unslash( $_POST['quantity'] ) ) );

		// Nonce check.
		// check_ajax_referer( 'kiswp-admin', 'security' );
		$request = NEPB_WC_Order::create_nepb_order( $payment_id, $product_id, $quantity );

		// Check if we got errors. Return the error object if something is wrong.
		if ( is_wp_error( $request ) ) {
			$data = array(
				'result'           => 'error',
				'message'          => $request->get_error_message(),
				'payment_id'  => $payment_id,
			);

		} elseif ( isset( $request->errors ) ) {
			foreach ( $request->errors as $error ) {
				$error_message = $error[0];
			}
			$data = array(
				'result'           => 'error',
				'message'          => $error_message,
				'nepb_session_id'  => '',
				'checkout_snippet' => '',
			);
		} else {
			// All good.
			$data = array(
				'result'          => 'success',
				'message'         => 'Checkout created',
				'payment_id'  => $payment_id,
				'redirect_url' => $request['redirect'],
			);
		}

		wp_send_json_success( $data );
		wp_die();

	}

	/**
	 * Customer address updated - triggered when address-changed event is fired
	 */
	public static function customer_adress_updated() {

		/*
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'dibs_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}
		*/
		$update_needed      = 'no';
		$must_login         = 'no';
		$must_login_message = apply_filters( 'woocommerce_registration_error_email_exists', __( 'An account is already registered with your email address. Please log in.', 'woocommerce' ) );

		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		// Get customer data from DIBS
		$country   = dibs_get_iso_2_country( $_REQUEST['address']['countryCode'] );
		$post_code = $_REQUEST['address']['postalCode'];

		// If customer is not logged in and this is a subscription purchase - get customer email from DIBS.
		if ( ! is_user_logged_in() && ( ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) || 'no' === get_option( 'woocommerce_enable_guest_checkout' ) ) ) {
			$payment_id = WC()->session->get( 'dibs_payment_id' );
			$request    = new DIBS_Requests_Get_DIBS_Order( $payment_id );
			$response   = $request->request();
			$email      = $response->payment->consumer->privatePerson->email;
			if ( email_exists( $email ) ) {
				// Email exist in a user account, customer must login.
				$must_login = 'yes';
			}
		}

		if ( $country ) {
			// If country is changed then we need to trigger an cart update in the DIBS Easy Checkout
			if ( WC()->customer->get_billing_country() !== $country ) {
				$update_needed = 'yes';
			}

			// If country is changed then we need to trigger an cart update in the DIBS Easy Checkout
			if ( WC()->customer->get_shipping_postcode() !== $post_code ) {
				$update_needed = 'yes';
			}
			// Set customer data in Woo
			WC()->customer->set_billing_country( $country );
			WC()->customer->set_shipping_country( $country );
			WC()->customer->set_billing_postcode( $post_code );
			WC()->customer->set_shipping_postcode( $post_code );
			WC()->customer->save();

			WC()->cart->calculate_totals();

		}
		$response = array(
			'updateNeeded'     => $update_needed,
			'country'          => $country,
			'postCode'         => $post_code,
			'mustLogin'        => $must_login,
			'mustLoginMessage' => $must_login_message,
		);
		wp_send_json_success( $response );
		wp_die();
	}


}

NEPB_Ajax::init();

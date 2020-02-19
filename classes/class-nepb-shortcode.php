<?php
/**
 * WooCommerce button class file.
 *
 * @package NEPB/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * WooCommerce product page button class.
 */
class NEPB_Shortcode {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		add_shortcode( 'easy_payment_button', array( $this, 'init_and_render_button' ) );
		add_filter( 'dibs_easy_create_order_args', array( $this, 'nepb_create_order_args' ) );
		add_filter( 'dibs_easy_update_order_args', array( $this, 'nepb_update_order_args' ) );
		
		add_action( 'woocommerce_thankyou_dibs_easy', array( $this, 'reset_nepb_session' ) );
	}

	public function reset_nepb_session() {

		echo '<script>sessionStorage.removeItem("nepb_session_id")</script>';
		echo '<script>sessionStorage.removeItem("nepbRedirectUrl")</script>';

	}

	/**
	 * Init and render KIS button.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Shortcode output.
	 */
	public function init_and_render_button( $atts ) {

		// Default attributes.
		$atts = shortcode_atts(
			array(
				'instance-id'    => 'nepb-shortcode-1',
				'wc-product-id'  => '',
				'button-label'   => 'Buy now',
				'quantity-field' => '',
			),
			$atts,
			'net_easy_payment_button'
		);

		if ( isset( $_GET['paymentid'] ) ) {
			$paymentid = $_GET['paymentid'];
		} elseif ( isset( $_GET['paymentId'] ) ) { 
			$paymentid = $_GET['paymentId'];
		} else {
			$paymentid = null;
		}

		if ( isset( $_GET['nepb_checkout'] ) && 'yes' === $_GET['nepb_checkout'] ) {
			$nepb_checkout = $_GET['nepb_checkout'];
		} else {
			$nepb_checkout = null;
		}

		if ( isset( $_GET['nepb_checkout'] ) && 'yes' === $_GET['nepb_checkout'] && isset( $_GET['paymentid'] ) && ! isset( $_GET['paymentFailed'] ) ) {
			$nepb_checkout_complete = 'yes';
		} else {
			$nepb_checkout_complete = 'no';
		}

		$params = array(
			'ajax_url'                    => admin_url( 'admin-ajax.php' ),
			'current_url'                 => get_permalink(),
			'private_key'                 => wc_dibs_get_private_key(),
			'locale'                      => wc_dibs_get_locale(),
			'paymentid'						=> $paymentid,
			'nepb_checkout'					=> $nepb_checkout,
			'nepb_checkout_complete'		=> $nepb_checkout_complete,
			'get_checkout_session_url'    => WC_AJAX::get_endpoint( 'get_checkout_session' ),
			'customer_adress_updated_url' => WC_AJAX::get_endpoint( 'customer_adress_updated' ),
			'process_woo_order_url' => WC_AJAX::get_endpoint( 'process_woo_order' ),

		);
		wp_localize_script(
			'net_easy_payment_button',
			'nepb_params',
			$params
		);
		wp_enqueue_script( 'net_easy_payment_button' );
		wp_enqueue_style( 'net_easy_payment_button' );
		$testmode   = 'yes' === $this->dibs_settings['test_mode'];
		$script_url = $testmode ? 'https://test.checkout.dibspayment.eu/v1/checkout.js?v=1' : 'https://checkout.dibspayment.eu/v1/checkout.js?v=1';
		wp_enqueue_script( 'dibs-script', $script_url, array( 'jquery' ) );

		ob_start();
		$this->render_button( $atts );
		return ob_get_clean();

	}

	/**
	 * Render KIS button html tag.
	 *
	 * @param string $instance_id The html tag instance ID.
	 */
	public function render_button( $atts ) {
		?>
		<div id="nepb-checkout">
			<?php
			if ( 'yes' === $atts['quantity-field'] ) {
				$product = wc_get_product( $atts['wc-product-id'] );
				woocommerce_quantity_input(
					array(
						'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
						'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
						'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
					)
				);
			}
			?>
			<div id="nepb-checkout-button" class="button" 
			data-wc-product-id="<?php esc_attr_e( $atts['wc-product-id'] ); ?>" 
			data-instance-id="<?php esc_attr_e( $atts['instance-id'] ); ?>">
			<?php esc_attr_e( $atts['button-label'] ); ?>
			</div>
		</div>
		<div class="nepb-checkout-modal">
			<div class="nepb-checkout-modal-box">
				<div class="nepb-checkout-modal-content">
				</div>
				<span class="nepb-close-checkout-modal">&times;</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Nets order create args.
	 *
	 * @param array $request_args The Nets request arguments.
	 * @return array
	 */
	public function nepb_create_order_args( $request_args ) {

		if ( isset( $_POST['action'] ) && 'nepb_get_checkout_session' === $_POST['action'] ) {
			$product  = ( wc_get_product( esc_attr( wp_unslash( $_POST['product_id'] ) ) ) );
			$quantity = intval( esc_attr( wp_unslash( $_POST['quantity'] ) ) );

			if ( $product ) {
				$current_url      = $_POST['current_url'];
				$confirmation_url = add_query_arg(
					array(
						'nepb_confirm'    => 'yes',
					),
					$current_url
				);
				$checkout_url     = add_query_arg(
					array(
						'nepb_checkout' => 'yes',
					),
					$current_url
				);

				unset( $request_args['order'] );
				
				$amount = 0;
				$items = NEPB_Items::get_items( $product, $quantity, $this->get_purchase_country() );
				foreach ( $items as $item ) {
					$amount += $item['grossTotalAmount']; 
				}
				$request_args['order'] = [
					'amount'    => $amount,
					'items'		=> $items,
					'currency'  => get_woocommerce_currency(),
					'shipping'  => [
						[
							'costSpecified' => false,
						],
					],
					'reference' => '1',
				];

				$request_args['checkout']        = DIBS_Requests_Checkout::get_checkout( 'embedded' );
				$request_args['checkout']['url'] = $checkout_url;
				$request_args['checkout']['returnUrl'] = $checkout_url;
				$request_args['checkout']['shipping'] = [
					'countries' => [],
					'merchantHandlesShippingCost' => false,
				];

				$request_args['notifications'] = DIBS_Requests_Notifications::get_notifications();
			}
		}
		return $request_args;
	}

	/**
	 * Nets order update args.
	 *
	 * @param array $request_args The Nets request arguments.
	 * @return array
	 */
	public function nepb_update_order_args( $request_args ) {

		if ( isset( $_POST['action'] ) && 'nepb_get_checkout_session' === $_POST['action'] ) {
			$product  = ( wc_get_product( esc_attr( wp_unslash( $_POST['product_id'] ) ) ) );
			$quantity = intval( esc_attr( wp_unslash( $_POST['quantity'] ) ) );
			$amount = 0;
			$items = NEPB_Items::get_items( $product, $quantity, $this->get_purchase_country() );
			foreach ( $items as $item ) {
				$amount += $item['grossTotalAmount']; 
			}
			if ( $product ) {

				$request_args = [
					'amount'    => $amount,
					'items'		=> $items,
					'currency'  => get_woocommerce_currency(),
					'shipping'  => [
						'costSpecified' => false,
						],
					'reference' => '1',
				];

			}
		}
		return $request_args;
	}

	/**
	 * Gets country for Nets purchase.
	 *
	 * @return string
	 */
	public function get_purchase_country() {
		// Try to use customer country if available.
		if ( ! empty( WC()->customer->get_billing_country() ) && strlen( WC()->customer->get_billing_country() ) === 2 ) {
			return WC()->customer->get_billing_country( 'edit' );
		}

		$base_location = wc_get_base_location();
		$country       = $base_location['country'];

		return $country;
	}

}

new NEPB_Shortcode();

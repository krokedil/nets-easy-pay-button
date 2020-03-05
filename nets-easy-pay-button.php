<?php // phpcs:ignore
/*
 * Plugin Name: Nets Easy Pay Button
 * Plugin URI: https://krokedil.com
 * Description: Adds a payment button directly on product pages or via shortcode.
 * Version: 0.2.0
 * Author: Krokedil
 * Author URI: https://krokedil.com
 * Text Domain: nets-easy-pay-button
 * Domain Path: /languages
 *
 * WC requires at least: 3.5.0
 * WC tested up to: 3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'NEPB_VERSION', '0.2.0' );
define( 'NEPB_MAIN_FILE', __FILE__ );
define( 'NEPB_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'NEPB_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

if ( ! class_exists( 'Nets_Easy_Pay_Button' ) ) {
	/**
	 * Main class for the plugin.
	 */
	class Nets_Easy_Pay_Button {
		/**
		 * The reference the *Singleton* instance of this class.
		 *
		 * @var $instance
		 */
		protected static $instance;
		/**
		 * Class constructor.
		 */
		public function __construct() {
			// Initiate the plugin.
			add_action( 'plugins_loaded', array( $this, 'init' ), 1000 );
			add_action( 'plugins_loaded', array( $this, 'check_version' ) );
		}
		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return self::$instance The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		/**
		 * Private clone method to prevent cloning of the instance of the
		 * *Singleton* instance.
		 *
		 * @return void
		 */
		private function __clone() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Nope' ), '1.0' );
		}
		/**
		 * Private unserialize method to prevent unserializing of the *Singleton*
		 * instance.
		 *
		 * @return void
		 */
		private function __wakeup() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Nope' ), '1.0' );
		}
		/**
		 * Initiates the plugin.
		 *
		 * @return void
		 */
		public function init() {
			load_plugin_textdomain( 'nets-easy-pay-button', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			// Only run if WooCommerce and Nets Easy is activated.
			if ( ! class_exists( 'WC_Payment_Gateway' ) || ! class_exists( 'DIBS_Easy' ) ) {
				return;
			}

			$this->include_files();

			// Load scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

			// Add info to plugin page.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

			$this->logger = new NEPB_Logging();
			$this->helper = new NEPB_Helper();
		}

		/**
		 * Includes the files for the plugin
		 *
		 * @return void
		 */
		public function include_files() {
			include_once NEPB_PLUGIN_PATH . '/classes/class-nepb-logging.php';
			include_once NEPB_PLUGIN_PATH . '/classes/class-nepb-settings.php';

			include_once NEPB_PLUGIN_PATH . '/classes/class-nepb-callbacks.php';
			include_once NEPB_PLUGIN_PATH . '/classes/class-nepb-shortcode.php';
			include_once NEPB_PLUGIN_PATH . '/classes/class-nepb-product-page-button.php';

			include_once NEPB_PLUGIN_PATH . '/classes/class-nepb-ajax.php';

			include_once NEPB_PLUGIN_PATH . '/classes/class-nepb-wc-order.php';
			include_once NEPB_PLUGIN_PATH . '/classes/class-nepb-helper.php';
			include_once NEPB_PLUGIN_PATH . '/classes/class-nepb-items.php';
		}

		/**
		 * Loads the needed scripts for PaysonCheckout.
		 */
		public function load_scripts() {
			global $post;
			if ( is_product() || ( isset( $post ) && has_shortcode( $post->post_content, 'easy_payment_button' ) ) ) {
				// Checkout script.
				wp_register_script(
					'net_easy_payment_button',
					NEPB_PLUGIN_URL . '/assets/js/nets-easy-payment-button.js',
					array( 'jquery' ),
					NEPB_VERSION,
					false
				);

				wp_register_style(
					'net_easy_payment_button',
					NEPB_PLUGIN_URL . '/assets/css/nets-easy-payment-button.css',
					array(),
					NEPB_VERSION
				);
			}
		}

		/**
		 * Checks the plugin version.
		 *
		 * @return void
		 */
		public function check_version() {
			require NEPB_PLUGIN_PATH . '/includes/plugin_update_check.php';
			$KernlUpdater = new PluginUpdateChecker_2_0 (
				'https://kernl.us/api/v1/updates/5e609f2e61ed601988fa2599/',
				__FILE__,
				'nets-easy-pay-button',
				1
			);
		}

		/**
		 * Adds plugin action links
		 *
		 * @since 1.0.4
		 */
		public function plugin_action_links( $links ) {

			$plugin_links = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dibs_easy' ) . '">' . __( 'Settings', 'dibs-easy-for-woocommerce' ) . '</a>',
				'<a href="https://docs.krokedil.com/article/333-nets-easy-pay-button">' . __( 'Docs', 'dibs-easy-for-woocommerce' ) . '</a>',
				'<a href="https://krokedil.se/support/">' . __( 'Support', 'dibs-easy-for-woocommerce' ) . '</a>',
			);
			return array_merge( $plugin_links, $links );
		}
	}
	Nets_Easy_Pay_Button::get_instance();
	/**
	 * Main instance PaysonCheckout_For_WooCommerce.
	 *
	 * Returns the main instance of PaysonCheckout_For_WooCommerce.
	 *
	 * @return PaysonCheckout_For_WooCommerce
	 */
	function NEPB() { // phpcs:ignore
		return Nets_Easy_Pay_Button::get_instance();
	}
}

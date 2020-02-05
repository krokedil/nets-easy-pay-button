<?php
/**
 * Handles callbacks for the plugin.
 *
 * @package NEPB/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Callback class.
 */
class NEPB_Callbacks {

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
	 * Class constructor.
	 */
	public function __construct() {
		// add_action( 'init', array( $this, 'create_kep_order' ) );
	}


}
new NEPB_Callbacks();

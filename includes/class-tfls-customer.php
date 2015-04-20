<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'TFLS_Customer' ) ) :

	/**
	 * TFLS_Customer
	 *
	 * Store TFLS frontend data Handler
	 *
	 * @class       TFLS_Customer
	 * @version     1.0.0
	 * @category    Class
	 * @author      Nicholas Byfleet, oscargare
	 */
	class TFLS_Customer {

		/** Stores customer price based on country data as an array */
		protected $_data;

		/** Stores bool when data is changed */
		private $_changed = false;

		/**
		 * Constructor for the tfls_customer class loads the data.
		 *
		 * @access public
		 */

		public function __construct() {

			$this->_data = WC()->session->get( 'tfls_customer' );

			if ( empty( $this->_data ) || ! in_array( WC()->customer->country, $this->countries ) || ( $this->timestamp < get_option( 'wc_tfls_timestamp' ) ) ) {

				$this->set_country( WC()->customer->country );
			}

			if ( ! WC()->session->has_session() ) {
				WC()->session->set_customer_session_cookie( true );
			}

			// When leaving or ending page load, store data
			add_action( 'shutdown', array( $this, 'save_data' ), 10 );
		}

		/**
		 * save_data function.
		 *
		 * @access public
		 */
		public function save_data() {

			if ( $this->_changed ) {
				WC()->session->set( 'tfls_customer', $this->_data );
			}

		}

		/**
		 * __get function.
		 *
		 * @access public
		 *
		 * @param string $property
		 *
		 * @return string
		 */
		public function __get( $property ) {

			$value = isset( $this->_data[ $property ] ) ? $this->_data[ $property ] : '';

			if ( $property === 'countries' && ! $value ) {
				$value = array();
			}

			return $value;
		}


		/**
		 * Sets tfls data form country.
		 *
		 * @access public
		 *
		 * @param mixed $country
		 */
		public function set_country( $country ) {

			$this->_data = array();

			foreach ( TFLS()->get_regions() as $key => $group_data ) {

				if ( in_array( $country, $group_data['countries'] ) ) {
					$this->_data = array_merge( $group_data, array( 'group_key' => $key, 'timestamp' => time() ) );
					break;
				}

			}

			$this->_changed = true;

		}

	}

endif;

?>
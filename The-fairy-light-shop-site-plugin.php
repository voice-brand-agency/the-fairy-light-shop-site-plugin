<?php
/*
Plugin Name: The Fairy Light Shop Site Plugin
Plugin URI: http://voice.co.nz/
Description: Our take on WooCommerce Product Price Based on Countries (https://github.com/wp-plugins/woocommerce-product-price-based-on-countries) for TFLS
Version: 1.0
Author: Nicholas Byfleet
Author URI: http://nbyfleet.com/
License: A http://www.binpress.com/license/view/l/a59b50bad876cc0b780d0d2652b39ca4
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) :

	if ( ! class_exists( 'WC_Fairy_Light_Shop' ) ) : // Make sure that we're not stepping on anyone's toes

		/**
		 * Main WC Fairy Light Shop Class
		 *
		 * @class WC_Fairy_Light_Shop
		 * @version 1.0.0
		 */
		class WC_Fairy_Light_Shop {

			/**
			 * @var The single instance of the class
			 */
			protected static $_instance = null;

			/**
			 * @var $regions
			 */
			protected $regions = null;

			/**
			 * Main WC_Fairy_Light_Shop Instance
			 *
			 * @static
			 * @see TFLS()
			 */
			public static function instance() {

				if ( is_null( self::$_instance ) ) {
					self::$_instance = new self();
				}

				return self::$_instance;
			}

			public function __construct() {

				$upload_dir = wp_upload_dir();

				// I presume these will be used to help administer the monthly refresh of the GeoIP database
				define( 'TFLS_FILE', __FILE__ );
				define( 'TFLS_UPLOAD_DIR', $upload_dir['basedir'] . '/the_fairy_light_shop_site_plugin' );
				define( 'TFLS_GEOIP_DB', TFLS_UPLOAD_DIR . '/GeoLite2-Country.mmdb' );

				include_once 'includes/tfls-functions.php';

				if ( $this->is_request( 'admin' ) ) {
					include_once 'includes/class-tfls-admin.php';
				} elseif ( $this->is_request( 'frontend' ) ) {
					require_once 'includes/class-tfls-frontend.php';
				}
			}

			/**
			 * Get regions
			 * @return array
			 */
			public function get_regions() {

				if ( is_null( $this->regions ) ) {
					$regions = get_option( '_oga_tfls_countries_groups' );

					if ( ! $regions ) {
						$regions = array();
					}

					$this->regions = $regions;
				}

				return $this->regions;
			}

			/**
			 * What type of request is this?
			 *
			 * @param string $type frontend or admin
			 *
			 * @return bool
			 */
			private function is_request( $type ) {

				$is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

				switch ( $type ) {

					case 'admin' :
						$ajax_allow_actions = array( 'woocommerce_add_variation' );

						return ( is_admin() && ! $is_ajax ) || ( is_admin() && $is_ajax && isset( $_POST['action'] ) && in_array( $_POST['action'], $ajax_allow_actions ) );

					case 'frontend' :
						return ! $this->is_request( 'bot' ) && file_exists( TFLS_GEOIP_DB ) && ( ! is_admin() || ( is_admin() && $is_ajax ) ) && ! defined( 'DOING_CRON' );
					case 'bot':
						$user_agent = strtolower( $_SERVER['HTTP_USER_AGENT'] );

						return preg_match( "/googlebot|adsbot|yahooseeker|yahoobot|msnbot|watchmouse|pingdom\.com|feedfetcher-google/", $user_agent );
				}
			}
		} // End class

		/**
		 * Returns the main instance of WC_Fairy_Light_Shop so as to avoid the need to use globals.
		 *
		 * @return WC_Fairy_Light_Shop
		 */
		function TFLS() {

			return WC_Fairy_Light_Shop::instance();
		}

		$wc_the_fairy_light_shop = TFLS();

	endif; // ! class_exists ( 'WC_The_Fairy_Light_Shop' )

else :

	add_action( 'admin_init', 'oga_tfls_deactivate' );

	function oga_tfls_deactivate() {

		deactivate_plugins( plugin_basename( __FILE__ ) );

	}

	add_action( 'admin_notices', 'oga_tfls_no_woocommerce_admin_notice' );

	function oga_tfls_no_woocommerce_admin_notice() {

		?>
		<div class="updated">
			<p><strong>The Fairy Light Shop Site Plugin </strong>has been deactivated because <a
					href="http://woothemes.com/">Woocommerce plugin</a> is required</p>
		</div>
	<?php
	}


endif;

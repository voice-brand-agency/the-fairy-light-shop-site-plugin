<?php

require_once '../vendor/autoload.php';

use GeoIp2\Database\Reader;

/**
 * WooCommerce functions for the Fairy Light Shop
 *
 * These are general functions available on both the front end and back end.
 */

// As always, we want to exit if someone attempts to access the script directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return country IsoCode from IP address.
 *
 * @return string
 */
if ( ! function_exists( 'get_country_from_ip' ) ) {
	function get_country_from_ip( $ip ) {

		$isoCode = '';

		try {
			$reader  = new Reader( TFLS_GEOIP_DB );
			$record  = $reader->country( $ip );
			$isoCode = $record->country->isoCode;
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
		}

		return $isoCode;
	}
}

/**
 * Return country ISO code from client IP
 *
 * @return string
 */
if ( ! function_exists( 'country_from_client_ip' ) ) {

	function country_from_client_ip() {

		$debug_ip      = get_option( 'tfls_debug_ip' );
		$debug_enabled = get_option( 'tfls_debug_mode' );

		if ( $debug_enabled && ! empty( $debug_ip ) ) {
			$client_ip = $debug_ip;
		} else {
			if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && $_SERVER['HTTP_CLIENT_IP'] ) {
				$client_ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && $_SERVER['HTTP_X_FORWARDED_FOR'] ) {
				$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$client_ip = $_SERVER['REMOTE_ADDR'];
			}
		}

		return get_country_from_ip( $client_ip );
	}
}

/**
 * Download GeoIP Database
 */
if ( ! function_exists( 'tfls_download_geoipdb' ) ) {
	function tfls_download_geoipdb() {

		$result = '';

		// We need the download_url() function, it should exist on virtually all installs of PHP, but if it doesn't for some
		// reason, bail out.
		if ( function_exists( 'download_url' ) ) {

			// TODO: Remove hard coding of URL
			$download_url = "http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz";

			// Check to see if the subdirectory we're going to download to exists, if not, create it.
			if ( ! file_exists( TFLS_UPLOAD_DIR ) ) {
				wp_mkdir_p( TFLS_UPLOAD_DIR );
			}

			$tmpFile = download_url( $download_url );

			if ( is_wp_error( $tmpFile ) ) {
				$result = sprintf( __( 'Error downloading GeoIP database from: %s. %s', 'the-fairy-light-shop-site-plugin' ), $download_url, $tmpFile->get_error_message() );
			} else {

				// Gunzip file
				$zh = gzopen( $tmpFile, 'rb' );
				$h  = fopen( TFLS_GEOIP_DB, 'wb' );

				// If we failed, display message
				if ( ! $zh ) {
					$result = __( 'Downloaded file could not be opened for reading.', 'the-fairy-light-shop-site-plugin' );
				} elseif ( ! $h ) {

					// woocommerce-product-price-based-on-countries had this:
					// $result = sprintf(__('Database could not be written (%s).', 'woocommerce-product-price-based-countries'), $outFile);
					//
					// TODO: Was the use of $outFile an error? Or does this variable have special significance?
					// TODO: I'm unable to find any reference to $outFile anywhere in the project source.

					$result = __( 'Database could not be written (%s).', 'the-fairy-light-shop-site-plugin' );
				} else {
					// Read the database in 4kb chunks, writing to our local db.
					while ( ( $string = gzread( $zh, 4096 ) ) !== false ) {
						fwrite( $h, $string );
					}

					// Close all the handlers
					gzclose( $zh );
					fclose( $h );
				}
			}

			unlink( $tmpFile );

		} // End function_exists( 'download_url' )

		return $result;
	}
}

// This basically maps the tfls_update_geoip hook to our tfls_download_geoipdb function
add_action( 'tfls_update_geoip', 'tfls_download_geoipdb' );

/**
 * Used by 'cron_schedule' filter to ensure that '4weeks' is a valid recurrence strategy in 'wp_schedule_event'.
 *
 * @param array
 *
 * @return array
 */
function tfls_cron_schedules( $schedules ) {

	if ( ! array_key_exists( '4weeks', $schedules ) ) {
		$schedules['4weeks'] = array(
			'interval' => 2419200,
			'display'  => __( 'Once every 4 weeks.' )
		);
	}

	return $schedules;
}

add_filter( 'cron_schedules', 'tfls_cron_schedules' );

/**
 * Runs when plugin is de-activated, and ensures that all scheduled events (i.e. to update geoip database) are removed.
 */
function tfls_deactivate() {

	if ( wp_next_scheduled( 'tfls_update_geoip' ) ) {
		wp_clear_scheduled_hook( 'tfls_update_geoip' );
	}
}

/**
 * Runs when the plugin is activated. Setups up the scheduled geoip updates if the options is set.
 */
function tfls_activate() {

	if ( get_option( 'tfls_update_geoip' ) && ! wp_next_scheduled( 'tfls_update_geoip' ) ) {``
		wp_schedule_event( time(), '4weeks', 'tfls_update_geoip' );
	}
}

register_activation_hook( TFLS_FILE, 'tfls_activate' );
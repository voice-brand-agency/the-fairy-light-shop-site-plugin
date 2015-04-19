<?php

// If uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

//delete de options

delete_option( '_oga_tfls_apiurl' );

delete_option( '_oga_tfls_api_country_field' );

delete_option( '_oga_tfls_countries_groups' );

delete_option( 'wc_tfls_update_geoip' );

delete_option( 'wc_tfls_debug_mode' );

delete_option( 'wc_tfls_debug_ip' );

delete_option( 'wc_tfls_timestamp' );


// unlink geoip db

$geoip_db = wp_upload_dir();
$geoip_db = $geoip_db['basedir'] . '/the_fairy_light_shop_site_plugin/GeoLite2-Country.mmdb';

if ( file_exists( $geoip_db ) ) {
	unlink( $geoip_db );
}
?>
<?php 
global $wpdb;

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
	exit();

$option_name = array( 'yaship_db_version', 'Yaship_Shipping_quickquote_flag', 'woocommerce_Yaship_Shipping_settings', 'woocommerece_yaship_account_mode', 'yaship_user', 'yaship_is_registered' );
foreach ( $option_name as $option_name ) {
	delete_option( $option_name );
}
/*$option_name = 'yaship_db_version';
$option_name1 = 'woocommerce_Yaship_Shipping_settings';

delete_option( $option_name );
delete_option( $option_name1);
delete_option( "Yaship_Shipping_quickquote_flag");*/

$result = $wpdb->get_results( "SELECT DISTINCT post_id, meta_key FROM $wpdb->postmeta WHERE meta_key LIKE '%yaship%'", ARRAY_N );

for ( $i=0; $i<sizeof( $result ); $i++ ) {
	delete_post_meta( $result[$i][0], $result[$i][1]);
}

// For site options in multisite
delete_site_option( $option_name );  

//drop a custom db table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}yaship_trans" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}yaship_data" );
?>
<?php
/*
Plugin Name: Yaship Shipping
Plugin URI: http://www.yaship.com/yaship-shipping/
Description: Provide real time shipping rates, generate shipping label for the shipment, generate return label for return shipment and allow to print label for single shipment or multiple labels in bulk i.e. 'bulk Print'.
Version: 1.0
Author: newhouse77
Author URI: http://www.yaship.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*CHECK IS WOOCOMMERCE AVAILABLE*/
 if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	die('please install and activate woocommerce plugin.');
} 
//CHECK IF US IS ALLOWED
$ctr_option = get_option( 'woocommerce_allowed_countries' );
$temp_ctr_ary = get_option( 'woocommerce_specific_allowed_countries' );

if ( $ctr_option=='specific' && ( empty( $temp_ctr_ary ) || !in_array( 'US',$temp_ctr_ary ) ) ) {
	die( "This shipping service is available only with in US. Please add 'US' in woocommerce's allowed countries" ); 
}

class Yaship {
	public function __construct() {
		$this->define_constants();
		$this->init_hooks();	
	}
	/**
	 * Define Constant
	 *
	 * @access private
	 * @return void
	 * @since 8.0.0
	 */	
	private function define_constants() {
		define( "YASHIP_ID", "Yaship_Shipping" );
		define( "YASHIP_LOGS", plugin_dir_path(__file__)."logs/" );
	}
	/**
	 * Hooks action and filter
	 *
	 * @access private
	 * @return void
	 * @since 8.0.0
	 */	
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'yaship_load_scripts') );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'woocommerce_shipping_init', array( $this, 'yaship_shipping_init') );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'yaship_add_method') );
		add_action( 'yaship_after_installation', array( $this,'quick_quote_form' ));
	}
	/**
	 * Load JS & CSS FrontEnd
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 */
	public function yaship_load_scripts() {
		
		wp_enqueue_script(
			'register_form',
			plugin_dir_url(__FILE__ ).'js/register_form.js',
			array('jquery')
		);
		wp_localize_script(
			'register_form',
			'objregisterform',
			array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'register_form_nonce' => wp_create_nonce( 'register_form_nonce' )
			)
		);
		wp_enqueue_script(
			'label_settings',
			plugin_dir_url(__FILE__ ).'js/label_settings.js',
			array( 'jquery' )
		);
		wp_localize_script(
			'label_settings',
			'objlabelsettings',
			array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'label_settings_nonce' => wp_create_nonce( 'label_settings_nonce' )
			)
		);
		
		wp_enqueue_script( 'jquery' );
		
		wp_enqueue_script( 'jquery-ui-sortable' );
		
		wp_register_script( 'yaship-bootstrap-min', plugin_dir_url(__FILE__ ).'js/bootstrap.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'yaship-bootstrap-min' );
		
		wp_register_script( 'yaship-dataTables-min', plugin_dir_url(__FILE__ ).'js/jquery.dataTables.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'yaship-dataTables-min' );
		
		wp_enqueue_style(
			'ya_register_form',
			plugin_dir_url(__FILE__ ).'css/ya_register_form.css'
		);
		wp_enqueue_style(
			'ya_shipping_style',
			plugin_dir_url(__FILE__ ).'css/ya_shipping_style.css'
		);
		wp_enqueue_style(
			'ya_report',
			plugin_dir_url(__FILE__ ).'css/ya_report.css'
		);
	}
	function quick_quote_form() {
		if( !get_option( YASHIP_ID."_quickquote_flag" ) ) {
			include_once( 'register/quick_quote.php' );
			update_option( YASHIP_ID."_quickquote_flag", true );
		}
	}
	public function yaship_add_method( $methods ) {
		$methods[] = 'Yaship_Shipping';
		return $methods;
	}
	public function init() {
		//Print Shipping Label.
		include_once ( 'includes/class-yaship-shipping-manager.php' );
	}
	public function yaship_shipping_init() {
		include_once( 'includes/class-yaship-shipping.php' );
	}
}
function yaship_registration() {
	include_once( 'register/yaship-register.php' );
}
add_action( 'init', 'yaship_registration' );
new Yaship();
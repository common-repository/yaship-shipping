<?php
class Yaship_Shipping_Manager {
	private $services = array(
			// Domestic
			"03" => "UPS Ground",
			"12" => "UPS 3 Day Select",
			"02" => "UPS 2nd Day Air",
			"59" => "UPS 2nd Day Air AM",
			"01" => "UPS Next Day Air",
			"13" => "UPS Next Day Air Saver",
			"14" => "UPS Next Day Air Early AM",
		);
		
	private $packaging = array(
		"01" => array(
					"name" 	 => "Letter",
					"length" => "12.5",
					"width"  => "9.5",
					"height" => "0.25",
					"weight" => "0.5"
				),
		"03" => array(
					"name" 	 => "Tube",
					"length" => "38",
					"width"  => "6",
					"height" => "6",
					"weight" => "100"
				),
		"24" => array(
					"name" 	 => "25KG Box",
					"length" => "19.375",
					"width"  => "17.375",
					"height" => "14",
					"weight" => "25"
				),
		"25" => array(
					"name" 	 => "10KG Box",
					"length" => "16.5",
					"width"  => "13.25",
					"height" => "10.75",
					"weight" => "10"
				),
		"2a" => array(
					"name" 	 => "Small Express Box",
					"length" => "13",
					"width"  => "11",
					"height" => "2",
					"weight" => "100"
				),
		"2b" => array(
					"name" 	 => "Medium Express Box",
					"length" => "15",
					"width"  => "11",
					"height" => "3",
					"weight" => "100"
				),
		"2c" => array(
					"name" 	 => "Large Express Box",
					"length" => "18",
					"width"  => "13",
					"height" => "3",
					"weight" => "30"
				)
		);
	function __construct() {
		$this->init(); 
		
		add_action( 'woocommerce_order_items_table', array( $this, 'yaship_create_shipment' ), 10, 1 );
		add_action( 'woocommerce_order_status_cancelled', array($this,'yaship_on_shipment_cancelled' ), 10, 1 );
		
		//HOOK TO ADD CUSTOM COLUMN AT ORDER LIST PAGE
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_ya_columns' ) );
		
		//HOOK TO PREFORM ACTION FOR THE CUSTOM COLUMN
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'alter_shop_order_columns' ), 2 );
		
		//HOOK TO CUSTOM ACTION OF CANCEL SHIPMENT FROM ORDER PAGE
		add_action( 'wp_ajax_yaship_shipment_cancelled_admin_order', array( $this,'yaship_shipment_cancelled_admin_order' ) );
		
		//HOOK TO CUSTOM ACTION OF CANCEL PACKAGE FROM ORDER PAGE
		add_action( 'wp_ajax_yaship_package_cancelled_admin_order', array( $this,'yaship_package_cancelled_admin_order' ) );
		
		//HOOK TO CUSTOM ACTION OF REGENERATING SHIPMENT FROM ORDER PAGE
		add_action( 'wp_ajax_yaship_re_shipment_admin_order', array( $this,'yaship_re_shipment_admin_order' ) );
		
		//HOOK TO ADD BUTTON AT order PAGE(admin panel)
		add_action( 'add_meta_boxes', array( $this, 'yaship_custom_order_meta_boxes'), 15, 2 );
		
		//HOOK TO ADD BULK ACTION PRINT LABEL AT ORDER SUMMARY PAGE
		add_action( 'admin_footer-edit.php', array( $this,'add_print_label_bulk_admin_footer' ), 10 );
		add_action( 'load-edit.php', array( &$this, 'custom_print_label_bulk_action' ) );
		
	}
	private function init(){
		$this->id = YASHIP_ID; 
		$this->settings = get_option( 'woocommerce_'.YASHIP_ID.'_settings', null );
		
		$this->api_key = isset( $this->settings['api_key'] ) ? $this->settings['api_key'] : '';
		$this->account_number = isset( $this->settings['account_number'] ) ? $this->settings['account_number'] : '';
		
		$this->first_name = isset( $this->settings['first_name'] ) ? $this->settings['first_name'] : '';
		$this->last_name = isset( $this->settings['last_name'] ) ? $this->settings['last_name'] : '';
		$this->origin_phone = isset( $this->settings['origin_phone'] ) ? $this->settings['origin_phone'] : '';
		$this->origin_company = isset( $this->settings['origin_company'] ) ? $this->settings['origin_company'] : '';
		$this->origin_email = isset( $this->settings['origin_email'] ) ? $this->settings['origin_email'] : '';
		
		$this->ship_from_address = isset( $this->settings['ship_from_address'] ) ? $this->settings['ship_from_address'] : 'shipping_address';
		$this->origin_addressline = isset( $this->settings['origin_addressline'] ) ? $this->settings['origin_addressline'] : '';
		$this->origin_city = isset( $this->settings['origin_city'] ) ? $this->settings['origin_city'] : '';
		$this->origin_postcode = isset( $this->settings['origin_postcode'] ) ? $this->settings['origin_postcode'] : '';
		$this->origin_country_state = isset( $this->settings['origin_country_state'] ) ?$this->settings['origin_country_state'] : '';
		
		$this->custom_services = isset( $this->settings['services'] ) ? $this->settings['services'] : array();
		$this->units = isset( $this->settings['units'] ) ? $this->settings['units'] : 'imperial';

		if ( $this->units == 'metric' ) {
			$this->weight_unit = 'KGS';
			$this->dim_unit    = 'CM';
		} else {
			$this->weight_unit = 'LBS';
			$this->dim_unit    = 'IN';
		}
		$this->res_addr = isset( $this->settings['res_addr'] ) && $this->settings['res_addr'] == 'yes' ? true : false;
		$this->packing_method = isset( $this->settings['packing_method'] ) ? $this->settings['packing_method'] : 'per_item';
		$this->yaship_packaging	= isset( $this->settings['yaship_packaging'] ) ? $this->settings['yaship_packaging'] : array();
		$this->boxes = isset( $this->settings['boxes'] ) ? $this->settings['boxes'] : array();
		$this->print_option = isset( $this->settings['print_option'] ) ? $this->settings['print_option'] : array();
		$this->email_label_link = isset( $this->settings['email_label_link'] ) ? $this->settings['email_label_link'] : array();
	}
	function yaship_create_shipment( $order ) {
		global $wpdb;
		$t_id = $order->id;
		
		$status = $wpdb->get_var( $wpdb->prepare(
		  "SELECT yaship_trans_status FROM wp_yaship_trans WHERE woo_order_id = %d",
		  $t_id // an untrusted integer (function will do the sanitization for you)
		) );
		
		if (!isset($status)) {
			$this->user_id = $order->get_user_id( );
			$wpdb->insert( 
					'wp_yaship_trans', 
					array( 
						'cust_id' =>$this->user_id ,//get_current_user_id(), 
						'woo_order_id' => $order->id,
						'creation_date' => date("Y-m-d H:i:s"),
						'yaship_trans_status' => 1,
						'yaship_trans_id' => 0,
						'track_number' => ''
					)
				);//1- in progress
				
			if ( isset( $wpdb->insert_id ) ) {
				echo $wpdb->insert_id;
				$yaship_ref_id = $wpdb->insert_id;
				$request = $this->get_Shipment_request( $yaship_ref_id, $order );
				
				$endpoint = "http://www.yaship.com/api/api/shipment";
				
				$post_id = $order->id;
				 
				//SAVE REQUEST TEMPARIRILY TILL ORDER STATUS CHANGE TO COMPLETE
				update_post_meta( $post_id, 'yaship_transient_request_'.$yaship_ref_id, $request );
				
				$response = wp_remote_post( $endpoint,
					array(
						'method' => 'POST',
						'timeout'   => 70,
						'sslverify' => 0,
						'body'      => $request,
						'cookies' => array()
					)
				);
				$this->process_ship_result( $yaship_ref_id, $response, $order, false, false );
			}else{
				//LOG ERROR as - can not insert data in wp_yaship_trans
				echo "can not insert data in wp_yaship_trans";
				$this->makeLog('ship_log.txt','can not insert data in database\n');
				exit;
			}
		}
	}
	
	private function get_shipment_request( $yaship_ref_id, $order ) {
		$sh_request = array();
		//shipFrom array store the address, from where package is dispatch
		//In our case from and shipper are same as site owner is paying directly to the api
		
		$ship_detail=get_post_meta( $order->id );
		
		$shipFrom = array();
		$shipFrom['fromName'] = $this->first_name.' '.$this->last_name;
		$shipFrom['fromAddr1'] = $this->origin_addressline;
		$shipFrom['fromCity'] = $this->origin_city;
		$shipFrom['fromState'] = $this->origin_country_state;
		$shipFrom['fromCode'] = $this->origin_postcode;
		$shipFrom['fromCountry'] = 'US';//$shipper_details['origin_country'];
		
		$shipTo = array();
		$shipTo['shipName'] = $ship_detail['_shipping_first_name'][0] . ' '.$ship_detail['_shipping_last_name'][0];
		$shipTo['shipAddr1'] = $ship_detail['_shipping_address_1'][0] . ' '.$ship_detail['_shipping_address_2'][0];
		$shipTo['shipCity'] = $ship_detail['_shipping_city'][0];
		$shipTo['shipState'] = $ship_detail['_shipping_state'][0];
		$shipTo['shipCountry'] = 'US';//$ship_detail['_shipping_country'];
		$shipTo['shipCode'] = $ship_detail['_shipping_postcode'][0];
		$shipTo['shipAttentionName'] = $shipTo['shipName'];
		$shipTo['shipCompany'] = $ship_detail['_billing_company'][0];
		$shipTo['shipEmail'] = $ship_detail['_billing_email'][0];
		$shipTo['shipPhone'] = $ship_detail['_billing_phone'][0];
		
		//shipper is same as billing address in our case
		$shipper = array();
		$shipper['shipperName'] = $this->first_name.' '.$this->last_name;
		$shipper['shipperAddr1'] = $this->origin_addressline;
		$shipper['shipperCity'] = $this->origin_city;
		$shipper['shipperCode'] = $this->origin_postcode;
		$shipper['shipperCountry'] = 'US';//$ship_detail['_billing_country'];
		$shipper['shipperState'] = $this->origin_country_state;
		$shipper['shipperPhone'] = $this->origin_phone;
		$shipper['shipperCompany'] = $shipper['shipperName'];
		$shipper['shipperEmail'] = $this->origin_email;
		
		$sh_request['shipFrom'] = $shipFrom;
		$sh_request['shipTo'] = $shipTo;
		$sh_request['shipper'] = $shipper;
		
		$shipping_methods = $order->get_shipping_methods();
		
		if ( ! $shipping_methods ) {
			return false;
		}
		$shipping_method = array_shift( $shipping_methods );
		$shipping_service_tmp_data = explode( ':', $shipping_method['method_id'] );
		
		//SERVICE DATA
		$service = array();
		$service['code'] = $shipping_service_tmp_data[1];
		$service['name'] = $shipping_method['name'];

		$cost=0;
		if( $shipping_method['cost']==0 && WC()->session->__isset( YASHIP_ID.'ya_cart_rate_resp' ) && !empty(WC()->session->get( YASHIP_ID.'ya_cart_rate_resp' ) ) ) {
			$rates_resp = WC()->session->get( YASHIP_ID.'ya_cart_rate_resp' );
			$services = $this->services;
			foreach ( $services as $rservice=>$val ) {
				if ( $shipping_service_tmp_data[1] == $rservice ) {
					$cost = $rates_resp[$rservice];
					break;
				} else {
					$this->makeLog('log.txt', $shipping_method['name'].'is not available in yaship enabled services');
				}
			}
		}
		if( $cost!=0 ) {
			$service['cost'] = $cost;
			update_post_meta ( $order->id, $this->id.'_fsr', $cost );
		} else {
			$service['cost'] = $shipping_method['cost'];
		}
			
		$sh_request['service'] = $service;
		
		//PACKAGE
		$package = array();
		
		switch ( $this->packing_method ) {
			case 'box_packing' :
				$package = $this->boxPackageShipRequest( $order );
			break;
			
			case 'per_item' :
				$package = $this->perItemShipRequest( $order );
			break;
			
			default :
				$package = $this->perItemShipRequest( $order );
			break;
		} 
		$sh_request['package'] = $package;
		$sh_request['api_key'] = $this->api_key;
		$sh_request['account_number'] = $this->account_number;
		$sh_request['res_addr'] = ( isset( $this->res_addr )&&( $this->res_addr =='yes' ) )?1:0;
		$sh_request['mode'] = get_option('woocommerece_yaship_account_mode');
		return $sh_request;
	}
	private function perItemShipRequest( $order ) {
		$orderItems = $order->get_items();
		$requests = array();
		foreach( $orderItems as $orderItem )
		{
			$ctr=0;
			$item_id = $orderItem['variation_id'] ? $orderItem['variation_id'] : $orderItem['product_id'];
			$product_data = wc_get_product( $item_id );

			if ( ! $product_data->get_weight() ) {
				$this->debug( sprintf( __( 'Product #%d is missing weight. Aborting.', 'yaship' ), $ctr ), 'error' );
				return;
			} 
			
			$cart_item_qty = $orderItem['qty'];
			$l_request = array();
			$l_package = array();
			$l_package_type = array();
			$l_package_type['Code'] = '02';
			$l_package_type['Description'] = 'Package/customer supplied';
			$l_package['PackagingType'] = $l_package_type;
			$l_package['Description'] = 'Ship';

			if ( $product_data->get_length( ) && $product_data->get_height( ) && $product_data->get_width( ) ) {
				$dimensions = array(
							number_format( woocommerce_get_dimension( $product_data->width, $this->dim_unit ), 2, '.', ''),
							number_format( woocommerce_get_dimension( $product_data->height, $this->dim_unit ), 2, '.', ''),
							number_format( woocommerce_get_dimension( $product_data->length, $this->dim_unit ), 2, '.', ''),
						);
				sort( $dimensions );
				$l_dimenssion = array();
				$l_dimenssion['UnitOfMeasurement']['code'] = $this->dim_unit;
				$l_dimenssion['Length'] = $dimensions[2];
				$l_dimenssion['Width'] = $dimensions[1];
				$l_dimenssion['Height'] = $dimensions[0];
				$l_package['Dimensions'] = $l_dimenssion;
			}
			$l_pkg_weight = array();
			$l_pkg_weight['UnitOfMeasurement']['code'] = $this->weight_unit;
			$l_pkg_weight['Weight']= $product_data->get_weight( );
			$l_package['PackageWeight'] = $l_pkg_weight;
			$l_request['Package'] = $l_package;
			for ( $i=0; $i < $cart_item_qty ; $i++)
				$requests[] = $l_request;
			//$items[$item_id] 	= array('data' => $product_data , 'quantity' => $orderItem['qty']);
		}
		return $requests; 
	}
	
	/*Function to create package structure for box packaging shipping option*/
	private function boxPackageShipRequest( $order ) {
		global $woocommerce;
		$requests = array();
		if ( ! class_exists( 'Yaship_Boxpack' ) )
			include_once 'class-packing.php';

		$boxpack = new Yaship_Boxpack();
		
		// Add Standard dimensions boxes
		if ( ! empty( $this->yaship_packaging )  ) {
			foreach ( $this->yaship_packaging as $key => $box_code ) {
				$box = $this->packaging[ $box_code ];
				$name = $box['name'];
				$newbox = $boxpack->add_box( $box['length'], $box['width'], $box['height'] );

				$newbox->set_inner_dimensions( $box['length'], $box['width'], $box['height'] );
				$newbox->set_id( $box['name'] );
				if ( $box['weight'] )
					$newbox->set_max_weight( $box['weight'] );
			}
		}

		// Define boxes
		if ( ! empty( $this->boxes ) ) {
			foreach ( $this->boxes as $box ) {
				$newbox = $boxpack->add_box( $box['outer_length'], $box['outer_width'], $box['outer_height'], $box['box_weight'] );

				$newbox->set_inner_dimensions( $box['inner_length'], $box['inner_width'], $box['inner_height'] );
				$newbox->set_id('custom box');
				if ( $box['max_weight'] )
					$newbox->set_max_weight( $box['max_weight'] );
			}
		}
		// Add items into the box by finding perfect one out of available options
		$ctr = 0;
		$orderItems = $order->get_items();
		foreach (  $orderItems as $orderItem ) {
			$ctr++;
			$item_id = $orderItem['variation_id'] ? $orderItem['variation_id'] : $orderItem['product_id'];
			$product_data = wc_get_product( $item_id );
			$cart_item_qty = $orderItem['qty'];
			
			if ( !( $cart_item_qty > 0 && $product_data->needs_shipping() ) ) {
				$this->debug( sprintf( __( 'Skipping Product #%d .', 'yaship' ), $ctr ) );
				continue;
			}
				//Check if dimensions are set
			if ( $product_data->length && $product_data->height && $product_data->width && $product_data->weight ) {
				$dimensions = array( $product_data->length, $product_data->height, $product_data->width );
				for ( $i = 0; $i < $cart_item_qty; $i ++ ) {
					$boxpack->add_item(
						number_format( woocommerce_get_dimension( $dimensions[2], $this->dim_unit ), 2, '.', ''),
						number_format( woocommerce_get_dimension( $dimensions[1], $this->dim_unit ), 2, '.', ''),
						number_format( woocommerce_get_dimension( $dimensions[0], $this->dim_unit ), 2, '.', ''),
						number_format( woocommerce_get_weight( $product_data->get_weight(), $this->weight_unit ), 2, '.', ''),
						$product_data->get_price()
					);
				}
			} else {
				//Return if dimensions are not sets
				$this->debug( sprintf( __( 'Yaship Packing Method is set to Pack into Boxes. Product #%d is missing dimensions. Aborting.', 'yaship' ), $ctr ), 'error' );
				return;
			}
		}
		// all dimensions are available then pack it
		$boxpack->pack();

		// Get packages
		$box_packages = $boxpack->get_packages();
		$ctr=0; 
		foreach ( $box_packages as $key => $box_package ) {
			$ctr++;

			$weight = $box_package->weight;
			$dimensions = array( $box_package->length, $box_package->width, $box_package->height );
			sort( $dimensions );
			$l_request = array();
			$l_package = array();
			$l_package_type = array();
			$l_package_type['Code'] = '02';
			$l_package_type['Description'] = 'Package/customer supplied';
			$l_package['PackagingType'] = $l_package_type;
			$l_package['Description'] = 'Ship';

			if ( !empty($dimensions) ) {
				$l_dimenssion = array();
				$l_dimenssion['UnitOfMeasurement']['code'] = $this->dim_unit;
				$l_dimenssion['Length'] = $dimensions[2];
				$l_dimenssion['Width'] = $dimensions[1];
				$l_dimenssion['Height'] = $dimensions[0];
				$l_package['Dimensions'] = $l_dimenssion;
			}
			$l_pkg_weight = array();
			$l_pkg_weight['UnitOfMeasurement']['code'] = $this->weight_unit;
			$l_pkg_weight['Weight']= $weight;
			$l_package['PackageWeight'] = $l_pkg_weight;
			$l_request['Package'] = $l_package;
			$requests[] = $l_request;
		}
		$this->makeLog('box_package_request',$requests);
		return $requests; 
	}
	private function process_ship_result( $yaship_ref_id, $response, $order, $code,$service )
	{
		global $wpdb;
		try {
			$response = json_decode( strstr($response['body'], '{' ), true);
		} catch ( Exception $ex ) {
			print_r( $response );
			$this->makeLog('ship_log.txt',serialize($ex));
			exit;
		}
		if ( $response['success'] ) {
			//UPDATE STATUS TO 2 - label generated
			try {
				$shipper_details = get_option( 'woocommerce_'.YASHIP_ID.'_settings');
				if ( isset( $response['re_ship_rate'] ) && $code ) {
					//delete all existing label entries
				}
				$print_code = $this->print_option;
				$label_links = $response['label_link'];
				$wpdb->update('wp_yaship_trans', 
							array( 
								'yaship_trans_status' => 2,
								'yaship_trans_id' => $response['shpnow_trans_id'],
								'track_number'=>$response['ship_tracking_number']
							), 
							array( 'ID' => $yaship_ref_id ) 
						);
				$email_link_ary = array();
				foreach ( $label_links as $tr_number => $label_link ) {
					if ( $print_code == "002" ) {
						$label_link = substr( $label_link, 0, ( strlen( $label_link)-strlen( strrchr ( $label_link, "/" ) ) ) )."/label".$tr_number.".gif";
					}
					$email_link_ary[$tr_number] = $label_link;
					$wpdb->insert( 
						'wp_yaship_data', 
						array( 
							'woo_order_id' => $order->id,  //order id
							'yaship_trans_id' => $yaship_ref_id, //id of wp_yaship_trans tbl
							'creation_date' => date("Y-m-d H:i:s"),
							'label_link' => $label_link,
							'track_number' => $tr_number,
							'is_deleted' => 0
						)
					);
				}
			} catch( Exception $ex ) {
				print_r( $response );
				$this->makeLog('log.txt',serialize($ex));
				exit;
			}
			
			//send an email to customer
			$b_email = get_post_meta( $order->id, '_billing_email', true );
			$fname = get_post_meta( $order->id, '_shipping_first_name', true );
			$lname = get_post_meta( $order->id, '_shipping_last_name', true );
				
			$message2= '<html><body><p>Hi,'.$fname . ' '.$lname.'</p><p> Your shipment has been submitted, </p><strong><p><strong>Your Tracking Number is: </strong>'.$response['ship_tracking_number'].'</p><p><strong>Thank You</strong></p></body></html>';

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
				
			wp_mail( $b_email, 'Yaship Shipment', $message2, $headers );

			$email_label_option = $this->email_label_link;
				
			if ( $email_label_option == "001" ) {
				$s_email = $this->origin_email;
				$s_fname = $this->first_name;
				$s_lname = $this->last_name;
				$email_name = '<html><body><p>Hi,'.$s_fname . ' '.$s_lname.'</p>';
				
				$e_str = '';
				foreach ( $email_link_ary as $tr_num => $e_link ) {
					$e_str .= '<a href="'.$e_link.'" target="_blank">'.$tr_num.'</a><br>';
				}
				
				$message1= '<p> Your shipment has been submitted, </p><strong><p><strong>Your Shipment Main Tracking Number is: </strong>'.$response['ship_tracking_number'].'</p><p><strong>Your Package Tracking Numbers are: </strong><br>'.$e_str.'</p><p><strong>Order Id : </strong>'.$order->id.'</p></body></html>';
				 
				wp_mail( $s_email, 'Yaship Shipment', $email_name.$message1, $headers );

				$email_name = '<html><body><p>Hi,'.$fname . ' '.$lname.'</p>';

				//wp_mail( $b_email, 'Yaship Shipment', $email_name.$message1, $headers );
				//wp_mail('kiran.ssl22@gmail.com', 'Yaship Shipment', $message,$headers );
			}
			if( isset( $response['re_ship_rate'] ) && $response['re_ship_rate'] > 0 && $code && $service ) {
				$shipping_methods = $order->get_shipping_methods();
				foreach ( $shipping_methods as $key => $val ) {
					$woo_order_item_id = $key;
					if ( strpos( $val['method_id'], YASHIP_ID ) >= 0 ) {
						$new_id = $this->id . ':' .$code ;
						$args = array(
									'method_id' 	=> $new_id,
									'method_title' => $service,
									'cost' 	=> $response['re_ship_rate'],
									//'sort'  => $sort
								);
						$order->update_shipping( $woo_order_item_id, $args );
						break;
					}
				}
			}
			$res = array( "res"=>true, "msg"=>'Successful Shipment!!' );
		} else {
			$res = array( "res"=>false, "msg"=>$response['message'] );
			//UPDATE STATUS TO 3 = on hold
			$wpdb->update( 
				'wp_yaship_trans', 
				array( 
					'yaship_trans_status' => '3' 
				), 
				array( 'ID' => $yaship_ref_id )
			);
		}
		return $res;
	}
	
	function yaship_shipment_cancelled_admin_order() {
		$nonce = $_POST['security'];
		if ( wp_verify_nonce( $nonce, 'label_settings_nonce' ) ) {
			$order_id = intval(sanitize_text_field($_POST['id']) );
			$this->yaship_on_shipment_cancelled( $order_id );
		}
		wp_die();
	}
	function yaship_on_shipment_cancelled( $order_id ) {
		global $wpdb;
		if ( is_admin() ) {
			try {
				//SEND REQUEST TO YASHIP TO CANCELED REQUEST
				$endpoint = "http://www.yaship.com/api/api/cancel_ship";
				
				$request = $this->create_ship_cancel_request( $order_id );
				
				$yaship_track_num = sanitize_text_field( $wpdb->get_var( "SELECT track_number FROM wp_yaship_trans WHERE woo_order_id = '".$order_id."'" ));
				
				$yaship_trans_id = $request['yaship_trans_id'];
				$response = wp_remote_post( 
					$endpoint,
					array(
						'method' => 'POST',
						'timeout'   => 70,
						'sslverify' => 0,
						'body'      => $request,
						'cookies' => array()
					)
				);
				$this->makeLog('ship_cancel_resp',$response);
				$response = json_decode( $response['body'], true );
				if( $response['success'] ) {
					//UPDATE order status 4 - cancelled 
					$wpdb->update( 
						'wp_yaship_trans', 
						array( 
							'yaship_trans_status' => 4,
							'track_number' => ""
						), 
						array( 'yaship_trans_id' => $yaship_trans_id ) 
					);
					//DELETE ALL SHIPPING LABELS REGARDING THIS CANCLED SHIPMENT
					$result = $wpdb->delete( 
						'wp_yaship_data',      // table name 
						array( 'woo_order_id' => ($order_id)),  // where clause 
						array( '%d' )      // where clause data type (string)
					);
					$shipper_details = get_option('woocommerce_'.YASHIP_ID.'_settings');
					$s_email = $this->origin_email;
					$s_fname = $this->first_name;
					$s_lname = $this->last_name;
					
					$message1= '<html><body><p>Hi,'.$s_fname . ' '.$s_lname.'</p><p> Shipment has been cancelled, </p><p><strong>Order ID : </strong>'.$order_id.'</p><p><strong>Shipment Tracking Number : </strong>'.$yaship_track_num.'</p></body></html>';
					
					$headers = array('Content-Type: text/html; charset=UTF-8');

					$b_email = get_post_meta($order_id,'_billing_email',true);
					$fname = get_post_meta($order_id,'_shipping_first_name',true);
					$lname = get_post_meta($order_id,'_shipping_last_name',true);
					
					$message= '<html><body><p>Hi,'.$fname . ' '.$lname.'</p><p> Shipment has been cancelled, </p><p><strong>Transaction ID : </strong>'.$yaship_trans_id.'</p><p><strong>Shipment Tracking Number : </strong>'.$yaship_track_num.'</p></body></html>';
					
					wp_mail( $b_email, 'Yaship - Shipment Cancellation ', $message,$headers );
				
					wp_mail( $s_email, 'Yaship - Shipment Cancellation ', $message1, $headers );
					
					$res = array( 'res'=>true, 'msg'=>'shipment voided successfully!!' );
				}else{
					 $res = array( 'res'=>false, 'msg'=>'void shipment error!!' );
				}
				print_r( json_encode ( $res ) );
			} catch ( Exception $ex ) {
				print_r( $response );
				file_put_contents("./log.txt",date('m-d-y').serialize($ex).'\n',FILE_APPEND);
				//die;
				exit;
			}
		} else {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
	}
	private function create_ship_cancel_request( $order_id )
	{
		global $wpdb;
		try {
			$yaship_trans_id = intval($wpdb->get_var( "SELECT yaship_trans_id FROM wp_yaship_trans WHERE woo_order_id = '".$order_id."'" ));
			
			$request = array();
			$request['api_key'] = isset($this->api_key)?$this->api_key : '';
			$request['account_number'] = isset($this->account_number) ? $this->account_number: '';
			$request['yaship_trans_id'] = $yaship_trans_id;
			$request['mode'] = get_option('woocommerece_yaship_account_mode');
			
			return $request;
		} catch( Exception $ex ) {
			print_r($response);
			//$this->makeLog('ship_cancle_log',serialize($ex))
			file_put_contents("./ship_cancle_log.txt",date('m-d-y').serialize($ex).'\n',FILE_APPEND);
			exit;
		}
	}
	function yaship_package_cancelled_admin_order() {
		$nonce = $_POST['security'];
		if ( wp_verify_nonce( $nonce, 'label_settings_nonce' ) ) {
			$order_id = intval( sanitize_text_field( $_POST[ 'id' ] ) );
			$packages = array_map( 'sanitize_text_field',$_POST['checkedValue']);
			$response = $this->yaship_on_package_cancelled( $order_id, $packages );
			echo $response['message'];
		}
		wp_die();
	}
	// function to cancel partial shipment
	function yaship_on_package_cancelled( $order_id, $tracking_numbers ){
		global $wpdb;
		if( is_admin() ) {
			try{
				$endpoint = "http://www.yaship.com/api/api/cancel_package";
				
				$request = $this->create_ship_cancel_request( $order_id );
				file_put_contents("./pkg_request.txt",print_r($request,true),FILE_APPEND);
				$yaship_track_num = sanitize_text_field($wpdb->get_var( "SELECT track_number FROM wp_yaship_trans WHERE woo_order_id = '".$order_id."'" ));
				$yaship_trans_id = $request['yaship_trans_id'];
				$request['tracking_numbers'] = $tracking_numbers;
				$request['main_tracking_no'] = $yaship_track_num;
				
				$response = wp_remote_post( 
					$endpoint,
					array(
						'method' => 'POST',
						'timeout'   => 70,
						'sslverify' => 0,
						'body'      => $request,
						'cookies' => array()
					)
				);
				$response = json_decode( $response['body'], true );
				
				if( $response['success'] ) {
					$voided_pkg = $response['voided_pkg'];
					// Delete all voided packages
					foreach( $voided_pkg as $voided_pkg ) {
						//UPDATE order status 4 - cancelled 
						$wpdb->update( 
							'wp_yaship_data', 
							array( 
								'is_deleted' => 1
							), 
							array( 'track_number' => $voided_pkg )
						);
					}
				} 
				return $response;
			} catch(Exception $ex){
				print_r($response);
				file_put_contents("./log.txt",date('m-d-y').serialize($ex).'\n',FILE_APPEND);
				//die;
				exit;
			}
		} else {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
	}
	
	function yaship_re_shipment_admin_order(){
		$nonce = $_POST['security'];
		if ( wp_verify_nonce( $nonce, 'label_settings_nonce' ) ) {
			global $wpdb;
			$t_id = intval(sanitize_text_field($_POST['id']));
			$code = sanitize_text_field($_POST['code']);
			$service = sanitize_text_field($_POST['service']);
			$status =  intval($wpdb->get_var( "SELECT yaship_trans_status FROM wp_yaship_trans WHERE woo_order_id = '".$t_id."'" ));
			
			if ( isset( $status ) ) {
				$order = new WC_Order( $t_id );
				$this->user_id = $order->get_user_id( );
				$yaship_ref_id =  intval($wpdb->get_var( "SELECT id FROM wp_yaship_trans WHERE woo_order_id = '".$t_id."'" ));
				
				if( isset( $yaship_ref_id ) ) {
					$request = $this->get_Shipment_request( $yaship_ref_id, $order );
					
					$request['service']['code']=isset($code)?$code:'';
					$request['service']['name']=isset($service)?$service :'';
					$request['service']['cost']=0;
					
					$endpoint = "http://www.yaship.com/api/api/re_ship";
					$post_id = $order->id;
					 
					//SAVE REQUEST TEMPARIRILY TILL ORDER STATUS CHANGE TO COMPLETE
					update_post_meta($post_id, 'yaship_transient_request_'.$yaship_ref_id, $request);
					
					$response = wp_remote_post( $endpoint,
						array(
							'method' => 'POST',
							'timeout'   => 70,
							'sslverify' => 0,
							'body'      => $request,
							'cookies' => array()
						)
					);
					$res = $this->process_ship_result( $yaship_ref_id, $response, $order, $code, $service );
					print_r( json_encode ( $res ) );
				}
			}
		}
		wp_die();
	}
	
	//Function to add bulk action  option in bulk action dropdown list(order page)
	function add_print_label_bulk_admin_footer(){
		global $post_type;
		if($post_type == 'shop_order') {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('<option>').val('print_lbl').text('<?php _e('Print Label')?>').appendTo("select[name='action']");
		  });
		</script>
   		<?php
		}	
	}
	
	//Function executed when print label custom action fired
	function custom_print_label_bulk_action(){
		global $typenow;
		$post_type = $typenow;
		if( ! isset( $_REQUEST['action'] ) || empty( $_REQUEST['action'] ) )
				return;
			// $action = sanitize_text_field($_REQUEST['action']);
			$action = ($_REQUEST['action']);
			// If no posts selected do nothing
			if( empty( $_REQUEST['post'] ) )
				return; 
			// If posts are selected join them into string
			if( is_array( $_REQUEST['post'] ) && ! empty( $_REQUEST['post'] ) ) {
				// $post_ids = array_map('intval', $_REQUEST['post']);
				$post_ids =$_REQUEST['post'];
				
				if( empty ( $post_ids ) ) 
					return;
				$sendback = remove_query_arg( array( 'untrashed', 'deleted', 'ids'), wp_get_referer() );
				if ( ! $sendback )
					$sendback = admin_url( "edit.php?post_type=$post_type" );
				$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
				$pagenum = $wp_list_table->get_pagenum();
				$sendback = add_query_arg( 'paged', $pagenum, $sendback );
				switch( $action ) {
					case 'print_lbl':
						if ( !$this->perform_print($post_ids) )
							wp_die( __('Error printing label .') );
						$sendback = add_query_arg( array('ids' => join(',', $post_ids) ), $sendback );
						break;
					
					default: return;
				}
				$sendback = remove_query_arg( array( 'action', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $sendback );
				
				echo '<script type="text/javascript">
					window.location = "'.$sendback.'"
					exit();
				</script>';
				exit();
			}
		return;
	}
	//Function toperform print label functionality
	/**************BULK PRINT*****************/
	/**CHECK FOR PRINT OPTION**/
	/**CHECK FOR LABEL AVAILABLE OR NOT**/
	/**IF ALL ABOVE CORRECT**/
	/**GET LINKS OF ALL ORDER IDS***/
	/***CREATE PDF OF ALL LABELS**/
	/**PRINT PDF***/
	/**RETURN TRUE IF ALL TASK COMLETE**/
	/**RETURN FALSE WITH FAILUER MESSAGE**/
	
	function perform_print( $post_ids )
	{
		global $wpdb;
		$id_ary = array();
		
		foreach ( $post_ids as $post_id ) {
			$id_ary[] ="woo_order_id = '".$post_id."'";
		}
		
		$flag = false;
		if ( !empty( $id_ary ) ) {
			$where_str = implode( " OR ", $id_ary );
			$flag = true; 
		}
		if ( $flag ) {
			$sql = "SELECT woo_order_id, track_number, label_link FROM wp_yaship_data WHERE $where_str AND is_deleted = 0" ;
			
			/*If multiple entries for same order_id including empty value then*/
			$result = $wpdb->get_results( $sql );
			$id_ary = array();
			$empty_res = array();
			foreach( $result as $key => $val ) {
				if( $val->track_number && $val->label_link ) {
					$id_ary[] = array(
					"order_id" =>$val->woo_order_id,
					"track_number"=>$val->track_number,
					"label_link"=>$val->label_link );
				} else {
					$empty_res[]= $val->woo_order_id;
				}	
			}
			foreach ( $empty_res as $key => $val ) {
				if ( array_key_exists( $val, $id_ary ) )
					unset( $empty_res[$key] );
			}
			if( !empty( $id_ary ) ) {
				$shipper_details = get_option( 'woocommerce_'.YASHIP_ID.'_settings');
				$print_code = $this->print_option;
				$link_ary = array();
				if ( $print_code == "002" ) {
					foreach ( $id_ary as $key =>$val ) {
						$ext = pathinfo( $val['label_link'], PATHINFO_EXTENSION );
						if ( $ext == "gif" ) {
							$link_ary[] = "<img src='".$val['label_link']."' width='600' height='384'/>";
						} else {
							$label_link = $val['label_link'];
							$img_src = "<img src='".substr( $label_link, 0, ( strlen( $label_link ) - strlen( strrchr( $label_link, "/" ) ) ) )."/label".$val['track_number'].".gif"."' width='600' height='384'/>";
							$link_ary[] = $img_src;
						}
					}
				} else {
					$dom = new DOMDocument();
					foreach ( $id_ary as $key =>$val ) {
						$ext = pathinfo( $val['label_link'], PATHINFO_EXTENSION );
						$file_cont = "";
						if ( $ext == "html" ) {
							$file_cont = file_get_contents( $val['label_link'] );
							$dom->loadHTML( $file_cont );
							$label_link = $val['label_link'];
							
							$img_src = substr( $label_link, 0, ( strlen( $label_link ) - strlen( strrchr( $label_link, "/" ) ) ) )."/label".$val['track_number'].".gif";
							
							foreach ( $dom->getElementsByTagName( 'img' ) as $img ) { 
								if ( strpos( $img->getAttribute( 'src' ), "label".$val['track_number'] ) ) {
									$img->setAttribute( 'src' , $img_src );
								}
							} 
							$file_cont = $dom->saveHTML();
						} else {
							$label_link = $val['label_link'];
							
							$file_cont = file_get_contents( substr( $label_link, 0, ( strlen( $label_link ) - strlen( strrchr( $label_link, "/" ) ) ) )."/".$val['track_number'].".html" );
							
							$dom->loadHTML( $file_cont );
							
							foreach ( $dom->getElementsByTagName( 'img' ) as $img ) { 
								if ( strpos( $img->getAttribute( 'src' ), "label".$val['track_number'] ) ) {
									$img->setAttribute( 'src' , $label_link );
								}
							} 
							$file_cont = $dom->saveHTML();
						}
						$link_ary[] = $file_cont;
					}
				}
				require_once(plugin_dir_path(__dir__)."lib/mpdf/mpdf.php");
				if ( $print_code == "002" ) {
					$tbl = "";
					$mpdf = new mPDF( '', '', 0, '', 0, 0, 0, 0, 0, 0, 'L' );
					foreach ( $link_ary as $index => $link ) {
						$tbl = "<table width='100%'><tr><td>$link</td></tr></table><br><br><br>";
						
						$html = mb_convert_encoding( $tbl, 'UTF-8', 'UTF-8' );	
						$mpdf->AddPage();
						$mpdf->WriteHTML( $html );
					}
				} else {
					$tbl = "";
					$mpdf = new mPDF( 'c', 'A4' );
					foreach ( $link_ary as $index => $link ) {
						$tbl = "<table><tr><td>$link</td></tr></table>";
						$mpdf->AddPage();
						$html = mb_convert_encoding( $tbl, 'UTF-8', 'UTF-8' );	
						$mpdf->WriteHTML( $html );
					}
				}
				$mpdf->Output( plugin_dir_path(__DIR__).'includes/print.pdf', 'F' );
				?>
				<script type="text/javascript">
					var win = window.open("<?php echo plugin_dir_url(__FILE__);?>"+"print.pdf", "_blank");
					win.document.body.innerHTML = "HTML";
				</script>
				<?php
				return true;	
			}
		}
		return false;
	}
	function add_ya_columns( $columns ) {
		$columns["custom_column"] = "Print Label";
		return $columns;
	}

	public function alter_shop_order_columns( $column ) 
	{
		global $post, $woocommerce, $the_order;

		if ( empty ( $the_order ) || $the_order->id != $post->ID ) {
			$the_order = wc_get_order( $post->ID );
		}
		global $wpdb;
		$links =  $wpdb->get_col( "SELECT label_link FROM wp_yaship_data WHERE woo_order_id = '".$the_order->id."' AND is_deleted = 0" );
		
		switch( $column ) 
		{
			case 'custom_column' :
				if( !empty( $links ) ) {
					$list_html = '<select onchange=window.open(this.value,"_blank");><option value="">Select Label</option>';
					foreach( $links as $key => $link )
					{
						if( $link )
						{
							$list_html = $list_html. '<option value="' . $link. '"><a target="_blank" href="' . $link. '">Print Label</a></option>';
						}
					}
					$list_html .= '</select>';
					echo $list_html;
				} else {
					echo'<label>Unavailable</label>';
				}
				break;
		} 
	}
	function yaship_custom_order_meta_boxes( $post_type, $post )
	{
		global $wpdb;
		if ( is_admin() ) {
			$order_id = $post->ID;
			$id =  intval($wpdb->get_var( "SELECT id FROM wp_yaship_trans WHERE woo_order_id = '".$order_id."'"));
			if ( isset( $id ) && $id > 0 ) {
				add_meta_box(
					'woocommerce-order-yaship-ref',
					__( 'Print label' ),
					array($this,'order_meta_box_yaship'),
					'shop_order',
					'side',
					'default',
					array( 'id' => $order_id )
				);
				add_meta_box(
					'woocommerce-order-yaship-cancel-shipment',
					__( 'Regenerate Shipment' ),
					array($this,'order_meta_box_yaship_regen_ship'),
					'shop_order',
					'side',
					'default',
					array( 'id' => $order_id )
				);
				add_meta_box(
					'woocommerce-order-yaship-cancel-labels',
					__( 'Void Shipment' ),
					array($this,'order_meta_box_yaship_void'),
					'shop_order',
					'side',
					'default',
					array( 'id' => $order_id )
				);
			}
		}
	}
	function order_meta_box_yaship_void( $post, $metabox )
	{
		$id=$metabox['args']['id'];
		if( is_admin() ) { //checks if is in admin interface
			global $wpdb;
			$tracking_numbers =  $wpdb->get_col( "SELECT track_number FROM wp_yaship_data WHERE woo_order_id = '".$id."' AND is_deleted = 0" );
			
			echo '<p>Select the packages you want to void and submit it or you can void complete shipment</p><table>
			<tr>
				<th style="padding-right: 10px;">Check</th>
				<th>No</th>
				<th>Tracking Number</th>
			</tr>';
			foreach ( $tracking_numbers as $key => $link )
			{
				echo '<tr>
				<td><input type="checkbox" value="'.$link.'" name ="track_number_checkbox"></td>
				<td>'.( $key + 1 ).'</td>
				<td>'.$link.'</td>
			</tr>';
			}
			
			echo '</table>
			<p><input type="button" value="Submit" id="yship_void_pckg_btn">
			<input type="button" value="Void Complete Shipment" id="yship_void_ship_btn" style="margin-left: 12px;"></p>';
		}
	}
	function order_meta_box_yaship_regen_ship( $post, $metabox )
	{
		$id=$metabox['args']['id'];
		$status = substr( get_post_status( $id ), 3 );
		if( is_admin() )
		{
			global $wpdb;
			$links =  $wpdb->get_col( "SELECT label_link FROM wp_yaship_data WHERE woo_order_id = '".$id."' AND is_deleted = 0");
			$link = false;
			if( !empty( $links ) )
				foreach( $links as $key=>$link1 ) {
					if( $link1 ) {
						$link = $link1;
						break;
					}
				}
			
			if ( $link )
			{
				echo '<p><ul>
				<li class="wide">
				<select class="select" id="yaship_admin_service_list" disabled>
				<option value="" selected="selected" >Select Service</option>';
				
				foreach ( $this->services as $service_code => $service_name ) {
					echo '<option value="'.$service_code.'" >'.$service_name.'</option>';
				}
				
				echo '</select>
				</li>
				<input type="hidden" value="'.$id.'" id="yaship_regen_hidden_field" >
				</p>
				
				<p><input type="button" value="Void Packages" id="yship_display_pckg_btn">
				<input type="button" value="Generate Label" id="yship_regen_ship_btn" disabled="disabled"></p>';
			} else {
				echo '<p><ul><li class="wide"><select class="select" id="yaship_admin_service_list"><option value="" selected="selected" >Select Service</option>';
				
				foreach ( $this->services as $service_code => $service_name )
				{
					echo '<option value="'.$service_code.'" >'.$service_name.'</option>';
				}
				echo '</select></li><input type="hidden" value="'.$id.'" id="yaship_regen_hidden_field" ></p><p><input type="button" value="Void Packages" id="yship_display_pckg_btn" disabled><input type="button" value="Generate Label" id="yship_regen_ship_btn" ></p>';
			}
		} else {
			echo"<p><h3>Cancelled Order</h3></p>";
		}
	}
	function order_meta_box_yaship( $post, $metabox )
	{
		$id = $metabox['args']['id'];
		$status = substr( get_post_status ( $id ), 3 );
		if ( $status != 'cancelled' ) {
			global $wpdb;
			$links =  $wpdb->get_col( "SELECT label_link FROM wp_yaship_data WHERE woo_order_id = '".$id."' AND is_deleted = 0");
			$link=array();
			
			if( !empty( $links ) ) {
				$list_html = '<select onchange=window.open(this.value,"_blank");><option value="">Select Label</option>';
				foreach ( $links as $key => $link1 ) {
					if ( $link1 ) {
						$list_html = $list_html. '<option value="' . $link1. '"><a target="_blank" href="' . $link1. '">Print Label</a></option>';
					}
				}
				$list_html = $list_html. '</select>';
				echo $list_html;
			}
			else
				echo '<div id="yaship_admin_print_btn_div"><h3>Label is not available</h3></div>';
		} else {
			echo"<p><h3>Cancelled Order</h3></p>";
		}
	}
	public function makeLog($filename, $data)
	{
		$myFile = YASHIP_LOGS.$filename;
		$fh = fopen( $myFile, 'a+' ) or die( "can't open file" );
		$new_data = array();
		$new_data['date'] = date("Y-m-d h:i:s");
		$new_data['data'] = $data;
		fwrite( $fh, print_r( $new_data, true ) );
		fclose( $fh );
	}
	function add_label_btn($order_id)
	{
		global $wpdb;
		$link =  esc_url($wpdb->get_var( "SELECT label_link FROM wp_yaship_data WHERE woo_order_id = '".$order_id."' AND is_deleted = 0" ));
		echo '<a href="'.$link.'"><input type="button" value="Print Label"></a>'; 
	}
}
new Yaship_Shipping_Manager();
 ?>
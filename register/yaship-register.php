<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $yaship_db_version;
$yaship_db_version = '1.0';

function yaship_install() {
	global $wpdb;
	global $yaship_db_version;
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql1 = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."yaship_trans (
		id int(11) NOT NULL AUTO_INCREMENT,
		cust_id int(11) NOT NULL,
		woo_order_id int(11) NOT NULL,
		creation_date timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
		yaship_trans_status int(1) NOT NULL,
		yaship_trans_id int(11) NOT NULL,
		track_number varchar(100),
		UNIQUE KEY id (id)
	) $charset_collate;";
	
	$sql2 = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."yaship_data(
		id int(11) NOT NULL AUTO_INCREMENT,
		woo_order_id int(11) NOT NULL,
		yaship_trans_id int(11) NOT NULL,
		creation_date timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
		label_link varchar(255),
		track_number varchar(100),
		is_deleted int(1),
		UNIQUE KEY id (id)
	) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql1 );
	dbDelta( $sql2 );
	
	add_option( 'yaship_db_version', $yaship_db_version );
	add_option( 'yaship_is_registered', 0 );
	add_option( 'woocommerece_yaship_account_mode', 0 );
	do_action( 'yaship_after_installation' );
}

if( !get_option( YASHIP_ID."_quickquote_flag" ) ){
	yaship_install();
}

add_action( 'admin_menu', 'yaship_add_registration_menu' );
function yaship_add_registration_menu() {
	add_menu_page( 'Yaship Page', 'Yaship', 'manage_options', 'yaship-doc.php', 'yaship_add_yaship_submenus' );
	add_submenu_page( 'yaship-doc.php', 'Register Your Account to Yaship', 'Register', 'manage_options', 'register-yaship-user.php', 'yaship_register_yaship_user' );
	add_submenu_page( 'yaship-doc.php', 'Transaction Report', 'Report', 'manage_options', 'report.php', 'yaship_display_report');
	add_submenu_page( 'yaship-doc.php', 'Return Shipment', 'Return Shipment', 'manage_options', 'return_list.php', 'yaship_return_shipment');
}

function yaship_add_yaship_submenus() {
	include_once( 'yaship-doc.php' );
}
function yaship_register_yaship_user() {
	include_once( 'register-yaship-user.php' );
}
function yaship_display_report() {
	include_once( 'report.php' );
}
function yaship_return_shipment() {
	include_once( 'return_list.php' );
}

add_action( 'wp_ajax_yaship_get_creditcard_form', 'yaship_get_creditcard_form' );
function yaship_get_creditcard_form() {
	$nonce = $_GET['security'];
	if ( wp_verify_nonce( $nonce, 'register_form_nonce' ) ) {
		echo '<form name="Registration_form" id = "card_update" method="post">
			<table class = "update_card"style="width:60%">
			<h3>Update Your Credit Card To Yaship</h3>
			<h3>Note:Your credit card details are securely passed to the payment gateway, we do not store any of the information related to your credit card</h3>
				<tr>
					<td style="font-size: 16px;">Card Type</td><td><input type="text" name="Card_Type" id="Card_Type" required><span id="First"></span></td>
				</tr>
				<tr>
					<td style="font-size: 16px;">Card Number</td><td><input type="number" name="Card_Number" id="Card_Number" required><span id="Middle"></span></td>
				</tr>
				<tr>
					<td style="font-size: 16px;">Card CSV</td><td><input type="number" name="Card_CSV"  id="Card_CSV"><span id="Last" required></span></td>
				</tr>
				<tr>
					<td style="font-size: 16px;">Expiry Month</td>
					<td><select name="ccexpmnth" id="ccexpmnth" style="width: 38%; font-size: 15px;">
					  <option value="01">January</option>
					  <option value="02">February</option>
					  <option value="03">March</option>
					  <option value="04">April</option>
					  <option value="05">May</option>
					  <option value="06">June</option>
					  <option value="07">July</option>
					  <option value="08">August</option>
					  <option value="09">September</option>
					  <option value="10">October</option>
					  <option value="11">November</option>
					  <option value="12">December</option>
					</select></td>
				</tr>
				<tr>
					<td style="font-size: 16px;">Expiry Year</td><td><input type="number" name="number" id="Expire_year" required><span id="Email"></span></td>
				</tr>
				<tr>
					<td style="font-size: 16px;">First Name</td><td><input type="text" id="First_Name" name="First_Name" required></td>
				</tr>
				<tr>
					<td style="font-size: 16px;">Last Name</td><td><input type="text" id="Last_Name" name="Last_Name" required></td>
				</tr>
				<tr>
					<td style="font-size: 16px;"><input type="submit" value="Submit" name="submit" id="submit_card_details" style="width: 100px;margin-top: 20px;"></td>
				</tr>
			</table>
		</form>';
	}
	wp_die();
}
/* Custom sanitize fields function */
add_action( 'wp_ajax_yaship_update_credit_card', 'yaship_update_credit_card' );
function yaship_update_credit_card() {
	$nonce = $_POST['security'];
	if ( wp_verify_nonce( $nonce, 'register_form_nonce' ) ) {
		$request = array();
		$request['cctype'] = sanitize_text_field($_REQUEST['card_type']);
		$request['cc_num'] = sanitize_text_field($_REQUEST['card_number']);
		$request['cc_csv'] = intval(sanitize_text_field($_REQUEST['card_csv']));
		$request['ccexpmnth'] = sanitize_text_field($_REQUEST['expire_month']);
		$request['ccexpyear'] = intval(sanitize_text_field($_REQUEST['expire_year']));
		$request['ccfname'] = sanitize_text_field($_REQUEST['first_name']);
		$request['cclname'] = sanitize_text_field($_REQUEST['last_name']);
		$user_details = get_option( 'yaship_user' );
		$request['uemail'] = sanitize_email($user_details['email']);
		
		$endpoint = "http://www.yaship.com/api/api/update_card";
		
		$response = wp_remote_post( $endpoint,
			array(
				'method' => 'POST',
				'timeout'   => 70,
				'sslverify' => 0,
				'body'      => $request,
				'cookies' => array()
			)
		); 
		$resp = json_decode( $response['body'], true );
		$resp = json_encode( array( "result" => $resp['message'] ) );
		echo $resp;
	}
	wp_die();
}
add_action( 'wp_ajax_yaship_save_mode', 'yaship_save_mode' );
function yaship_save_mode() {
	$nonce = $_POST['security'];
	if ( wp_verify_nonce( $nonce, 'register_form_nonce' ) ) {
		$user_details = get_option( 'yaship_user' );
	
		$request = array();
		$mode = sanitize_text_field( $_REQUEST['mode'] );
		$request['mode'] = $mode;
		
		if ( !empty( $user_details ) ) {
			$request['uemail'] = $user_details['email'];
			$endpoint = "http://www.yaship.com/api/api/update_mode";
			
			$response = wp_remote_post( $endpoint,
				array(
					'method' => 'POST',
					'timeout'   => 70,
					'sslverify' => 0,
					'body'      => $request,
					'cookies' => array()
				)
			);
			if( $response['body'] ) {
				$resp = json_decode( $response['body'], true );
				if( $resp['success'] ) {
					update_option( 'woocommerece_yaship_account_mode', $mode );
					$user_details['account_mode'] = $mode;
					update_option( 'yaship_user', $user_details );
				}
			}
			echo $resp['message'];
		} else {
			update_option( 'woocommerece_yaship_account_mode', $mode );
			if($mode == 1)
				echo "Your account has been updated to production mode";
			else 
				echo "Your account has been updated to test mode";
		}
	}
	wp_die();
} 

add_action( 'wp_ajax_yaship_update_information', 'yaship_update_information' );
function yaship_update_information() {
	$nonce = $_POST['security'];
	if ( wp_verify_nonce( $nonce, 'register_form_nonce' ) )
	{
		// valid nonce
		$request = array();
		$request['ufname'] = sanitize_text_field($_REQUEST['user_fname']);
		$request['ulname'] = sanitize_text_field($_REQUEST['user_lname']);
		$request['uemail'] = sanitize_email($_REQUEST['user_email']);
		$request['uphone'] = sanitize_text_field($_REQUEST['user_phone']);
		$request['site_url'] = esc_url($_REQUEST['user_site_url']);
		$request['uaddr'] = sanitize_text_field($_REQUEST['user_addr']);
		$request['ucity'] = sanitize_text_field($_REQUEST['user_state']);
		$request['ustate'] = sanitize_text_field($_REQUEST['user_city']);
		$request['ucontry'] = sanitize_text_field($_REQUEST['user_country']);
		$request['upo_code'] = sanitize_text_field($_REQUEST['user_post_code']);
		
		$url = "http://www.yaship.com/api/api/update_user";

		$data = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' => $request,
			'cookies' => array()
		);
		$resp = wp_remote_post( $url, $data );
		$response = json_decode( $resp['body'], true );
		
		if ( $response['success'] == "1")
		{
			$user = array();
			$user['fname'] = $request['ufname'];
			$user['lname'] = $request['ulname'];
			$user['email'] = $request['uemail'];
			$user['phone'] = $request['uphone'];
			$user['site_url'] = $request['site_url'];
			$user['address'] = $request['uaddr'];
			$user['city'] = $request['ucity'];
			$user['state'] = $request['ustate'];
			$user['country'] = $request['ucontry'];
			$user['postal_code'] = $request['upo_code'];
			$user['account_mode'] = get_option( 'woocommerece_yaship_account_mode', $default = false );
			update_option( 'yaship_user', $user);
		}
		echo $response['message'];
	}
	wp_die();
}
add_action( 'wp_ajax_yaship_calculate_quick_quote', 'yaship_calculate_quick_quote' );
function yaship_calculate_quick_quote() {
	$nonce = $_POST['security'];
	if ( wp_verify_nonce( $nonce, 'register_form_nonce' ) ) {
		$services = array(
			"03" => "UPS Ground",
			"12" => "UPS 3 Day Select",
			"02" => "UPS 2nd Day Air",
			"59" => "UPS 2nd Day Air AM",
			"01" => "UPS Next Day Air",
			"13" => "UPS Next Day Air Saver",
			"14" => "UPS Next Day Air Early AM",
		);
		/* get quick quote rate */
		$request = array();
		$request['to_code'] = sanitize_text_field($_POST['to']);
		$request['from_code'] = sanitize_text_field($_POST['from']);
		$request['length'] = floatval(sanitize_text_field($_POST['length']));
		$request['height'] = floatval(sanitize_text_field($_POST['height']));
		$request['width'] = floatval(sanitize_text_field($_POST['width']));
		$request['weight'] = floatval(sanitize_text_field($_POST['weight']));
		
		$endpoint = "http://www.yaship.com/api/api/qc_rate";
		
		  $response = wp_remote_post($endpoint,
			array(
				'method' => 'POST',
				'timeout'   => 70,
				'sslverify' => 0,
				'body'      => $request,
				'cookies' => array()
			)
		);
		
		if( !empty( $response['body'] ) ) {
			$result = json_decode( $response['body'], true );
			
			if( $result['success'] )
			{
				$str = '<p style="color: rgb(0,0,255);text-transform: capitalize;font-size: 30px"><strong>See below for rates</strong></p><p><h2>Cart Totals</h2></p>
				<div id="qc-result-data-div"><p><strong>Pickup from: </strong>' . sanitize_text_field($_POST['from']) . '</p>
				<p><strong>Ship to: ' . sanitize_text_field($_POST['to']) . '</p>';
				
				foreach( $result['response'] as $key => $val )
				{
					$ser_name = $services[ $key ];
					if ( isset( $ser_name ) ) {
						$str = $str . '<p><label ><strong>Total Charges for  '.$ser_name.': </strong><span>'.$val.' </span></label></p>';
					}
					$ser_name='';
				}
				$str = $str .'</div>';
				$resp = json_encode( array( "result"=>$str ) );
				echo $resp;
				
			} else {
				echo json_encode( array( "error"=>$result['message'] ) );
			}	
		} else {
			echo json_encode( array( "error"=>"Invalid Response" ) );
		}
	}
	wp_die();
}

add_action( 'wp_ajax_yaship_call_return_shipment', 'yaship_call_return_shipment' );
function yaship_call_return_shipment() {
	$order_id= intval(sanitize_text_field($_REQUEST['order_id']));
	
	$items=( stripslashes( sanitize_text_field($_REQUEST['msg'] ) ) );
	$items=json_decode( $items, true );
	
	$shipper_details = get_option( 'woocommerce_'.YASHIP_ID.'_settings' );
	if ( !empty( $shipper_details ) ) {
		$unit = $shipper_details['units'];
		if ( $unit == "imperial" ) {
			$weight_unit="LB";
			$dim_unit="IN";
		} elseif ( $unit=="metric" ) {
			$weight_unit="KG";
			$dim_unit="CM";
		}
		$api_key=$shipper_details['api_key'];
		$acc_no=$shipper_details['account_number'];
		$shipper=array();
		$shipper['name'] = $shipper_details['first_name']." ".$shipper_details['last_name'];
		//$shipper['addr'] = $shipper_details['origin_addressline'];
		$shipper['addr'] = $shipper_details['origin_addressline'];
		$shipper['city'] = $shipper_details['origin_city'];
		$shipper['state'] = $shipper_details['origin_country_state'];
		$shipper['po_code'] = $shipper_details['origin_postcode'];
		$shipper['phone'] = $shipper_details['origin_phone'];
		
		$shipTo=array();
		$shipTo['company'] = $shipper_details['origin_company'];
		$shipTo['phone'] = $shipper_details['origin_phone'];
		$shipTo['addr'] = $shipper_details['origin_addressline'];
		$shipTo['city'] = $shipper_details['origin_city'];
		$shipTo['state'] = $shipper_details['origin_country_state'];
		$shipTo['po_code'] = $shipper_details['origin_postcode'];
		
		$shipFrom=array();
		$shipFrom['name'] = get_post_meta( $order_id, '_shipping_first_name', true)." ".get_post_meta( $order_id, '_shipping_last_name', true );
		$shipFrom['addr'] = get_post_meta( $order_id, '_shipping_address_1', true)." ".get_post_meta( $order_id, '_shipping_address_2', true );
		$shipFrom['city'] = get_post_meta( $order_id, '_shipping_city', true );
		$shipFrom['state'] = get_post_meta( $order_id, '_shipping_state', true );
		$shipFrom['po_code'] = get_post_meta( $order_id, '_shipping_postcode',true );
		$shipFrom['country'] = get_post_meta( $order_id, '_shipping_country', true );
	} else {
		wp_die();
	}
	$packages = array();
	$total_return_pkg = 0;
	foreach ( $items as $key => $val ) {
		$_pf = new WC_Product_Factory();
		$_product = $_pf->get_product( $key );
		
		$weight = woocommerce_get_weight( $_product->get_weight(), $weight_unit );

		// get package dimensions
		if ( $_product->length && $_product->height && $_product->width ) {

			$dimensions = array( 
				number_format( woocommerce_get_dimension( $_product->length, $dim_unit ), 2, '.', ''),
				number_format( woocommerce_get_dimension( $_product->height, $dim_unit ), 2, '.', ''),
				number_format( woocommerce_get_dimension( $_product->width, $dim_unit ), 2, '.', '') );
				sort( $dimensions );
		}
		$packages[$key]['length'] = $dimensions[2];
		$packages[$key]['width'] = $dimensions[1];
		$packages[$key]['height'] = $dimensions[0];
		$packages[$key]['weight'] = $weight;
		$packages[$key]['quantity'] = $val;
		$total_return_pkg = $total_return_pkg + $val;//Used to find is the same return request from same order id
	}
	if ( !empty( $packages ) && !empty( $shipper ) && !empty( $shipTo ) && !empty($shipFrom ) && $api_key && $acc_no ) {
		$request['packages']=$packages;
		$request['api_key']=$api_key;
		$request['account_number']=$acc_no;
		$request['service']='03';
		$request['packageDescription']='Return Package';
		$request['shipper']=$shipper;
		$request['shipTo']=$shipTo;
		$request['shipFrom']=$shipFrom;
		$request['mode']=0;
		$response = get_post_meta($order_id,"_yaship_return_labels",true);

		$html = "<table class='display' style='border: 1px solid black;
    border-collapse: collapse;' border='1'><thead><th>Product Id</th><th>Tracking Number</th><th>Label Link</th><th>Charges</th></thead><tbody>"; 
		
		if ( empty ( $response ) || ( sizeof( $response ) != $total_return_pkg ) ) {
			//also dought here about url
			$endpoint = "http://www.yaship.com/api/api/return_pkg";
			$response = wp_remote_post( $endpoint,
								array(
									'method' => 'POST',
									'timeout'   => 70,
									'sslverify' => 0,
									'body'      => $request,
									'cookies' => array()
								)
							);
			if( $response['body'] ) {
				$resp = json_decode( $response['body'], true );
				if( $resp['success'] ) {
					$result = $resp['result'];
					
					$total_charges = 0;
					$post_ary = array();
					foreach ( $result as $key => $temp_ary ) {
						for ( $i=0; $i<sizeof( $temp_ary ); $i++ ) {
							$data = $temp_ary[$i]['data'];
							if ( $data ) {
								//print_r($data);
								$shipper_details = get_option('woocommerce_'.YASHIP_ID.'_settings');
								$print_code = $shipper_details['print_option'];
								$label_link = $data['label_link'];
								if ( $print_code == "002" ) {
									$label_link = substr( $label_link, 0, ( strlen($label_link )-strlen( strrchr( $label_link, "/") ) ) )."/label".$data['tracking_number'].".gif";
								}
								$html = $html . "<tr><td>$key</td><td>". $data['tracking_number']."</td><td><a href='".$label_link."' target='_blank'>".$label_link."</a></td><td>".$data['charges'] . "</td></tr>";
								$total_charges = $total_charges + $data['charges'];
								$post_ary[$key][$i]= array( $label_link, $data['tracking_number'], $data['charges'] );
							} else {
								$post_ary[$key][$i] = array();
								//Label is not generated for this product
								$html=$html . "<tr><td colspan='2'>$key</td><td colspan='2'>Could not return</td></tr>";
							}
						}
					}
					update_post_meta($order_id,"_yaship_return_labels",$post_ary);
					$html = $html . "<tr><td colspan='3' >Total</td><td>".$total_charges ."</td></tr>";
				} else {
					//Invalid Response
					$html=$html . "Invalid Response";
				}
			} else {
				//No RESPONSE
				$html=$html . "No RESPONSE";
			}
		} else {
			$total_charges = 0;
			foreach ( $response as $key => $val ) {
				for ($i=0; $i<sizeof( $val ); $i++) {
					if( !empty( $val[$i] ) ) {
						$html=$html . "<tr><td>$key</td><td>". $val[$i][1]."</td><td><a href='".$val[$i][0]."' target='_blank'>".$val[$i][0]."</a></td><td>".$val[$i][2] . "</td></tr>";
						$total_charges = $total_charges + $val[$i][2];
					} else {
						$html=$html . '<tr><td colspan="2">$key</td><td colspan="2">Could not return</td></tr>';
					}
				}
				
			}
			$html = $html . "<tr><td colspan='3' >Total</td><td>".$total_charges ."</td></tr>";
		}
		$html=$html."</tbody><table>";
		echo $html;
	}
	wp_die();
}
add_action( 'wp_ajax_display_product', 'yaship_display_product' );
function yaship_display_product() {
	/**
	*CHECK ORDER ID IS SET
	*/
	if ( isset ($_REQUEST['order_id']  ) ) {
		$order = new WC_Order( intval( sanitize_text_field( $_REQUEST['order_id'] ) ) );
		$shipping_methods = $order->get_shipping_methods();
		$id = intval( sanitize_text_field( $_REQUEST['order_id'] ) );
		/**
		*CHECK FOR VALID ORDER ID  
		*CHECK FOR COMPLETEED STATUS 
		*CHECK IF SHIPPNG IS ENABLED
		*/
		if ( isset ( $order->post ) && ( $order-> get_status() == 'completed' ) &&!empty( $shipping_methods ) ) {
			/**
			*CHECK FOR YASHIP_SHIPPNG IS USED
			**/
			$has_method = false;
			foreach ( $shipping_methods as $shipping_method ) {
				if ( strstr( $shipping_method['method_id'], 'Yaship_Shipping' ) )
				{
					$has_method = true;
					break;
				}
			}
			if( $has_method ) {
				/**
				*RETRIVE ALL PRODUCT DATA 
				*/
				$items = $order->get_items();
				$product_list = array();//<form id=sh_light_box_frm></form>
				$counter = 0;
				$html="";
				$act = admin_url( 'return_list.php' );
				$html = $html."<form action='".$act."' name='sh_light_box_frm' id='sh_light_box_frm'><input type='hidden' value='admin-ajax.php?action=yaship_call_return_shipment' class='sh_ajx_file1' id='".$id."' ><table><thead>
					<th>Please Select</th>
					<th>Item ID</th>
					<th>Item Name</th>
					<th>Quantity</th>
					<th>Price</th>
					<th>Return Quantity<th>
				</thead>
				<tbody>";
				foreach ( $items as $key => $item ) {
					$i_id = $item['product_id'];
					$i_name = $item['name'];
					$i_qty = $item['qty'];
					$i_pr = $item['line_subtotal'];
					$html = $html.'<tr>
					<td>
						<input type="checkbox" id="'.$i_id.'" name="items[]" class="chk">
					</td>
					<td>
						<label name="item_id" id="iid_'.$i_id .'">'. $i_id.'
						</label>
					</td>
					<td>
						<label name="item_name" id="inm_'.$i_name.'" >'.$i_name.'
						</label>
					</td>
					<td>
						<label name="item_id" id="iqt_'.$i_qty.'">'. $i_qty.' 
					</label>
					</td>
					<td>
						<label name="item_id" id="ipr_'.$i_pr.'">'.$i_pr.'
					</label>
					</td>
					<td><input type="number" id="tx_'.$i_id.'" value="1" name="return[]"  min="1" max="'.$i_qty.'"></td></tr>';
				}
				$html = $html."</tbody></table><input type='button' id='ret_ship_submmit' value='submit'></form>";
				echo $html;
			}
		}
	} 
	wp_die();
}
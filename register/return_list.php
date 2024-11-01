<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
//user logged in and if he is admin
if ( is_user_logged_in() ) {
	if( current_user_can( 'administrator' ) ) { // only if administrator
		global $wpdb;
		global $post;
		$result = $wpdb->get_results( "SELECT `ID` FROM wp_posts WHERE post_type='shop_order'" ); ?>
		
		<body>
		<h3>Yaship Return Shipment</h3>
		<div class="display_return_ship_response">
			<input type="hidden" value="admin-ajax.php?action=display_product" class="sh_ajx_file" >
			<table id="example" class="yaship-report" cellspacing="0" width="100%">
				<thead style="text-align: left;">
					<th>Sr. No.</th>
					<th>Order ID</th>
					<th>Select Service</th>
					<th>status</th>
					<th>Return From Address</th>
					<th>Return</th>
				</thead> <?php 
				if ( ! empty ($result) ) {
					$ctr = 0;
					foreach( $result as $key => $row ) {
						$order_data = get_post_meta($row->ID);
						$order = new WC_order($row->ID);
						$shipping_methods = $order->get_shipping_methods();
						if ( $shipping_methods ) {
							$shipping_method = array_shift( $shipping_methods );
							$shipping_service_tmp_data	= explode( ':',$shipping_method['method_id'] );
							if($shipping_service_tmp_data[0] === "Yaship_Shipping" && substr(get_post_status( $row->ID ),3)=='completed'){
								//var_dump($shipping_method);
								?>
								<tr>
								<td><?php echo ++$ctr;?></td>
								<td><?php echo $row->ID;?></td>
								<td><?php echo $shipping_method['name'];//display all services here?></td>
								<td><?php echo substr(get_post_status( $row->ID ),3);?></td>
								<td><?php echo $order->get_formatted_shipping_address( );?></td>
								<td><input type="button" class="sh_return_shipment" id="<?php echo $row->ID;?>" value="Return Ship">
								</td>
								</tr>
							  <?php	
								
							} else {
								//SKIP AS IT IS NOT YASHIP SHIPPING METHOD
							}
						} else {
							//SKIP AS TIS ORDER DOES NOT CONTAIN ANY SHIPPING METHOD
						}
					}
				} else {
					echo "<br>Records are not available";
				} ?>
			</table>
		</div>
		<div class="display_product_view">product display</div>
	</body> <?php
	}
}
?>

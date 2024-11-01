<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
//user logged in and if he is admin
if ( is_user_logged_in() ) {
	if( current_user_can( 'administrator' ) ) { // only if administrator
		global $wpdb;
		$result = $wpdb->get_results( "SELECT `ID` FROM wp_posts WHERE post_type='shop_order'" ); 

		$url = admin_url()."admin.php?page=register-yaship-user.php"; ?>
		<html>
		<head>
			<h3>Yaship Transaction Report</h3>
		</head>
		<table id="example" class="yaship-report" cellspacing="0" width="100%">
			<thead style="text-align: left;">
				<th>Sr. No.</th>
				<th>Order ID</th>
				<th>User ID</th>
				<th>Service</th>
				<th>Amount</th>
				<th>status</th>
				<th>Billing Address</th>
				<th>Shipping Address</th>
				<th>Date</th>
			</thead>
			<?php if ( ! empty($result ) ) {
					$ctr = 0;
					foreach( $result as $key => $row ) {
						$order_data = get_post_meta($row->ID);
						$order = new WC_order($row->ID);
						$shipping_methods = $order->get_shipping_methods();
						if ( $shipping_methods ) {
							$shipping_method = array_shift( $shipping_methods );
							$shipping_service_tmp_data	= explode( ':',$shipping_method['method_id'] );
							if ( $shipping_service_tmp_data[0] === "Yaship_Shipping" ) {
								?>
								<tr>
								<td><?php echo ++$ctr;?></td>
								<td><?php echo $row->ID;?></td>
								<td><?php echo $order->get_user_id();?></td>
								<td><?php echo $shipping_method['name'];?></td>
								<td><?php 
									$t_cost = get_post_meta ( $row->ID, 'Yaship_Shipping_fsr', true );
									print_r($t_cost);
									if ( ! empty ($t_cost) )
										echo $t_cost;
									else
										echo $shipping_method['cost'];
								?></td>
								<td><?php echo substr(get_post_status( $row->ID ),3);?></td>
								<td><?php echo $order->get_formatted_billing_address( );?></td>
								<td><?php echo $order->get_formatted_shipping_address( );?></td>
								<td><?php echo get_the_date( "Y/m/d", $row->ID );?></td>
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
		</html> <?php
	}
} else {
	// your code for logged out user 
	echo "<br>You must be logged in";
}
?>


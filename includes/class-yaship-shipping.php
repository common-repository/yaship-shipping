<?php
if ( ! class_exists( 'Yaship_Shipping' ) ) :
	class Yaship_Shipping extends WC_Shipping_Method {
		private $services = array(
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
	
	private $print_options = array(
		"001" => "INK JET/LASER PRINTER",
		"002" => "THERMAL PRINTER",
	);
	
	private $email_label_link = array(
		"001" => "EMAIL LABEL LINK WITH PRINT LABEL AT ORDER PAGE",
		"002" => "PRINT LABEL AT ORDER PAGE",
	);

	private $packaging_select = array(
		"01" => "Letter",
		"03" => "Tube",
		"24" => "25KG Box",
		"25" => "10KG Box",
		"2a" => "Small Express Box",
		"2b" => "Medium Express Box",
		"2c" => "Large Express Box",
	);
		
	/**
	* Constructor for Yaship_Shipping shipping class
	*
	* @access public
	* @return void
	*/	
	public function __construct() {
		$this->id = YASHIP_ID; // Id for your shipping method. Should be unique.
		
		$this->method_title = __( 'Yaship', 'yaship' );  // Title shown in admin
		
		$this->method_description = __( 'The <strong>yaship</strong> Shipping process allow to ship package and get label of shipment.', 'yaship' ); // Description shown in admin

		$this->enabled = "yes"; // This can be added as an setting but for this example its forced enabled
		
		$this->title = "Yaship Shipping"; // This can be added as an setting but for this example its forced.

		$this->init();
	}
		/**
	 * Output a message or error
	 * @param  string $message
	 * @param  string $type
	 */
    public function debug( $message, $type = 'notice' ) {
        // Hard coding to 'notice' as recently noticed 'error' is breaking with wc_add_notice.
        $type = 'notice';
    	if ( $this->debug && !is_admin() ) { //WF: do not call wc_add_notice from admin.
    		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
    			wc_add_notice( $message, $type );
    		} else {
    			global $woocommerce;
    			$woocommerce->add_message( $message );
    		}
		}
    }
	/**
	* Init your settings
	*
	* @access public
	* @return void
	*/
	function init() {
		// Load the settings API
		$this->init_form_fields();
		
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
		
		//HERE $this->settings ARRAY IS INITIALISE
		$this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : $this->enabled;
		
		$this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->method_title;
		
		$this->availability = isset( $this->settings['availability'] ) ? $this->settings['availability'] : 'US';
		
		$this->debug = isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;
		
		// Account Settings 
		$this->api_key = isset( $this->settings['api_key'] ) ? $this->settings['api_key'] : '';
		$this->account_number = isset( $this->settings['account_number'] ) ? $this->settings['account_number'] : '';
		
		// User settings
		$this->first_name = isset( $this->settings['first_name'] ) ? $this->settings['first_name'] : '';
		$this->last_name = isset( $this->settings['last_name'] ) ? $this->settings['last_name'] : '';
		$this->origin_phone = isset( $this->settings['origin_phone'] ) ? $this->settings['origin_phone'] : '';
		$this->origin_company = isset( $this->settings['origin_company'] ) ? $this->settings['origin_company'] : '';
		$this->origin_email = isset( $this->settings['origin_email'] ) ? $this->settings['origin_email'] : '';
	
		// Ship from address settings
		$this->ship_from_address = isset( $this->settings['ship_from_address'] ) ? $this->settings['ship_from_address'] : 'shipping_address';
		$this->origin_addressline 	= isset( $this->settings['origin_addressline'] ) ? $this->settings['origin_addressline'] : '';
		$this->origin_city = isset( $this->settings['origin_city'] ) ? $this->settings['origin_city'] : '';
		$this->origin_postcode = isset( $this->settings['origin_postcode'] ) ? $this->settings['origin_postcode'] : '';
		$this->origin_country_state = isset( $this->settings['origin_country_state'] ) ?$this->settings['origin_country_state'] : '';
		
		// Carrier settings
		$this->custom_services  = isset( $this->settings['services'] ) ? $this->settings['services'] : array();
		
		$this->res_addr = isset( $this->settings['res_addr'] ) && $this->settings['res_addr'] == 'yes' ? true : false;
		
		$this->units = isset( $this->settings['units'] ) ? $this->settings['units'] : 'imperial';
		if ( $this->units == 'metric' ) 
		{
			$this->weight_unit = 'KGS';
			$this->dim_unit    = 'CM';
		} else {
			$this->weight_unit = 'LBS';
			$this->dim_unit    = 'IN';
		}
		
		// Services and Packaging
		$this->offer_rates = isset( $this->settings['offer_rates'] ) ? $this->settings['offer_rates'] : 'all';
		$this->fallback = ! empty( $this->settings['fallback'] ) ? $this->settings['fallback'] : '';
		
		$this->packing_method = isset( $this->settings['packing_method'] ) ? $this->settings['packing_method'] : 'per_item';
		$this->yaship_packaging	= isset( $this->settings['yaship_packaging'] ) ? $this->settings['yaship_packaging'] : array();
		
		$this->boxes = isset( $this->settings['boxes'] ) ? $this->settings['boxes'] : array();
		
		$this->print_option = isset( $this->settings['print_option'] ) ? $this->settings['print_option'] : array();
		$this->email_label_link = isset( $this->settings['email_label_link'] ) ? $this->settings['email_label_link'] : array();
		
		// Save settings in admin if you have any defined
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}
		/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		// Check users environment supports this method
		$this->environment_check();

		// Show settings
		parent::admin_options();
	}
	/**
	 * environment_check function.
	 *
	 * @access public
	 * @return void
	 */
	private function environment_check() {
		global $woocommerce;
		$error_message = '';

		// Check for Yaship Api_Key
		if ( ! $this->api_key && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the ShipIt api_key has not been set.', 'yaship' ) . '</p>';
		}

		// Check for last_name
		if ( ! $this->last_name && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the last_name has not been set.', 'yaship' ) . '</p>';
		}
		
		// Check for first_name
		if ( ! $this->first_name && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the first_name has not been set.', 'yaship' ) . '</p>';
		}
		
		// Check for origin_phone
		if ( ! $this->origin_phone && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the origin_phone has not been set.', 'yaship' ) . '</p>';
		}
		
		// Check for origin_company
		if ( ! $this->origin_company && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the origin_company has not been set.', 'yaship' ) . '</p>';
		}
		
		// Check for origin_email
		if ( ! $this->origin_email && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the origin_email has not been set.', 'yaship' ) . '</p>';
		}
		
		//Check for print option set or not
		if ( ! $this->print_option && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but any label print option has not been set.', 'yaship' ) . '</p>';
		}

		// Check for account_number
		if ( ! $this->account_number && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the ShipIt account Number has not been set.', 'yaship' ) . '</p>';
		}
		
		// Check for origin_addressline 
		if ( ! $this->origin_addressline  && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the address has not been set.', 'yaship' ) . '</p>';
		}

		// Check for City 
		if ( ! $this->origin_city  && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the City has not been set.', 'yaship' ) . '</p>';
		}
		// Check for Origin Postcode
		if ( ! $this->origin_postcode && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the origin postcode has not been set.', 'yaship' ) . '</p>';
		}

		// Check for Origin country
		if ( ! $this->origin_country_state && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but the origin country/state has not been set.', 'yaship' ) . '</p>';
		}

		// Check for at least one service enabled
		$ctr=0;
		if ( isset($this->custom_services ) && is_array( $this->custom_services ) ){
			foreach ( $this->custom_services as $key => $values ){
				if ( $values['enabled'] == 1)
					$ctr++;
			} 
		}
		
		if ( ( $ctr == 0 ) && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'yaship is enabled, but there are no services enabled.', 'yaship' ) . '</p>';
		}

		// If user has selected to pack into boxes,
		if ( ( $this->packing_method == 'box_packing' ) && ( $this->enabled == 'yes' ) ) {
			if ( empty( $this->yaship_packaging )  && empty( $this->boxes ) ){
				$error_message .= '<p>' . __( 'Yaship is enabled, and Parcel Packing Method is set to \'Box Packaging\', but no yaship standerd Package is selected and there are no custom boxes defined. Items will be packed individually.', 'yaship' ) . '</p>';
			}
		}
		
		if ( ! $error_message == '' ) {
			echo '<div class="error">';
			echo $error_message;
			echo '</div>';
		}
		else //send for validation of api key,account_num and address
		{
			$api_key = $this->api_key;
			$account_number = $this->account_number;
			
			$address = array();
			$address['address1'] = $this->origin_addressline;
			$address['city'] = $this->origin_city;
			$address['state'] = $this->origin_country_state;
			$address['po_code'] = $this->origin_postcode;
			
			$request = array();
			$request['api_key'] = $api_key;
			$request['account_number'] = $account_number;
			$request['address'] = $address;
			
			$endpoint = "http://www.yaship.com/api/api/validate";

			$response = wp_remote_post( $endpoint,
							array(
								'method' => 'POST',
								'timeout'   => 70,
								'sslverify' => 0,
								'body'      => $request,
								'cookies' => array()
							)
						);
			$response = json_decode( $response['body'], true );
			
			if( isset( $response->success )&& !$response->success )
			{
				echo '<div class="error" style="color: red;">';
				echo $response['message'];
				echo '</div>';
			}
			/* Check if user details are present or not */
			
			$user_info =  get_option('yaship_user');
			if ( isset( $user_info ) && empty( $user_info ) ) {
				$this->updateUserInfo( $this->api_key, $this->account_number );
			}
		}
	}
	public function updateUserInfo( $api_key, $account_number )
    {
        $request = array();
        $request['api_key'] = $api_key;
        $request['account_number'] = $account_number;
        
        $endpoint = "http://www.yaship.com/api/api/get_user_info";
        
		$response = wp_remote_post( $endpoint,
							array(
								'method' => 'POST',
								'timeout'   => 70,
								'sslverify' => 0,
								'body'      => $request,
								'cookies' => array()
							)
						);
			
		$resp = (array)json_decode( $response['body'] );
		
		if( isset( $resp['success'] ) && $resp['success'] == 1 ) {
			update_option( 'yaship_user', $resp );
			if ( $resp['is_live_registered'] == 1 ) {
				update_option( 'yaship_is_registered', 1 );
			} else {
				update_option( 'yaship_is_registered', 0 );
			}
		}
	}
	/**
	 * validate_box_packing_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_box_packing_field( $key ) {

		$boxes = array();

		if ( isset( $_POST['boxes_outer_length'] ) ) {
			$boxes_outer_length = floatval( sanitize_text_field( $_POST['boxes_outer_length'] ) );
			$boxes_outer_width  = floatval( sanitize_text_field( $_POST['boxes_outer_width'] ) );
			$boxes_outer_height = floatval( sanitize_text_field( $_POST['boxes_outer_height'] ) );
			$boxes_inner_length = floatval( sanitize_text_field( $_POST['boxes_inner_length'] ) );
			$boxes_inner_width  = floatval( sanitize_text_field( $_POST['boxes_inner_width'] ) );
			$boxes_inner_height = floatval( sanitize_text_field( $_POST['boxes_inner_height'] ) );
			$boxes_box_weight   = floatval( sanitize_text_field( $_POST['boxes_box_weight'] ) );
			$boxes_max_weight   = floatval( sanitize_text_field( $_POST['boxes_max_weight'] ) );


			for ( $i = 0; $i < sizeof( $boxes_outer_length ); $i ++ ) {

				if ( $boxes_outer_length[ $i ] && $boxes_outer_width[ $i ] && $boxes_outer_height[ $i ] && $boxes_inner_length[ $i ] && $boxes_inner_width[ $i ] && $boxes_inner_height[ $i ] ) {

					$boxes[] = array(
						'outer_length' => floatval( $boxes_outer_length[ $i ] ),
						'outer_width'  => floatval( $boxes_outer_width[ $i ] ),
						'outer_height' => floatval( $boxes_outer_height[ $i ] ),
						'inner_length' => floatval( $boxes_inner_length[ $i ] ),
						'inner_width'  => floatval( $boxes_inner_width[ $i ] ),
						'inner_height' => floatval( $boxes_inner_height[ $i ] ),
						'box_weight'   => floatval( $boxes_box_weight[ $i ] ),
						'max_weight'   => floatval( $boxes_max_weight[ $i ] ),
					);

				}

			}
		}
		return $boxes;
	}
	/**
	 * validate_services_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_services_field( $key ) {
		$services         = array();
		foreach ( $_POST['yaship_service'] as $code => $settings ) {

			$services[ $code ] = array(
				'name' => sanitize_text_field( $settings['name'] ),
				'enabled' => isset( $settings['enabled'] ) ? true : false,
				'fsr' =>(isset($settings['enabled'])&&isset($settings['fsr'])) ? floatval( $settings['fsr'] ) : 0,
			);
		}
		return $services;
	}
	
	
	/**
	 * generate_box_packing_html function.
	 *
	 * @access public
	 * @return void
	 */
	public function generate_box_packing_html() {
		ob_start(); ?>
		<tr valign="top" id="packing_options">
			<td class="forminp" colspan="2" style="padding-left:0px">
				<strong><?php _e( 'Custom Box Dimensions', 'yaship' ); ?></strong><br/>
				<table class="yaship-boxes widefat">
					<thead>
						<tr>
						<th class="check-column"><input type="checkbox" /></th>
						<th><?php _e( 'Outer Length', 'yaship' ); ?></th>
						<th><?php _e( 'Outer Width', 'yaship' ); ?></th>
						<th><?php _e( 'Outer Height', 'yaship' ); ?></th>
						<th><?php _e( 'Inner Length', 'yaship' ); ?></th>
						<th><?php _e( 'Inner Width', 'yaship' ); ?></th>
						<th><?php _e( 'Inner Height', 'yaship' ); ?></th>
						<th><?php _e( 'Box Weight', 'yaship' ); ?></th>
						<th><?php _e( 'Max Weight', 'yaship' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="3">
								<a href="#" class="button plus insert"><?php _e( 'Add Box', 'yaship' ); ?></a>
								<a href="#" class="button minus remove"><?php _e( 'Remove selected box(es)', 'yaship' ); ?></a>
							</th>
							<th colspan="6">
								<small class="description"><?php _e( 'Items will be packed into these boxes depending based on item dimensions and volume. Outer dimensions will be used for shipping, whereas inner dimensions will be used for packing. Items not fitting into boxes will be packed individually.', 'yaship' ); ?></small>
							</th>
						</tr>
					</tfoot>
					<tbody id="rates">
					<?php if ( $this->boxes && ! empty( $this->boxes ) ) {
							foreach ( $this->boxes as $key => $box ) { ?>
						<tr>
							<td class="check-column"><input type="checkbox" /></td>
							<td><input type="text" size="5" name="boxes_outer_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_length'] ); ?>" /><?php echo $this->dim_unit; ?></td>
							<td><input type="text" size="5" name="boxes_outer_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_width'] ); ?>" /><?php echo $this->dim_unit; ?></td>
							<td><input type="text" size="5" name="boxes_outer_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_height'] ); ?>" /><?php echo $this->dim_unit; ?></td>
							<td><input type="text" size="5" name="boxes_inner_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_length'] ); ?>" /><?php echo $this->dim_unit; ?></td>
							<td><input type="text" size="5" name="boxes_inner_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_width'] ); ?>" /><?php echo $this->dim_unit; ?></td>
							<td><input type="text" size="5" name="boxes_inner_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_height'] ); ?>" /><?php echo $this->dim_unit; ?></td>
							<td><input type="text" size="5" name="boxes_box_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['box_weight'] ); ?>" /><?php echo $this->weight_unit; ?></td>
							<td><input type="text" size="5" name="boxes_max_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['max_weight'] ); ?>" /><?php echo $this->weight_unit; ?></td>
						</tr> <?php
							}
						}
					?>
					</tbody>
				</table>
				<script type="text/javascript">
				jQuery(window).load(function(){
					jQuery('.yaship-boxes .insert').click( function() {
						var $tbody = jQuery('.yaship-boxes').find('tbody');
						var size = $tbody.find('tr').size();
						var code = '<tr class="new">\
								<td class="check-column"><input type="checkbox" /></td>\
								<td><input type="text" size="5" name="boxes_outer_length[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
								<td><input type="text" size="5" name="boxes_outer_width[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
								<td><input type="text" size="5" name="boxes_outer_height[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
								<td><input type="text" size="5" name="boxes_inner_length[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
								<td><input type="text" size="5" name="boxes_inner_width[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
								<td><input type="text" size="5" name="boxes_inner_height[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
								<td><input type="text" size="5" name="boxes_box_weight[' + size + ']" /><?php echo $this->weight_unit; ?></td>\
								<td><input type="text" size="5" name="boxes_max_weight[' + size + ']" /><?php echo $this->weight_unit; ?></td>\
							</tr>';

						$tbody.append( code );
						return false;
					});

					jQuery('.yaship-boxes .remove').click(function() {
						var $tbody = jQuery('.yaship-boxes').find('tbody');

						$tbody.find('.check-column input:checked').each(function() {
							jQuery(this).closest('tr').hide().find('input').val('');
						});

						return false;
					});
				});
				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}
	
		/**
	 *
	 * generate_single_select_country_html function
	 *
	 * @access public
	 * @return void
	 */
	function generate_origin_country_state_html() {
		global $woocommerce;
		$ctr = array();
		ob_start();
		?>
		<tr valign="top">
			<th scope="row">
				<label for="origin_country_state"><?php _e( 'Origin State', 'yaship' ); ?></label>
			</th>
            <td class="forminp"><select name="origin_country_state" id="origin_country_state" style="width: 250px;" placeholder="<?php _e('Choose a state;', 'yaship'); ?>" title="State" class="chosen_select"> 
			<?php
			$states = $woocommerce->countries->states['US'];
			foreach ( $states as $state_key => $state_value ) :
				echo '<option value="' . $state_key . '"';
				if ( $this->origin_country_state == $state_key ) {
					echo ' selected="selected"';
				}
				echo '>' . $state_value .'</option>';
			endforeach;
			?>
	        </select> <span class="description"><?php _e( 'State for the <strong>shipper</strong>.', 'yaship' ) ?>
			</span>
       		</td>
       	</tr>
		<?php
		return ob_get_clean();
	}
	/**
	 * generate_services_html function.
	 *
	 * @access public
	 * @return void
	 */
	function generate_services_html() {
		ob_start(); ?>
		<tr valign="top" id="service_options">
			<td class="forminp" colspan="2" style="padding-left:0px">
				<table>
					<thead>
						<th><?php _e( 'Service Code', 'yaship' ); ?></th>
						<th><?php _e( 'Name', 'yaship' ); ?></th>
						<th><?php _e( 'Enabled', 'yaship' ); ?></th>
						<th><?php _e( 'Free Shipping Start Form', 'yaship' ); ?></th>
					</thead>
					<tfoot>
					</tfoot>
					<tbody>
						<?php
						$use_services = $this->services;
						foreach ( $use_services as $code=>$value ) {
						$name = $value; ?>
						<tr>
							<td><strong><?php echo $code; ?></strong></td>
							<td><input type="text" name="yaship_service[<?php echo $code;?>][name]" placeholder="<?php echo $name; ?> (<?php echo $this->title; ?>)" value="<?php echo isset( $this->custom_services[ $code ]['name'] ) ? $this->custom_services[ $code ]['name'] : ''; ?>" size="50" /></td>
							
							<td><input type="checkbox" name="yaship_service[<?php echo $code; ?>][enabled]" <?php checked( ( ! isset( $this->custom_services[ $code ]['enabled'] ) || ! empty( $this->custom_services[ $code ]['enabled'] ) ), true ); ?> /></td>
							<td><span>&#36;</span><input type="text" name="yaship_service[<?php echo $code;?>][fsr]" placeholder="0" value="<?php echo isset( $this->custom_services[ $code ]['fsr'] ) ? $this->custom_services[ $code ]['fsr'] :'0'; ?>" size="6" /></td>
						</tr>
						<?php
						}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}
	/**
	 * validate_single_select_country_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_origin_country_state_field( $key ) {
		//echo "In validate";
		if ( isset( $_POST['origin_country_state'] ) )
			return sanitize_text_field($_POST['origin_country_state']);
		return '';
	}
	public function init_form_fields() {
		global $woocommerce;
		$this->form_fields  = array(
			'enabled'          => array(
				'title'           => __( 'Enable/Disable', 'yaship' ),
				'type'            => 'checkbox',
				'label'           => __( 'Enable this shipping method', 'yaship' ),
				'default'         => 'no'
			),
			'title'            => array(
				'title'           => __( 'yaship Method Title', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'This controls the title which the user sees during checkout.', 'yaship' ),
				'default'         => __( 'yaship', 'yaship' )
			),
			'availability'  => array(
				'title'           => __( 'Method Availability', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'Shippng Service Available In US' ),
				'class'           => 'availability',
				'default'         => __( 'US', 'yaship' )
			),
			
			'debug'  => array(
				'title'           => __( 'Debug', 'yaship' ),
				'label'           => __( 'Enable debug mode', 'yaship' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'description'     => __( 'Enable debug mode to show debugging information on your cart/checkout.', 'yaship' )
			),
			'api'           => array(
				'title'           => __( 'API Settings', 'yaship' ),
				'type'            => 'title',
				'description'     => __( 'You need to obtain <strong>yaship</strong> account credentials by registering to yaship apis', 'yaship' ),
			),
			'api_key'          => array(
				'title'           => __( 'Yaship Api Key', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'Obtained from yaship after registration.', 'yaship' ),
				'default'         => '',
			),
			'account_number'      => array(
				'title'           => __( 'Yaship Account Number', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'Obtained from yaship after registration.', 'yaship' ),
				'default'         => '',
			),
			'first_name'      => array(
				'title'           => __( 'First Name', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'First name of <strong>shipper</strong>..', 'yaship' ),
				'default'         => '',
			),
			'last_name'      => array(
				'title'           => __( 'Last Name', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'Last name of <strong>shipper</strong>..', 'yaship' ),
				'default'         => '',
			),
			'origin_phone'      => array(
				'title'           => __( 'Phone Number ', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'Contact number of  <strong>shipper</strong>.', 'yaship' ),
				'default'         => '',
			),
			'origin_email'      => array(
				'title'           => __( 'Email Adress', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'An email address for the <strong>shipper</strong>.', 'yaship' ),
				'default'         => '',
			),
			'origin_company'      => array(
				'title'           => __( 'Company', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'Company of <strong>shipper</strong>.', 'yaship' ),
				'default'         => '',
			),
			'origin_addressline'  => array(
				'title'           => __( 'Origin Address', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'Address for the <strong>sender</strong>.', 'yaship' ),
				'default'         => '',
			),
			'origin_city'      	  => array(
				'title'           => __( 'Origin City', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'City for the <strong>sender</strong>.', 'yaship' ),
				'default'         => '',
			),
			'origin_country_state'      => array(
				'type'            => 'origin_country_state',
			),
			'origin_postcode'     => array(
				'title'           => __( 'Origin Postcode', 'yaship' ),
				'type'            => 'text',
				'description'     => __( 'Zip/postcode for the <strong>sender</strong>.', 'yaship' ),
				'default'         => '',
			),
			'res_addr'  => array(
				'title'           => __( 'Residential Address', 'yaship' ),
				'label'           => __( 'Enable Residential Address', 'yaship' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'description'     => __( 'Enable shipment to residential/commercial address.', 'yaship' )
			),
			'ship_from_address'  => array(
				'title'           => __( 'Ship From Address', 'yaship' ),
				'type'            => 'select',
				'default'         => 'shipping_address',
				'options'         => array(
					'shipping_address' => __( 'Shipping Address', 'yaship' ),
					'billing_address' => __( 'Billing Address', 'yaship' ),
				),
				'description'     => __( 'Change the preferance of Shipping Address printed on the label.', 'yaship' )
			),
			'units'      => array(
				'title'           => __( 'Weight/Dimension Units', 'yaship' ),
				'type'            => 'select',
				'description'     => __( 'Switch this to metric units, if you see "This measurement system is not valid for the selected country" errors.', 'yaship' ),
				'default'         => 'imperial',
				'options'         => array(
					'imperial'    => __( 'LB / IN', 'yaship' ),
					'metric'      => __( 'KG / CM', 'yaship' ),
				),
			),
			'label_settings'  => array(
				'title'           => __( 'Label Settings', 'yaship' ),
				'type'            => 'title',
				'description'     => '',
			),
			 'print_option'  => array(
				'title'           => __( 'Label Print Options', 'yaship' ),
				'type'            => 'select',
				'default'         => '',
				'class'           => 'print_options',
				'options'         =>$this->print_options,
			),
			 'email_label_link'  => array(
				'title'           => __( 'Email Label Link', 'yaship' ),
				'type'            => 'select',
				'default'         => '',
				'class'           => 'email_label_link',
				'options'         =>$this->email_label_link,
			),
			'services_packaging'  => array(
				'title'           => __( 'Services and Packaging', 'yaship' ),
				'type'            => 'title',
				'description'     => '',
			),
			'services'        => array(
				'type' => 'services',
			),
			
			'packing_method'  => array(
				'title'           => __( 'Parcel Packing', 'yaship' ),
				'type'            => 'select',
				'default'         => '',
				'class'           => 'packing_method',
				'options'         => array(
					'per_item'       => __( 'Default: Per Item Packaging', 'yaship' ),
					'box_packing'    => __( 'Recommended: Box Packaging ', 'yaship' ),
				),
			),
			'yaship_packaging'  => array(
				'title'           => __( 'Standerd Packaging', 'yaship' ),
				'type'            => 'multiselect',
				'description'	  => __( 'Yaship standard packaging options', 'yaship' ),
				'default'         => array(),
				'css'			  => 'width: 450px;',
				'class'           => 'yaship_packaging chosen_select',
				'options'         => $this->packaging_select
			),

			'boxes'  => array(
				'type'            => 'box_packing'
			)
		);
	}
	/**
	* calculate_shipping function.
	*
	* @access public
	* @param mixed $package
	* @return void
	*/
	public function calculate_shipping( $package = array() ) {
		global $woocommerce;
		global $post;
		$rates = array();
		$resp = array();
		libxml_use_internal_errors( true );
		
		if ( '' == $package['destination']['country'] ) {
			$this->debug( __('Yaship: Country not yet supplied. Rates not requested.', 'yaship') );
			return; 
		}
		if ( empty( $package['destination']['city'] ) ) {
			$this->debug( __('Yaship: City not yet supplied. Rates not requested.', 'yaship') );
			return;
		}
		if( ''== $package['destination']['postcode'] ) {
			$this->debug( __('Yaship: Zip not yet supplied. Rates not requested.', 'yaship') );
			return;
		} 
		$cart = WC()->cart;
		$cart_total = $cart->get_cart_total();
		$cart_total = trim(str_replace("&#36;"," ",strip_tags($cart_total)));
		
		$settings = get_option('woocommerce_'.YASHIP_ID.'_settings');
		$services = $settings['services'];
		$free_setting=array();
		foreach($services as $service=>$val){
			if(isset($val['fsr']) && $val['fsr']>0){
				$free_setting[$service]=$val['fsr'];
			}
		}
		$package_requests = $this->get_package_requests( $package );
		
		if ( $package_requests ) {
			$_request = $this->get_rate_requests( $package_requests, $package );
	
			if ( empty($_request['service_codes'])) {
				$this->debug( __('Yaship: No Services are enabled in admin panel.', 'yaship') );
			}
			$endpoint = "http://www.yaship.com/api/api/fetchrate";
				
			$response = wp_remote_post( $endpoint,
						array(
							'method' => 'POST',
							'timeout'   => 70,
							'sslverify' => 0,
							'body'      => $_request,
							'cookies' => array()
						)
					);
			if ( empty( $response['body'] ) ) {
				$this->debug( __( 'Yaship: Can Not Get Rates .', 'yaship' ) );
				exit;
			}
			$response = json_decode( $response['body'], true );
			
			if( is_array( $response ) && in_array( 'rates', $response ) && !empty($response['rates'] ) ){
				$resp_rate = $response['rates'];
				$ctr = 0;
				foreach( $resp_rate as $key => $val ){
					$flag=false;
					if( isset ( $val ) && array_key_exists( $key, $free_setting ) ){
						$free_rate = $free_setting[$key];
						
						if( $cart_total >= $free_rate ){
							//set flag to true
							$val=0;
							$ctr++;
							$flag = true;
						}
					}
					if( ( !$flag && $val != 0 ) || ( $flag && $val==0 ) ){
						$rate_id = $this->id . ':' . $key;
						$rate_name = $this->services[ $key ];
						$rates[ $rate_id ] = array(
							'id' 	=> $rate_id,
							'label' => $rate_name,
							'cost' 	=> $val,
							//'sort'  => $sort
						);
					}
						
				}
				if( $ctr!=0 ){
					WC()->session->set( YASHIP_ID.'ya_cart_rate_resp', $resp_rate );
				}
				if ( $rates ) {
					
					foreach ( $rates as $key => $rate ) {
						$this->add_rate( $rate );
					}
				}
			} else {
				wc_add_notice( __( 'Yaship: ' . $response['message'], 'woocommerce' ), 'error' );					
				$this->debug( __('Yaship: ' . $response['message'], 'yaship') );
			}
		}
	}
	/**
	 * get_package_requests
	 *
	 *
	 *
	 * @access private
	 * @return void
	 */
	private function get_package_requests( $package ) {
		switch ( $this->packing_method ) {
			case 'box_packing' :
				$requests = $this->box_shipping( $package );
				break;
				
			case 'per_item' :
				$requests = $this->per_item_shipping( $package );
				break;
				
			default :
				$requests = $this->per_item_shipping( $package );
				break;
		} 
		return $requests;
	}
		
	/**
	 * box_shipping function.
	 *
	 * @access private
	 * @param mixed $package
	 * @return void
	 */
	private function box_shipping( $package ) {
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
				
				$newbox = $boxpack->add_box($box['length'], $box['width'], $box['height']);

				$newbox->set_inner_dimensions( $box['length'], $box['width'], $box['height'] );
				$newbox->set_id($box['name']);
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
		foreach ( $package['contents'] as $item_id => $values ) {
			$ctr++;
			
			if ( !( $values['quantity'] > 0 && $values['data']->needs_shipping() ) ) {
				$this->debug( sprintf( __( 'Skipping Product #%d .', 'yaship' ), $ctr ) );
				continue;
			}
				//Check if dimensions are set
			if ( $values['data']->length && $values['data']->height && $values['data']->width && $values['data']->weight ) {

				$dimensions = array( $values['data']->length, $values['data']->height, $values['data']->width );
				for ( $i = 0; $i < $values['quantity']; $i ++ ) {
					$boxpack->add_item(
						number_format( woocommerce_get_dimension( $dimensions[2], $this->dim_unit ), 2, '.', ''),
						number_format( woocommerce_get_dimension( $dimensions[1], $this->dim_unit ), 2, '.', ''),
						number_format( woocommerce_get_dimension( $dimensions[0], $this->dim_unit ), 2, '.', ''),
						number_format( woocommerce_get_weight( $values['data']->get_weight(), $this->weight_unit ), 2, '.', ''),
						$values['data']->get_price()
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

			$weight     = $box_package->weight;
			$dimensions = array( $box_package->length, $box_package->width, $box_package->height );
			
			sort( $dimensions );
			
			$l_request = array();
			$l_package = array();
			$l_package_type = array();
			$l_package_type['Code'] = '02';
			$l_package_type['Description'] = 'Package/customer supplied';
			$l_package['PackagingType'] = $l_package_type;
			$l_package['Description'] = 'Rate';

			if ( !empty($dimensions)) {
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
			//for ( $i=0; $i < $cart_item_qty ; $i++)
				$requests[] = $l_request;
		}
		return $requests; 
	}
	/**
	 * per_item_shipping function.
	 *
	 * @access private
	 * @param mixed $package
	 * @return mixed $requests - an array of XML strings
	 */
	private function per_item_shipping( $package ) {
		
		global $woocommerce;

		$requests = array();

		$ctr=0;
		foreach ( $package['contents'] as $item_id => $values ) {
			$ctr++;

			if ( !( $values['quantity'] > 0 && $values['data']->needs_shipping() ) ) {
				$this->debug( sprintf( __( 'Product #%d is virtual. Skipping.', 'yaship' ), $ctr ) );
				continue;
			}

			if ( ! $values['data']->get_weight() ) {
				$this->debug( sprintf( __( 'Product #%d is missing weight. Aborting.', 'yaship' ), $ctr ), 'error' );
				return;
			}

			// get package weight
			$weight = woocommerce_get_weight( $values['data']->get_weight(), $this->weight_unit );

			// get package dimensions
			if ( $values['data']->length && $values['data']->height && $values['data']->width ) {

				$dimensions = array( number_format( woocommerce_get_dimension( $values['data']->length, $this->dim_unit ), 2, '.', ''),
									 number_format( woocommerce_get_dimension( $values['data']->height, $this->dim_unit ), 2, '.', ''),
									 number_format( woocommerce_get_dimension( $values['data']->width, $this->dim_unit ), 2, '.', '') );
				sort( $dimensions );
			} 

			// get quantity in cart
			$cart_item_qty = $values['quantity'];
			$l_request = array();
			$l_package = array();
			$l_package_type = array();
			$l_package_type['Code'] = '02';
			$l_package_type['Description'] = 'Package/customer supplied';
			$l_package['PackagingType'] = $l_package_type;
			$l_package['Description'] = 'Rate';

			if ( $values['data']->length && $values['data']->height && $values['data']->width ) {
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
			for ( $i=0; $i < $cart_item_qty ; $i++)
				$requests[] = $l_request;
		}
		return $requests;
	}
	/**
	 * get_rate_requests
	 *
	 * Get rate requests for all
	 * @access private
	 * @return array of strings - XML
	 *
	 */
	private function get_rate_requests( $package_requests, $package ) {
		global $woocommerce;
		$customer = $woocommerce->customer;
		
		$l_request = array();
		$shipFrom = array();
		$shipTo = array();
		$service_codes = array();
		$rate_requests = array();
		
		$l_request['api_key'] = $this->api_key;
		$l_request['account_number'] = $this->account_number;
		
		$shipFrom['addr'] = $this->origin_addressline;
		$shipFrom['city'] = $this->origin_city;
		$shipFrom['state'] = $this->origin_country_state;
		$shipFrom['po_code'] = $this->origin_postcode;
		$l_request['shipFrom_addr'] = $shipFrom;
		
		$fname = get_metadata('user', get_current_user_id(), 'shipping_first_name');
		$lname = get_metadata('user', get_current_user_id(), 'shipping_last_name');
		$shipTo['fname'] = (!empty($fname))?$fname[0]:"";
		$shipTo['lname'] = (!empty($lname))?$lname[0]:"";
		$shipTo['addr'] = $package['destination']['address'];
		$shipTo['addr2'] = $package['destination']['address_2'];
		$shipTo['city'] = $package['destination']['city'];
		$shipTo['state'] = $package['destination']['state'];
		$shipTo['po_code'] = $package['destination']['postcode'];
		$l_request['shipTo_addr'] = $shipTo;
		
		foreach ( $this->custom_services as $code => $params ) {
			if ( 1 == $params['enabled'] ) {
				$service_codes[] = $code;
			}
			
		}
		$l_request['service_codes'] = $service_codes;
		$l_request['res_addr'] = $this->res_addr;
		$l_request['packages'] = $package_requests;
		return $l_request;
	}
	public function makeLog($filename, $data)
	{
		$myFile = YASHIP_LOGS.$filename;
		$fh = fopen( $myFile, 'a+' );
		$new_data = array();
		$new_data['date'] = date("Y-m-d h:i:s");
		$new_data['data'] = $data;
		fwrite($fh, print_r($new_data,true));
		fclose($fh);
	}
}
endif;	
 ?>
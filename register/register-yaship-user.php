<?php
if ( isset( $_POST['yaship_register_nonce'] ) && current_user_can( 'administrator' ) ) {
	if ( wp_verify_nonce ( $_POST['yaship_register_nonce'], 'yaship-register-nonce' ) ) {
		if (isset($_POST['register_yaship_button'])) {
			$url = "http://www.yaship.com/api/api/register";
			
			$param = array();
			$param['ufname'] = sanitize_text_field($_POST['ufname']);
			$param['ulname'] = sanitize_text_field($_POST['ulname']);
			$param['uemail'] = sanitize_email($_POST['uemail']);
			$param['uphone'] = sanitize_text_field($_POST['uphone']);
			$param['site_url'] = esc_url($_POST['site_url']);
			$param['uaddr'] = sanitize_text_field($_POST['uaddr']);
			$param['ucity'] = sanitize_text_field($_POST['ucity']);
			$param['ustate'] = sanitize_text_field($_POST['ustate']);
			$param['ucontry'] = sanitize_text_field($_POST['ucontry']);
			$param['upo_code'] = sanitize_text_field($_POST['upo_code']);
			
			$param['cctype'] = sanitize_text_field($_POST['cctype']);
			$param['cc_num'] = sanitize_text_field($_POST['cc_num']);
			$param['cc_csv'] = intval(sanitize_text_field($_POST['cc_csv']));
			$param['ccexpmnth'] = sanitize_text_field($_POST['ccexpmnth']);
			$param['ccexpyear'] = intval(sanitize_text_field($_POST['ccexpyear']));
			$param['ccfname'] = sanitize_text_field($_POST['ccfname']);
			$param['cclname'] = sanitize_text_field($_POST['cclname']);
			
			$param['mode'] = get_option( 'woocommerece_yaship_account_mode', $default = false ); 
			
			$data = array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => $param,
				'cookies' => array()
			);
			$resp = wp_remote_post( $url, $data );
			$resp = (array)json_decode($resp['body']);
			if ($resp['success']) {
				// Insert into options user data and save user 
				$user = array();
				$user['fname'] = $param['ufname'];
				$user['lname'] = $param['ulname'];
				$user['email'] = $param['uemail'];
				$user['phone'] = $param['uphone'];
				$user['site_url'] =$param['site_url'];
				$user['address'] = $param['uaddr'];
				$user['city'] = $param['ucity'];
				$user['state'] = $param['ustate'];
				$user['country'] = $param['ucontry'];
				$user['postal_code'] = $param['upo_code'];
				$user['account_mode'] = $param['mode'];
				
				update_option( 'yaship_user', $user );
				$msg = $resp['message'];
				$replay = "<div class = 'row' style='background-color: #5CCC6E;color: white;font-size: 14px;text-align: center;padding: 7px;font: bolder;'>$msg</div>";
		
			} else {
					$msg = $resp['message'];
					$replay = "<div class = 'row' style='background-color: #F2766A;color: white;font-size: 14px;text-align: center;padding: 7px;font: bolder;'>$msg</div>";
			}
		}
	} else {
			/// throw an error
			$msg = 'Please resubmit the form.';
			$replay = "<div class = 'row' style='background-color: #F2766A;color: white;font-size: 14px;text-align: center;padding: 7px;font: bolder;'>$msg</div>";
	}
	echo $replay;
}
	
$mode = get_option( 'woocommerece_yaship_account_mode', $default = false );
$is_registered = get_option( 'yaship_is_registered', $default = false );
$user_details = get_option( 'yaship_user' );
?>
<html>
<body>
<form id="registration_form" action="<?php echo get_permalink(); ?>" method="POST">
	<h3>Register Your Account to Yaship</h3>
	<h3>NOTE: Your credit card details are securely passed to the payment gateway, we do not store any of the information related to your credit card</h3>
	<table class = "test">
		<tr>
			<td>Test</td>
			<?php if ($mode == 0) { ?>
				<td>
					<label class="switch"><input type="checkbox" class = "account_mode" id = "yaship_mode"><div class="slider round" ></div></label>
				</td>
			<?php } else { ?>
				<td>
					<label class="switch"><input type="checkbox" class = "account_mode" id = "yaship_mode" checked><div class="slider round" ></div></label>
				</td>
			<?php  } ?>
			<td>Live</td>
		</tr>
	</table>
	<table class = "yaship_register_form">
		<tr>
			<td ><h3 class="yaship_header"><?php _e('User Details'); ?></h3></td>
		</tr>
		<tr>
			<td style="text-align:left;"><label for="ufname"><?php _e('First Name'); ?></label><span class="ast" style="color:red">*</span></td>
			<td><input name="ufname" id="ufname"  type="text" value = "<?php echo (!empty($user_details))?$user_details['fname']:'';?>" required /></td>
		</tr>
		<tr>
			<td><label for="ulname"><?php _e('Last Name'); ?><span class="ast" style="color:red">*</span></label></td>
			<td><input name="ulname" id="ulname"  type="text" value = "<?php echo (!empty($user_details))?$user_details['lname']:'';?>" required /></td>
		</tr>
		<tr>
			<td style="text-align:left;"><label for="uemail"><?php _e('Email'); ?><span class="ast" style="color:red">*</span></label></td>
			
			<?php if (!empty($user_details)) { ?>
				<td><input name="uemail" id="uemail"  type="email" value = "<?php echo (!empty($user_details))?$user_details['email']:'';?>" readonly /></td>
				<?php } else { ?>
				<td><input name="uemail" id="uemail"  type="email" value = "<?php echo (!empty($user_details))?$user_details['email']:'';?>" required /></td>
			<?php } ?>
		</tr>
		<tr>
			<td><label for="uphone"><?php _e('Phone'); ?><span class="ast" style="color:red">*</span></label></td>
			<td><input name="uphone" id="uphone"  type="number" value = "<?php echo (!empty($user_details))?$user_details['phone']:'';?>" required /></td>
		</tr>
		<tr>
			<td><label for="site_url"><?php _e('Website'); ?><span class="ast" style="color:red">*</span></label></td>
			<td><input name="site_url" id="site_url"  type="url" value = "<?php echo (!empty($user_details))?$user_details['site_url']:'';?>" required /></td>
		</tr>
		<tr>
			<td><label for="uaddr"><?php _e('Address Line'); ?><span class="ast" style="color:red">*</span></label></td>
			<td><input name="uaddr" id="uaddr"  type="text" value = "<?php echo (!empty($user_details))?$user_details['address']:'';?>" required /></td>
		</tr>
		<tr>
			<td><label for="ucity"><?php _e('City'); ?><span class="ast" style="color:red">*</span></label></td>
			<td><input name="ucity" id="ucity"  type="text" value = "<?php echo (!empty($user_details))?$user_details['city']:'';?>" required /></td>
		</tr>
		<tr>
			<td><label for="ustate"><?php _e('State'); ?> <span class="ast" style="color:red">*</span> </label></td>
			<td><input name="ustate" id="ustate"  type="text" value = "<?php echo (!empty($user_details))?$user_details['state']:'';?>" required /></td>
		</tr>
		<tr>
			<td><label for="ucontry"><?php _e('Country'); ?><span class="ast" style="color:red">*</span></label></td>
			<td><input name="ucontry" id="ucontry"  type="text" value = "<?php echo (!empty($user_details))?$user_details['country']:'';?>" required /></td>
		</tr>
		<tr>
			<td><label for="upo_code"><?php _e('Postal Code'); ?><span class="ast" style="color:red">*</span></label></td>
			<td><input name="upo_code" id="upo_code"  type="number" value = "<?php echo (!empty($user_details))?$user_details['postal_code']:'';?>" required /></td>
		</tr>
		
		<?php if (empty($user_details) || $is_registered == 0 && $mode == 1) { ?>
			<tr>
				<td><h3 class="yaship_header"><?php _e('Credit Card Details'); ?>
				</h3></td>
			</tr>
			
			<tr>
				<td><label for="cctype"><?php _e('Card Type'); ?><span class="ast" style="color:red">*</span></label></td>
				<?php if($mode == 0) { ?>
				<td><input name="cctype" id="cctype"  type="text" value="Visa" readonly required /></td>
				<?php } else { ?>
				<td><input name="cctype" id="cctype"  type="text" required /></td>
				<?php } ?>
			</tr>
			<tr>
				<td><label for="cc_num"><?php _e('Card Number'); ?><span class="ast" style="color:red">*</span></label></td>
				<?php if ($mode == 0) { ?>
				<td><input name="cc_num" id="cc_num"  type="number" value="4111111111111111" readonly required/></td>
				<?php } else { ?>
				<td><input name="cc_num" id="cc_num"  type="number" required /></td>
				<?php } ?>
			</tr>
			<tr>
				<td><label for="cc_csv"><?php _e('Card CSV'); ?><span class="ast" style="color:red">*</span></label></td>
				<?php if($mode == 0) { ?>
				<td><input name="cc_csv" id="cc_csv"  type="number" value="147" readonly required /></td>
				<?php } else { ?>
				<td><input name="cc_csv" id="cc_csv"  type="number" required /></td>
				<?php } ?>
			</tr>
			<tr>
				<td><label for="ccexpmnth"><?php _e('Expiry Month'); ?><span class="ast" style="color:red">*</span></label></td>
				<td><select name="ccexpmnth" id="ccexpmnth" style="width: 98%;">
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
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="ccexpyear"><?php _e('Expiry Year'); ?><span class="ast" style="color:red">*</span></label></td>
				<td><input name="ccexpyear" id="ccexpyear"  type="number" required /></td>
			</tr>
			<tr>
				<td><label for="ccfname"><?php _e('First Name'); ?><span class="ast" style="color:red">*</span></label></td>
				<td><input name="ccfname" id="ccfname"  type="text" required /></td>
			</tr>
			<tr>
				<td><label for="cclname"><?php _e('Last Name'); ?><span class="ast" style="color:red">*</span></label></td>
				<td><input name="cclname" id="cclname"  type="text" required /></td>
			</tr>
		<?php } ?>
		
		<?php if (!empty($user_details) && $is_registered == 1 && $mode == 1) { ?>
			<tr>
				<td><input type="hidden" name="yaship_update_card" value="<?php echo wp_create_nonce('yaship-update-card'); ?>" />
				<input type="button" value="<?php _e('Update Credit Card'); ?>" name="update_credit_card" id = "update_credit_card" /></td>
				
				<td><input type="hidden" id="yaship_update_user" value="<?php echo wp_create_nonce('update-user-information'); ?>" />
				<input type="button" value="<?php _e('Update User Information'); ?>" name="update_user_information" id = "update_user_information" /></td>
			</tr>
		<?php } ?>
		
		<?php if (!empty($user_details) && $mode == 0) { ?>
			<tr>
				<td><input type="hidden" name="yaship_update_user" value="<?php echo wp_create_nonce('update-user-information'); ?>" />
				<input type="button" value="<?php _e('Update User Information'); ?>" name="update_user_information" id = "update_user_information" /></td>
			</tr>
		<?php } ?>
		
		<?php if(!empty($user_details) && $is_registered == 0 && $mode == 1) { ?>
			<tr>
				<td><input type="hidden" name="yaship_register_nonce" value="<?php echo wp_create_nonce('yaship-register-nonce'); ?>" />
				
				<input type="submit" value="<?php _e('Register Your Account'); ?>" name="register_yaship_button" /></td>
			</tr>
		<?php } ?>
		
		<?php if (empty($user_details)) { ?>
			<tr>
				<td><input type="hidden" name="yaship_register_nonce" value="<?php echo wp_create_nonce('yaship-register-nonce'); ?>" />
				
				<input type="submit" value="<?php _e('Register Your Account'); ?>" name="register_yaship_button" /></td>
			</tr>
		<?php } ?>
	</table>
</form>
<div id ="update_cc_form"></div>
</body>
		
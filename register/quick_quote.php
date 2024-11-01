<?php 
if ( current_user_can( 'administrator' ) ) {
	global $wpdb;
	$url = admin_url()."admin.php?page=register-yaship-user.php";
?>	<div id="loginScreen"> 
		<div id="qc-top-div">
			<a href="#" id="qc_cancel" class="cancel">CLOSE</a> 
			<label for="length"  style="font-size: 14px;">QUICK QUOTE</label>
		</div>
		<div id="qc-text-div">
			<span for="quick_quote_text" style="font-size: 14px;margin-left:10px">Register now or do a Quick Quote rate test to see what your discounted rates will be.</span>
		</div>
		<div id="qc-bottom-div">
			<div id="qc-qo-form-div">
				<form id="quick_quote_form" action="" method="POST">
				
					<p><label for="ShipFrom" class="qc-form-field-label">ShipFrom:</label></p>
					<p><input type="text" name="ShipFrom" id="ShipFrom" placeholder="Enter zip code" class="quick-quote-input"></p>
					
					<p><label for="ShipTo" class="qc-form-field-label">ShipTo:</label></p>
					<p><input type="text" name="ShipTo" id="ShipTo" placeholder="Enter zip code" class="quick-quote-input"></p>
					
					<p><label for="length" class="qc-form-field-label">Length:</label></p>
					<p><input type="text" name="length" id="length" placeholder="Enter length" class="quick-quote-input"></p>
					
					<p><label for="width" class="qc-form-field-label">Width:</label></p>
					<p><input type="text" name="width" id="width" placeholder="Enter width" class="quick-quote-input"></p>
					
					<p><label for="height" class="qc-form-field-label">Height:</label></p>
					<p><input type="text" name="height" id="height" placeholder="Enter height" class="quick-quote-input"></p>
					
					<p><label for="weight" class="qc-form-field-label">Weight:</label><br>
					<p><input type="text" name="weight" id="Weight" placeholder="Enter weight" class="quick-quote-input"></p>
					
					<p><input type="button" id="qc_qo_form_submit" data-inline="true" value="SUBMIT" >
					<input type="button"  value="REGISTER" id="qc_qo_form_register" ></p>
				</form>
			</div>
			<div id="qc-qo-result-div"></div>
			<input type="hidden" value="<?php echo $url;?>" id="qc_redirect_url">
		</div>
	</div>
<?php } ?>
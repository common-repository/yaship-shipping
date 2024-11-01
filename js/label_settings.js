
jQuery(document).ready(function () {
	jQuery("#woocommerce-order-yaship-cancel-labels").hide();
	
	jQuery(document).on("click","#qc_cancel",function(){
		location.href = parent.location;
	});
	
	// Void package function
	jQuery(document).on("click","#yship_void_pckg_btn",function(){
		var id = jQuery("#yaship_regen_hidden_field").val();
		var checkedValue = {};
		var inputElements = jQuery("input[name*=track_number_checkbox]:checked");
		
		for( var i=0; i < inputElements.length; ++i ) {
			  checkedValue[ i ] = inputElements[ i ].value;
		}
		jQuery.ajax({
			data: {
				'action':'yaship_package_cancelled_admin_order',
				'security': objlabelsettings.label_settings_nonce,
				'id':id,
				'checkedValue':checkedValue
			},
			type: 'post',
			url: 'admin-ajax.php',
		    success: function( data ) {
				alert(data);
				location.reload(true);
			},
			error: function( err ) {
				alert( JSON.stringify( err ) );
			}
		});
	});
	
	jQuery(document).on("click","#yship_display_pckg_btn",function(){
		//display the hidden meta box to show labels
		jQuery("#woocommerce-order-yaship-cancel-labels").show();
	});
	
	jQuery(document).on("click","#yship_void_ship_btn",function(){
		jQuery("#yship_void_ship_btn").attr('disabled','disabled');
		var id = jQuery("#yaship_regen_hidden_field").val();
		jQuery.ajax({
			data:{
				'action':'yaship_shipment_cancelled_admin_order',
				'security': objlabelsettings.label_settings_nonce,
				'id':id
				},
			type:'post',
			dataType: 'json',
			url: 'admin-ajax.php',
		    success: function(data) {
				if( data && data[ 'res' ] ) {
					jQuery("#yaship_admin_service_list").removeAttr('disabled');
					jQuery("#yship_regen_ship_btn").removeAttr('disabled');
					location.href = location.href;
				}else{
					if( data )
					alert( data[ 'msg' ] );
				    else	
					alert(JSON.stringify(data));
				}
			},
			error: function(err){
				alert(JSON.stringify(err));
			}
		});
	});
	
	jQuery(document).on("click","#yship_regen_ship_btn",function(){
		var id = jQuery("#yaship_regen_hidden_field").val();
		var code = jQuery("#yaship_admin_service_list").val();
		var service = jQuery("#yaship_admin_service_list option:selected").text();
		if(!code){
			alert("Please Select Shipment Service!!!")
		} else {
			jQuery.ajax({
				data:{
					'action':'yaship_re_shipment_admin_order',
					'security': objlabelsettings.label_settings_nonce,
					'id':id,
					'code':code,
					'service':service
					},
				type:'post',
				dataType: 'json',
				url: 'admin-ajax.php',
				success: function(data) {
					if(data && data['res']){
						
						jQuery("#yship_void_ship_btn").removeAttr('disabled');
						jQuery("#yaship_admin_service_list").attr('disabled','disabled');
						jQuery("#yship_regen_ship_btn").attr('disabled','disabled');
						location.href = location.href;
					}else{
					   if( data )
						alert(data['msg']);
					else	
						alert(JSON.stringify(data));
					}
				},
				error: function(err){
					alert(JSON.stringify(err));
				}
			});
		}
	});
	
	jQuery(document).on("click",".sh_return_shipment",function(){
		var id = jQuery(this).attr("id");
		var link = jQuery(".sh_ajx_file").val();
		var urls=link+"&order_id="+id;
		jQuery.ajax({
			url: urls,
			success: function(data) {
				if(data){
					jQuery(".display_product_view").css('display',"block").html(data);
				}
			}
		});
	});
	 
	jQuery(document).on('click','#ret_ship_submmit',function(){
		var link = jQuery(".sh_ajx_file1").val();
		var id = jQuery(".sh_ajx_file1").attr("id");
		var return_array ={};
		var urls=link+"&order_id="+id;
		jQuery(".chk").each(function(){
			if(jQuery(this).prop("checked")){
				var item_id=jQuery(this).attr("id");
				var return_qty=jQuery("#tx_"+item_id).val();
				return_array[item_id]=return_qty;	
			}
		});
		var str = JSON.stringify( return_array );
		jQuery.ajax({
			url: urls,
			data: {
				msg:str
			},
			success: function( data ) {
				if( data ) {
					jQuery( ".display_product_view" ).css( 'display', "none" );
					jQuery( ".display_return_ship_response" ).css( 'display',"block" ).html(data);
				}
			}
		});
	});
});
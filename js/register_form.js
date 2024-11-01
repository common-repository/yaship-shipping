jQuery(document).ready(function () {
	jQuery(document).on( 'click', '#yaship_mode', function() {
		var mode = document.getElementById('yaship_mode').checked;
		if ( mode == true ) {
			var accountMode = 1;	
		} else {
			var accountMode = 0;
		}
		
		jQuery.ajax({
			data: {
				'action': 'yaship_save_mode',
				'security': objregisterform.register_form_nonce,
				'mode': accountMode
				},
			type: 'post',
			url: 'admin-ajax.php',
			success: function(data) {
				location.reload();
			},
			error: function(err) {
				alert(JSON.stringify(err));
			}
		});
	});
	
	jQuery( document ).on( 'click', '#submit_card_details', function() {
		var cardType = document.getElementById('Card_Type').value;
		var cardNumber = document.getElementById('Card_Number').value;
		var cardCsv = document.getElementById('Card_CSV').value;
		var expireMonth = document.getElementById('ccexpmnth').value;
		var expireYear = document.getElementById('Expire_year').value;
		var firstName = document.getElementById('First_Name').value;
		var lastName = document.getElementById('Last_Name').value;
		if (cardType == "" || cardNumber == "" || cardCsv == "" || expireMonth == "" || expireYear == "" || firstName == "" || lastName == "")
		{
			alert("Please fill all the fields");
			return false;
		}
		jQuery.ajax({
            type: "POST",
			url: "admin-ajax.php",
            data: {
				'action': 'yaship_update_credit_card',
				'security': objregisterform.register_form_nonce,
				'card_type': cardType,
				'card_number':cardNumber,
				'card_csv':cardCsv,
				'expire_month':expireMonth,
				'expire_year':expireYear,
				'first_name':firstName,
				'last_name':lastName
				},
            success: function (response) {
                alert( response );
			},
			error: function(err){
				alert( JSON.stringify( err ) );
			}
        });
	});
	
	
	jQuery( "#update_credit_card" ).click( function() {
		jQuery.ajax({
			type: "get",
			url: "admin-ajax.php",
			data: {
				'action': 'yaship_get_creditcard_form',
				'security': objregisterform.register_form_nonce
				},
			success: function(result){
				jQuery( '#yaship_register_form' ).hide();
				jQuery( '#registration_form' ).html( result );
			}
		});
	}); 
	  
	jQuery( document ).on( 'click', '#update_user_information', function(){
		var userFname = document.getElementById('ufname').value;
		var userLname = document.getElementById('ulname').value;
		var userPhone = document.getElementById('uphone').value;
		var userSiteUrl = document.getElementById('site_url').value;
		var userAddr = document.getElementById('uaddr').value;
		var userEmail = document.getElementById('uemail').value;
		var userCity = document.getElementById('ucity').value;
		var userState = document.getElementById('ustate').value;
		var userCountry = document.getElementById('ucontry').value;
		var userPostCode = document.getElementById('upo_code').value;
		
        jQuery.ajax({
            type: "POST",
            data: {
				'action': 'yaship_update_information',
				'security': objregisterform.register_form_nonce,
				'user_fname': userFname,
				'user_lname':userLname,
				'user_email':userEmail,
				'user_phone':userPhone,
				'user_site_url':userSiteUrl,
				'user_addr':userAddr,
				'user_city':userCity,
				'user_state':userState,
				'user_country':userCountry,
				'user_post_code':userPostCode
			},
            url: "admin-ajax.php",
            success: function (response) {
                alert( response );
			},
			error: function(xhr, status, error) {
			  var err = eval("(" + xhr.responseText + ")");
			  alert( err.Message );
			}
        });
	});
	
	jQuery(document).on("click","#qc_qo_form_register",function(){
		var url = jQuery("#qc_redirect_url").val();
		location.href = url;
	});
	
	jQuery(document).on("click","#qc_qo_form_submit",function(){
		var shipTo = parseInt(jQuery('#ShipTo').val());
		var shipFrom = parseInt(jQuery('#ShipFrom').val());
		var length = parseInt(jQuery('#length').val());
		var width = parseInt(jQuery('#width').val());
		var height = parseInt(jQuery('#height').val());
		var weight = parseInt(jQuery('#Weight').val());
		var btnTxt = jQuery("#qc_qo_form_submit").val();
		if(btnTxt === "SUBMIT"){
			if(!shipTo || shipTo<0 || (shipTo.toString().length != 5)) {
				alert('Ship To code must be a 5 digit number and greater than 0');
			} else if (!shipFrom || shipFrom<0 || (shipFrom.toString().length != 5)) {
				alert('Ship From code must be a 5 digit number and greater than 0');
			} else if (!(length && length>0)) {
				alert('Length must be number and greater than 0');
			} else if(!(width && width>0)) {
				alert('width must be number and greater than 0');
			} else if(!(height && height>0)) {
				alert('Height must be number and greater than 0');
			} else if(!(weight && weight>0)) {
				alert('Weight must be number and greater than 0');
			} else {
				jQuery.ajax({
					type:'post',
					url: 'admin-ajax.php',
					data:{
						'action':'yaship_calculate_quick_quote',
						'security': objregisterform.register_form_nonce,
						'to':shipTo,
						'from':shipFrom,
						'length':length,
						'width':width,
						'height':height,
						'weight':weight
					},
					dataType: 'json',
					success: function(response) {
						if( response[ 'result' ] ) {
							jQuery("#qc-qo-result-div").html(response['result']);
						} else {
							alert(response['error']);
						}
						jQuery("#qc_qo_form_submit").val("RESET");
					},
					error: function(XMLHttpRequest, textStatus, errorThrown){
						 alert(textStatus);
					}
				});	
			}	
		} else if(btnTxt === "RESET") {
			jQuery('#ShipTo').val("");
			jQuery('#ShipFrom').val("");
			jQuery('#length').val("");
			jQuery('#width').val("");
			jQuery('#height').val("");
			jQuery('#Weight').val("");
			jQuery("#qc_qo_form_submit").val("SUBMIT");
			jQuery("#qc-qo-result-div").html("");
		}
	});
	
	jQuery(document).ready(function() {
	jQuery('.yaship-report').DataTable( {
		initComplete: function () {
			this.api().columns().every( function () {
			   var column = this;
			   var select = jQuery('<select><option value="">Select</option></select>')
			   .appendTo( jQuery (column.footer()).empty() )
				    .on( 'change', function () {
					    var val = jQuery.fn.dataTable.util.escapeRegex(
						   jQuery(this).val()
						);
					   column
						   .search( val ? '^'+val+'$' : '', true, false )
						   .draw();
					} );

				column.data().unique().sort().each( function ( d, j ) {
				   select.append( '<option value="'+d+'">'+d+'</option>' )
				} );
			} );
		}
	} );
} );

});

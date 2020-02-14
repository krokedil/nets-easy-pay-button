jQuery(function($) {
	const nepb = {

        bodyEl: $('body'),
		/*
		 * Document ready function. 
		 * Runs on the $(document).ready event.
		 */
		documentReady: function() {

			nepb.checkoutModal();
			nepb.maybeAutoOpenModal();
		},

		checkoutModal: function() {
			let modalBtn = document.getElementById("nepb-checkout-button")
			let modal = document.querySelector(".nepb-checkout-modal")
            let closeBtn = document.querySelector(".nepb-close-checkout-modal")
            let body = document.body
			modalBtn.onclick = function(){
                modal.style.display = "block"
                body.classList.add("nepb-modal-open")
                nepb.getCheckoutSession();
			}
			closeBtn.onclick = function(){
				modal.style.display = "none"
			}
			window.onclick = function(e){
			if(e.target == modal){
				modal.style.display = "none"
			}
			}
		},
		maybeAutoOpenModal: function() {
			
			let modal = document.querySelector(".nepb-checkout-modal")
			let body = document.body
			if( nepb_params.paymentid && 'yes' === nepb_params.nepb_checkout ) {
				modal.style.display = "block"
				body.classList.add("nepb-modal-open")
				if( 'yes' === nepb_params.nepb_checkout_complete) {
					nepb.getCheckoutSessionComplete( nepb_params.paymentid );
				} else {
					nepb.getCheckoutSession( nepb_params.paymentid );
				}
			}
			
		},
		onPaymentInitiated: function( response ) {
			
				$(document.body).trigger('dibs_pay_initialized');
				console.log('dibs_pay_initialized');
				
				nepb.processWooOrder(response);				
		},

		/*
		 * For regular products.
		 * Updates the customer address in WooCommerce if customer country in KIS modal is different from Woo.
		 * This function is run on Klarna JS callback events session_initiated & identification_updated.
		 */
		getCheckoutSession: function( paymentid = null ){
			// e.preventDefault();
			// $('.kis-submit').addClass('disabled kis-spinner');
			//const buttonIdData = $(this).attr("data-id");
			//const buttonMerchantId = $(this).attr("data-merchant-id");
			//const buttonSharedSecret = $(this).attr("data-shared-secret");
			
			// const buttonMerchantId = $('input[name=merchant-id]').val();
			// const buttonSharedSecret = $('input[name=shared-secret]').val();
			// const buttonEnvironment = $('input[name=environment]:checked').val();
			// const buttonCountries = $('select[name=countries]').val();
			console.log('getCheckoutSession');
			// console.log(buttonEnvironment);
			if( paymentid ) {
				var nepbSessionId = paymentid;
			} else {
				if (sessionStorage.getItem('nepb_session_id')) {
					var nepbSessionId = sessionStorage.getItem('nepb_session_id');
				} else {
					var nepbSessionId = '';
				}
			}
			
			const nepbCheckout = document.querySelector('#nepb-checkout-button');
			

			if ( document.getElementById("nepb-checkout").querySelector('.qty') ) {
				var quantity = document.getElementById("nepb-checkout").querySelector('.qty').value;
			} else {
				var quantity = 1;
			}
			console.log('quantity');
			console.log(quantity);
			console.log(nepbCheckout.dataset.wcProductId);
			console.log('nepbSessionId');
			console.log(nepbSessionId);
			jQuery.ajax(
				nepb_params.get_checkout_session_url,
				{
					type: "POST",
					data: {
						product_id: nepbCheckout.dataset.wcProductId,
						current_url : nepb_params.current_url,
						quantity: quantity,
						// button_environment: buttonEnvironment,
						// button_countries: buttonCountries,
						// security: nepb_admin.ajax_nonce,
						nepb_session_id : nepbSessionId,
						action: "nepb_get_checkout_session",
					},
					dataType: "json",
					success: function(data) {
						console.log('getCheckoutSession success');
						console.log(data);
						console.log(data.data.result);
						if('error' === data.data.result) {
							// $('.kis-submit').removeClass('disabled');
							$('.nepb-checkout-modal-content').html('<div class="nets-ifame">'  + data.data.message +  '</div>');
							// $('.nepb-new-button-modal-content').prepend('<div class="notice notice-error is-dismissible"><p><strong> ' + data.data.message + '</strong></p></div>');
						} else {
							if( data.data.nepb_session_id ) {
								sessionStorage.setItem( 'nepb_session_id', data.data.nepb_session_id );
							}
							
							// $('.kis-submit').removeClass('disabled');
							// $('.nepb-checkout-modal-content').html('<div class="nets-ifame">'  + data.data.nepb_session_id +  '<div id="dibs-complete-checkout"></div></div>');
							$('.nepb-checkout-modal-content').html('<div class="nets-ifame"><div id="dibs-complete-checkout"></div></div>');
							// $('.nepb-field-list').append('<div class="nepb-field-object"><ul class="nepb-tbody nepb-hl"><li class="li-field-key">' + data.data.button_key + '</li><li class="li-field-key">' + buttonMerchantId + '</li><li class="li-field-key">' + buttonCountries + '</li><li class="li-field-key">' + buttonEnvironment + '</li></ul></div>');
							
							// document.getElementById("kis-new-data-key").reset();
							var checkoutOptions = {
										checkoutKey: nepb_params.private_key, 	//[Required] Test or Live GUID with dashes
										paymentId : data.data.nepb_session_id, 		//[required] GUID without dashes
										containerId : "dibs-complete-checkout", 		//[optional] defaultValue: dibs-checkout-content
										language: nepb_params.locale,            //[optional] defaultValue: en-GB
							};
							var dibsCheckout = new Dibs.Checkout(checkoutOptions);
							dibsCheckout.on('pay-initialized', function(response) {
								console.log('nets pay-initialized');
								// nepb.onPaymentInitiated( response );
								console.log('response');
								console.log(response);
								$(document.body).trigger('dibs_pay_initialized');
								console.log('dibs_pay_initialized');
								
								nepb.processWooOrder(response, dibsCheckout);	
								
								
							});
							dibsCheckout.on('payment-completed', function (response) {
								console.log('payment-completed');
								console.log(response.paymentId);
								//DIBS_Payment_Success(response.paymentId);
								var redirectUrl = sessionStorage.getItem( 'nepbRedirectUrl' );
								console.log(redirectUrl);
								if( redirectUrl ) {
									window.location.href = redirectUrl;
								}
							});
						}
					},
					error: function(data) {
						console.log('getCheckoutSession error');
						console.log(data);
						$('.nepb-checkout-modal-content').html('<div class="nets-ifame">'  + data.statusText +  '</div>');
					},
					complete: function(data) {
						// Update Klarna order.
						
					}
				}
			);
		},


		/*
		 * For regular products.
		 * Updates the customer address in WooCommerce if customer country in KIS modal is different from Woo.
		 * This function is run on Klarna JS callback events session_initiated & identification_updated.
		 */
		getCheckoutSessionComplete: function( paymentid = null ){
			
			console.log('getCheckoutSessionComplete');
			// console.log(buttonEnvironment);
			if( paymentid ) {
				var nepbSessionId = paymentid;
			} else {
				if (sessionStorage.getItem('nepb_session_id')) {
					var nepbSessionId = sessionStorage.getItem('nepb_session_id');
				} else {
					var nepbSessionId = '';
				}
			}
			$('.nepb-checkout-modal-content').html('<div class="nets-ifame"><div id="dibs-complete-checkout"></div></div>');
			
			var checkoutOptions = {
				checkoutKey: nepb_params.private_key, 	//[Required] Test or Live GUID with dashes
				paymentId : nepbSessionId, 		//[required] GUID without dashes
				containerId : "dibs-complete-checkout", 		//[optional] defaultValue: dibs-checkout-content
				language: nepb_params.locale,            //[optional] defaultValue: en-GB
			};
			var dibsCheckout = new Dibs.Checkout(checkoutOptions);
			dibsCheckout.on('pay-initialized', function(response) {
				console.log('nets pay-initialized');
				// nepb.onPaymentInitiated( response );
				console.log('response');
				console.log(response);
				$(document.body).trigger('dibs_pay_initialized');
				console.log('dibs_pay_initialized');
				
				nepb.processWooOrder(response, dibsCheckout);	
				
				
			});
			dibsCheckout.on('payment-completed', function (response) {
				console.log('payment-completed');
				console.log(response.paymentId);
				//DIBS_Payment_Success(response.paymentId);
				var redirectUrl = sessionStorage.getItem( 'nepbRedirectUrl' );
				console.log(redirectUrl);
				if( redirectUrl ) {
					window.location.href = redirectUrl;
				}
			});
		},

		/*
		 * For regular products.
		 * Updates the customer address in WooCommerce if customer country in KIS modal is different from Woo.
		 * This function is run on Klarna JS callback events session_initiated & identification_updated.
		 */
		processWooOrder: function( response, dibsCheckout ){
			// e.preventDefault();
			// $('.kis-submit').addClass('disabled kis-spinner');
			//const buttonIdData = $(this).attr("data-id");
			//const buttonMerchantId = $(this).attr("data-merchant-id");
			//const buttonSharedSecret = $(this).attr("data-shared-secret");
			
			// const buttonMerchantId = $('input[name=merchant-id]').val();
			// const buttonSharedSecret = $('input[name=shared-secret]').val();
			// const buttonEnvironment = $('input[name=environment]:checked').val();
			// const buttonCountries = $('select[name=countries]').val();

			const nepbCheckout = document.querySelector('#nepb-checkout-button');

			if ( document.getElementById("nepb-checkout").querySelector('.qty') ) {
				var quantity = document.getElementById("nepb-checkout").querySelector('.qty').value;
			} else {
				var quantity = 1;
			}
			console.log('processWooOrder');
			
			jQuery.ajax(
				nepb_params.process_woo_order_url,
				{
					type: "POST",
					data: {
						payment_id: response,
						product_id: nepbCheckout.dataset.wcProductId,
						quantity: quantity,
						action: "nepb_process_woo_order",
					},
					dataType: "json",
					success: function(data) {
						console.log('processWooOrder success');
						console.log(data);
						console.log(data.data.result);
						if('error' === data.data.result) {
							console.log('processWooOrder success - error message');
							console.log(data.data.message);
							// $('.kis-submit').removeClass('disabled');
							// $('.nepb-checkout-modal-content').html('<div class="nets-ifame">'  + data.data.message +  '</div>');
							// $('.nepb-new-button-modal-content').prepend('<div class="notice notice-error is-dismissible"><p><strong> ' + data.data.message + '</strong></p></div>');
							return 'error';
						} else {
							sessionStorage.setItem( 'nepbRedirectUrl', data.data.redirect_url );
							dibsCheckout.send('payment-order-finalized', true);
							return 'success';
						}
					},
					error: function(data) {
						console.log('processWooOrder error');
						console.log(data);
						// $('.nepb-checkout-modal-content').html('<div class="nets-ifame">'  + data.statusText +  '</div>');
						return 'error';
					},
					complete: function(data) {
						// Update Klarna order.
						
					}
				}
			);
		},



		/*
		 * Initiates the script and sets the triggers for the functions.
		 */
		init: function() {
            $(document).ready( nepb.documentReady() );
            
			// nepb.bodyEl.on('submit', '#kis-new-data-key', nepb.createButtonId);
			//nepb.bodyEl.on('click', '.new-kis-button', nepb.createButtonId);
		},
	}
	nepb.init();
	
});
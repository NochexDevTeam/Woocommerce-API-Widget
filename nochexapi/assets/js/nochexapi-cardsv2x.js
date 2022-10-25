( function ( nochexapi, $ ) {
    'use strict';

    nochexapi.nochexapiCardVars                 = {};
    var nochexapiCardsInProgress         = false;
    var globalPrefix              = '';
    var cardIframeContainerID     = '';
    var cardIframeID              = '';
    var nochexVals                = '';

    /*function scroll_to_notices( scrollElement ) {
        var offset = 300;
        if ( scrollElement.length ) {
            $( 'html, body' ).animate( {
                scrollTop: ( 300 )
            }, 1000 );
        }
    }*/

    function cardsv2Log(obj){
        if( parseInt( nochexapi.nochexapiCardVars.jsLogging ) === 1 ){
	        var e = new Error(obj);
            console.log(obj);
            console.log(e.stack);
        }
    }

    function childFramePost( iFrameId, obj ){
        var iFrame            = document.getElementById( iFrameId );
        var iFrameDoc         = (iFrame.contentWindow || iFrame.contentDocument);
        if( iFrameDoc.document ){
            iFrameDoc         = iFrameDoc.document;
        }

        var event;
        if( typeof window.CustomEvent === "function" ) {
            event             = new CustomEvent( 'frameLogV52', { detail: obj } );
        } else {
            event             = document.createEvent('Event');
            event.initEvent('frameLogV52', true, true);
            event.detail = obj;
        }
        iFrameDoc.dispatchEvent(event);
    }

    function logToParentWindow( e ){
        if( e.detail.hasOwnProperty( 'funcs' ) ){
            for ( var i = 0, len = e.detail.funcs.length; i < len; i++ ) {
                try{
                    let tempfunc    = eval( e.detail.funcs[ i ].name );
                    tempfunc(e.detail.funcs[i].args); 
                }catch( err ){
                    cardsv2Log( err );
                }
            }
        }
    }

    function sendnochexapiVarsObject(args){
        cardsv2Log('sendnochexapiVarsObject => args:');
        var obj = {funcs:[{name:"initialiseCnp", args:[nochexapi.nochexapiCardVars,$('#'+globalPrefix+'checkout_id').val()]}]};
        cardsv2Log(obj);
        childFramePost( cardIframeID, obj );
    }

    function chkCreateAccField(args){
        var nodeList = document.getElementsByName("createaccount");
        if(nodeList.length > 0){
            var obj = {funcs:[
                {name:"adjRgState",args:[$('input[name^="createaccount"]').is(":checked")]}
            ]};
            childFramePost( cardIframeID, obj );
        }
    }

    function nochexapiSetFrameHeight(args){
        if(typeof args[0] === 'number'){
            if(args[0] > 0) {
                //cardsv2Log(args[0] + 'px');
                document.getElementById( cardIframeID ).style.height = args[0] + 'px';
            }
        }
    }

    function nochexapiSetParentBlockUI(args){
        $('body').block({
            message: '<p class="text-align:center">Please wait till payment is processing.<br />It may take some time to process.<br />Don\'t refresh or close or hit back.</p>',
            overlayCSS: {
                background:  '#fff',
                opacity:     1
            },
            css: {
                width:       '50%',
                border:      'none',
                //cursor:      'wait',
                opacity:     1
            }
        });
    }

    function nochexapiUnSetParentBlockUI(args){
        $.unblockUI;
    }

    function pciFormSubmit(iFrameId,paymentContainer){
        cardsv2Log('executing wpwl! =>');
        var iFrame = document.getElementById(iFrameId);
        var iFrameDoc = (iFrame.contentWindow || iFrame.contentDocument);
        if (iFrameDoc.document) iFrameDoc = iFrameDoc.document;
        var obj = {funcs:[
            {name:"executePayment",args:[paymentContainer]}
        ]};
        var event;
        if(typeof window.CustomEvent === "function") {
            event = new CustomEvent('frameLogV52', {detail:obj});
        } else {
            event = document.createEvent('Event');
            event.initEvent('frameLogV52', true, true);
            event.detail = obj;
        }
        iFrameDoc.dispatchEvent(event);
    }

    function unloadWpwlnochexapiCardsv2(){
        if (window.wpwl !== undefined && window.wpwl.unload !== undefined) {
            window.wpwl.unload();
            $('script').each(function () {
                if (this.src.indexOf('static.min.js') !== -1) {
                    $(this).remove();
                }
            });
        }
        $('#nochexapi_alt_cnp_container').empty();
    }

    function instantiateCheckoutIdOrder(){
        cardsv2Log('instantiateCheckoutIdOrder => running!');
        nochexapiCardsInProgress = false;
        $.ajax({
            type: 'post',
            dataType : 'json',
            url: nochexapi.nochexapiCardVars.adminUrl,
            data : {action: globalPrefix + "requestOrderCheckoutId"},
            success: function(response){
                cardsv2Log(response);
                if(response.success === true) {
                    $( '#' + cardIframeContainerID ).empty();
                    $( '#' + globalPrefix + 'checkout_id').val( response.data.uuid );
                    cardsv2Log('set the uuid to: ' + response.data.uuid);
            /*        $( '#' + cardIframeContainerID ).html('<iframe id="' + cardIframeID + '" src="'+response.data.frameurl+'?v='+Date.now()+'" style="background:#eee;width: 100%; height:275px; border: none;transform: scale(0.9) !important;"></iframe>');*/
                }
            }
        });
    }

   /* nochexapi.initCheckoutIdOrder = function(){
	    if($('iframe#' + cardIframeID).length === 0){
            cardsv2Log('Checkout Iframe is initiated');
            instantiateCheckoutIdOrder();
        }
    }*/
	
	function validationTrigger(){
		//$('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
		$('.validate-required input, .validate-required select').trigger('validate');
		if($('.validate-required').length != $('.woocommerce-validated').length){
			
			/*var message = '<p style="color: red">Please check your: <ul>';
			jQuery('.woocommerce-invalid-required-field').each(function(index, element){
				var label = jQuery(this).contents().find("*input").attr('id');
				label = label.replace(/_/g,' ');
				label = label[0].toUpperCase() + label.slice(1);
				message = message + '<li>' + label + '</li>';
			});
			message = message + '</ul>';
			sessionStorage.setItem('errors', message);*/
			return true;
		}
		return false;
	}

    function completeOrdernochexapiCards(endpoint,checkoutData){
        if(nochexapiCardsInProgress === true){
            cardsv2Log('form in 3d progress!!');
            return;
        }
		
        nochexapiCardsInProgress = false;
        cardsv2Log('start the post.');
        $('#place_order').after('<p id="nochexapiCardsBtnReplace" style="color:#CCC; text-align:center;">Processing, please wait...</p>');
		
		//$('#'+globalPrefix+'container').html('<iframe id="' + cardIframeID + '" src="'+response.frameurl+'" style="background:#eee;width: 100%; height:275px; border: none;transform: scale(0.9) !important;"></iframe>');
		//<input type="button" value="Continue" id="ncx-show-checkout" class="btn btn-primary" style="">

        $('#place_order').hide();
		
		
/*        if(validationTrigger() == true){
			//alert('');
			/*$('#nochexapiCardsBtnReplace').remove();
			$('#place_order').show();
			//$('body').trigger("update_checkout");
			location.reload();
			return;*
			// causes error but shows validation message - // break;
		}*/
		$('body').trigger("update_checkout");
		$('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
        $.ajax({
            type: 'POST',
            url: endpoint,
            contentType: "application/x-www-form-urlencoded; charset=UTF-8",
            enctype: "multipart/form-data",
            data: checkoutData,
            success: function(response){
			
			if(response.result === 'success'){
				var nochexVals = new Array(); 
				nochexVals = JSON.parse(response.popNCX);
				
				$('#ncx_form_container').html('<input type="button" value="Continue" id="ncx-show-checkout" class="btn btn-primary" style="display:none"><script>var cell1 = document.getElementById("ncx_form_container");	var buttonnode= document.createElement("script"); 				buttonnode.setAttribute("id","ncxLib");				buttonnode.setAttribute("src","https://secure.nochex.com/exp/nochex_lib.js"); 				cell1.appendChild(buttonnode);								var formnode= document.createElement("form");				formnode.setAttribute("name","ncx-form"); 				formnode.setAttribute("id","nochexForm"); 				formnode.setAttribute("class","ncx-form");				 				var ncFm = document.createElement("script");				ncFm.setAttribute("id","ncx-config"); 				ncFm.setAttribute("ncxField-api_key","' + nochexVals.apiKey + '");			ncFm.setAttribute("NCXFIELD-MERCHANT_ID","' + nochexVals.merchantId + '");	ncFm.setAttribute("NCXFIELD-order_id", "' + nochexVals.merchantTransactionId +'"); 			ncFm.setAttribute("NCXFIELD-AMOUNT", "' + nochexVals.amount +'"); ncFm.setAttribute("ncxField-address","' + nochexVals.billingstreet1 + '"); ncFm.setAttribute("ncxField-city","' + nochexVals.billingcity + '"); ncFm.setAttribute("ncxField-postcode","' + nochexVals.billingpostcode + '"); ncFm.setAttribute("ncxField-email","' + nochexVals.customeremail + '"); ncFm.setAttribute("ncxField-fullname","' + nochexVals.cardholder + '"); ncFm.setAttribute("ncxField-phone","' + nochexVals.customermobile + '"); ncFm.setAttribute("ncxField-callback_url","' + nochexVals.callbackurl + '");ncFm.setAttribute("ncxField-success_url","' + response.pay_url + '");ncFm.setAttribute("ncxField-test_transaction","' + nochexVals.testMode + '"); ncFm.setAttribute("ncxField-autoredirect","True"); formnode.appendChild(ncFm);cell1.appendChild(formnode);setTimeout(function (){     document.getElementById("ncx-show-checkout").click();  OnScriptLoad(); }, 500); function OnScriptLoad(){ var att = document.createAttribute("onClick"); att.value = "location.reload();"; document.getElementById("ncx-exit-btn").setAttributeNode(att);    }</script>');
			} else {
				
				cardsv2Log(response.messages);
				$('form.checkout').prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + response.messages + '</div>');
				$('#nochexapiCardsBtnReplace').remove();
				$('#place_order').show();
				$( 'html, body' ).animate( {
					scrollTop: ( 300 )
				}, 1000 );
				
				var place_orderbtn = jQuery( '#place_order' );
				place_orderbtn.on( 'click', wc_gateway_nochexapi.nochexapiCardsHandoff );
				//return false;
			}
			
            },
            error: function(error){
                cardsv2Log(error);
            }
        });
    }

    function validate_nochexapi_cardsv2_checkout(args){
        $('#nochexapi_cardsv2_container').empty();
        $('#place_order').after('<p id="nochexapiCardsBtnReplace" style="color:#CCC; text-align:center;">Processing, please wait...</p>');
        //$('#place_order').hide();
        var generalAlertMsg     = 'Uncertain Response. Please report this to the merchant before reattempting payment. They will need to verify if this transaction is successful.';
        Promise.resolve(
            $.ajax({
                type: 'POST',
                url: nochexapi.nochexapiCardVars.adminUrl,
                data: { action: globalPrefix + 'validate_nochexapi_cardsv2_checkout', resourcePath: args[0]},
                success: function(response) {
                    cardsv2Log(response);
                    if(response.hasOwnProperty("data")){
                        if(response.data.hasOwnProperty("url")){
                            window.location = (response.data.url);
                        } else {
                            alert( "Error(#1):" + generalAlertMsg + "\n" + 'resource:' + args[0] );
                            $('body').trigger("update_checkout");
                            $('#nochexapiCardsBtnReplace').remove();
                            $('#place_order').show();
                        }
                    } else {
                        alert( "Error(#2):" + generalAlertMsg + "\n" + 'resource:' + args[0] );
                        $('body').trigger("update_checkout");
                        $('#nochexapiCardsBtnReplace').remove();
                        $('#place_order').show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown){
                    alert( "Error(#3):" + generalAlertMsg + "\n" + 'Message::' + textStatus + '->' + errorThrown + "\n" + 'resource:' + args[0] );  
                    cardsv2Log(jqXHR);
                    cardsv2Log(textStatus);
                    cardsv2Log(errorThrown);
                },
            })
        ).then(function(){
            //do something
        }).catch(function(e) {
            alert( "Error(#3):" + generalAlertMsg + "\n" + 'resource:' + args[0] );
            cardsv2Log(e); 
        });
    }

    function progress_tp_cardsv2(args){
        if(args.length === 1){
            cardsv2Log('progress_tp_cardsv2: ' + args[0]);
            tpCardsInProgress = args[0];
        }
    }

    nochexapi.nochexapiCardsHandoff = function() {
	// onplaceorder click alert();
		/*if(validationTrigger() == true){
			console.log('test');/*break;*
			$('body').trigger("checkout_error");
			//jQuery('form.woocommerce-checkout').trigger("checkout_place_order");
			return false;
			
		}*/
		//alert("");
        if($('form.woocommerce-checkout').find('input[name^="payment_method"]:checked').val() !== nochexapi.nochexapiCardVars.pluginId){
            return;
        }
        var checkoutData = $("form.woocommerce-checkout").serialize();
        document.querySelector('form.woocommerce-checkout').classList.add('processing');
		
		$('body').trigger("update_checkout");
		
		
        completeOrdernochexapiCards(wc_checkout_params.checkout_url,checkoutData);
	
        return false;
    };

    nochexapi.init = function( options ){
        nochexapi.nochexapiCardVars               = options;
        globalPrefix                = options.pluginPrefix;
        cardIframeContainerID       = globalPrefix + 'iframe_container';
        cardIframeID                = globalPrefix + 'cnpFrame';
        cardsv2Log('checkout endpoint docReady!');
        cardsv2Log(nochexapi.nochexapiCardVars.pluginId + ' v.' + nochexapi.nochexapiCardVars.pluginVer);
        window.document.addEventListener('parentLogV52', logToParentWindow, false);
		
		
    var checkout_form = jQuery( 'form.woocommerce-checkout' );
    checkout_form.on( 'checkout_place_order', wc_gateway_nochexapi.nochexapiCardsHandoff );
	
    }
} )( window.wc_gateway_nochexapi = window.wc_gateway_nochexapi || {}, jQuery );

function getnochexapiGlobalVariable(){
	return nochexapi_CardVars;
}
jQuery( function(){
    var nochexapiGlobalVars    = getnochexapiGlobalVariable();
    wc_gateway_nochexapi.init( nochexapiGlobalVars );
    /*jQuery(document.body).on('updated_checkout', function() {
        window.wc_gateway_nochexapi.initCheckoutIdOrder();
    });*/
});

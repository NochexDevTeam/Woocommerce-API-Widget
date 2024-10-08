( function ( nochexapi, $ ) {
    'use strict';

    nochexapi.nochexapiCardVars                 = {};
    var nochexapiCardsInProgress         = false;
    var globalPrefix              = '';
    var cardIframeContainerID     = '';
    var cardIframeID              = '';
    var nochexVals                = '';

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
                }
            }
        });
    }
	
	function validationTrigger(){
		$('.validate-required input, .validate-required select').trigger('validate');
		if($('.validate-required').length != $('.woocommerce-validated').length){
			
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

        $('#place_order').hide();
		
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
				
				$('#ncx_form_container').html('<style>.ncx-parg{margin-bottom: 4px !important;} #ncx-email, #ncx-card_number, #ncx-expiry_month, #ncx-expiry_year, #ncx-cvv, #ncx-fullname, #ncx-address {outline: none !important; border: 1px solid #c9cedd !important; border-radius: 6px!important; background-color: #fff !important;} #ncx-address, #ncx-city, #ncx-postcode, #ncx-country {border: 0px solid transparent !important; background-color: #fff !important;} #ncx-email {background: url(https://secure.nochex.com/images/formIcons.png) !important; background-size: 34px auto !important; background-position: 1px -203px !important; background-repeat: no-repeat !important;} #ncx-card_number {background: url(https://secure.nochex.com/images/formIcons.png) !important; background-size: 32px auto !important; background-position: 1px -61px !important; background-repeat: no-repeat !important;} #ncx-expiry_month {background: url(https://secure.nochex.com/images/calendar.png) !important; background-size: 15px auto !important; background-position: 6px 11px !important; background-repeat: no-repeat !important;} #ncx-expiry_year {background: url(https://secure.nochex.com/images/calendar.png) !important; background-size: 15px auto !important; background-position: 6px 11px !important; background-repeat: no-repeat !important;} #ncx-cvv {background: url(https://secure.nochex.com/images/formIcons.png) !important; background-size: 32px auto !important; background-position: -1px 3px !important; background-repeat: no-repeat !important;} #ncx-fullname {background: url(https://secure.nochex.com/images/formIcons.png) !important; background-size: 32px auto !important; background-position: 0px -127px !important; background-repeat: no-repeat !important;} #ncx-address {background: url(https://secure.nochex.com/images/Home.png) !important; background-size: 18px auto !important; background-position: 8px 8px !important; background-repeat: no-repeat !important;} #ncx-city {background: url(https://secure.nochex.com/images/formIcons.png) !important; background-size: 32px auto !important; background-position: 1px -159px !important; background-repeat: no-repeat !important;} #ncx-postcode {background: url(https://secure.nochex.com/images/formIcons.png) !important; background-size: 30px auto !important; background-position: 1px -241px !important; background-repeat: no-repeat !important;} #ncx-country {padding-left: 31px !important; margin-top: 3px; background: url(https://secure.nochex.com/images/formIcons.png) !important; background-size: 32px auto !important; background-position: 2px -93px !important; background-repeat: no-repeat !important;} </style><input type="button" value="Continue" id="ncx-show-checkout" class="btn btn-primary" style="display:none"><script>var cell1 = document.getElementById("ncx_form_container");  var ncFm = document.createElement("script");  ncFm.setAttribute("id", "ncx-config");  ncFm.setAttribute("ncxField-api_key", "' + nochexVals.apiKey + '");  ncFm.setAttribute("NCXFIELD-MERCHANT_ID", "' + nochexVals.merchantId + '");  ncFm.setAttribute("NCXFIELD-order_id", "' + nochexVals.merchantTransactionId +'");  ncFm.setAttribute("NCXFIELD-AMOUNT", "' + nochexVals.amount +'");  ncFm.setAttribute("ncxField-address", "' + nochexVals.billingstreet1 + '");  ncFm.setAttribute("ncxField-city", "' + nochexVals.billingcity + '");  ncFm.setAttribute("ncxField-postcode", "' + nochexVals.billingpostcode + '");  ncFm.setAttribute("ncxField-email", "' + nochexVals.customeremail + '");  ncFm.setAttribute("ncxField-optional_2", "Enabled");  ncFm.setAttribute("ncxField-fullname", "' + nochexVals.cardholder + '");  ncFm.setAttribute("ncxField-phone", "' + nochexVals.customermobile + '");  ncFm.setAttribute("ncxField-callback_url", "' + nochexVals.callbackurl + '");  ncFm.setAttribute("ncxField-success_url", "' + response.pay_url + '");  ncFm.setAttribute("ncxField-test_transaction", "' + nochexVals.testMode + '");  ncFm.setAttribute("ncxField-autoredirect", "True");   var ncxLib = document.createElement("script");   ncxLib.setAttribute("id", "ncxLib");   ncxLib.setAttribute("src", "https://secure.nochex.com/exp/nochex_lib.js");  var formnode = document.createElement("form");  formnode.setAttribute("name", "ncx-form");  formnode.setAttribute("id", "nochexForm");  formnode.setAttribute("class", "ncx-form");  cell1.appendChild(formnode);  formnode.appendChild(ncFm); cell1.appendChild(ncxLib);var $jQuery = jQuery.noConflict(); showPopup(); async function showPopup() {await until(_ => $jQuery("#ncx-exit-btn").length == 1 && $jQuery("#ncx-show-checkout").hasClass("ncx-loading")==false);try {document.getElementById("ncx-show-checkout").click(); document.getElementById("ncx-exit-btn").setAttribute("onClick", "location.reload();")} catch (error) { console.log(error) }};function until(conditionFunction) {const poll = resolve => {if(conditionFunction()) resolve();else setTimeout(_ => poll(resolve), 100);};return new Promise(poll);}</script>');
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

    function progress_nx(args){
        if(args.length === 1){
            cardsv2Log('progress_nx: ' + args[0]);
            ncxInProgress = args[0];
        }
    }

    nochexapi.nochexapiCardsHandoff = function() {
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
});

( function () {
	'use strict';

	var registerPaymentMethod = wc.wcBlocksRegistry.registerPaymentMethod;
	var createElement         = wp.element.createElement;
	var settings              = wc.wcSettings.getSetting( 'nochexapi_data', {} );

	function mountWidgetFromPayload( payload, payUrl ) {
		if ( ! payload || ! payload.apiKey || ! payload.merchantId ) {
			return;
		}

		var container = document.getElementById( 'ncx_form_container' );
		if ( ! container ) {
			container = document.createElement( 'div' );
			container.id = 'ncx_form_container';
			document.body.appendChild( container );
		}

		container.innerHTML = '';

		var formNode = document.createElement( 'form' );
		formNode.setAttribute( 'name', 'ncx-form' );
		formNode.setAttribute( 'id', 'nochexForm' );
		formNode.setAttribute( 'class', 'ncx-form' );
		container.appendChild( formNode );

		var showCheckoutButton = document.createElement( 'input' );
		showCheckoutButton.setAttribute( 'type', 'button' );
		showCheckoutButton.setAttribute( 'id', 'ncx-show-checkout' );
		showCheckoutButton.setAttribute( 'value', 'Continue' );
		showCheckoutButton.style.display = 'none';
		container.appendChild( showCheckoutButton );

		var configNode = document.createElement( 'script' );
		configNode.setAttribute( 'id', 'ncx-config' );
		configNode.setAttribute( 'ncxField-api_key', payload.apiKey || '' );
		configNode.setAttribute( 'NCXFIELD-MERCHANT_ID', payload.merchantId || '' );
		configNode.setAttribute( 'NCXFIELD-order_id', payload.merchantTransactionId || '' );
		configNode.setAttribute( 'NCXFIELD-AMOUNT', payload.amount || '' );
		configNode.setAttribute( 'ncxField-address', payload.billingstreet1 || '' );
		configNode.setAttribute( 'ncxField-city', payload.billingcity || '' );
		configNode.setAttribute( 'ncxField-postcode', payload.billingpostcode || '' );
		configNode.setAttribute( 'ncxField-email', payload.customeremail || '' );
		configNode.setAttribute( 'ncxField-optional_2', 'Enabled' );
		configNode.setAttribute( 'ncxField-fullname', payload.cardholder || '' );
		configNode.setAttribute( 'ncxField-phone', payload.customermobile || '' );
		configNode.setAttribute( 'ncxField-callback_url', payload.callbackurl || '' );
		configNode.setAttribute( 'ncxField-success_url', payUrl || '' );
		configNode.setAttribute( 'ncxField-test_transaction', payload.testMode ? 'true' : 'false' );
		configNode.setAttribute( 'ncxField-autoredirect', 'True' );
		formNode.appendChild( configNode );

		var libNode = document.createElement( 'script' );
		libNode.setAttribute( 'id', 'ncxLib' );
		libNode.setAttribute( 'src', 'https://secure.nochex.com/exp/nochex_lib.js' );
		container.appendChild( libNode );

		libNode.onload = function () {
			var attempts = 0;
			var maxTries = 60;
			var poll = window.setInterval( function () {
				attempts++;
				var continueButton = document.getElementById( 'ncx-show-checkout' );
				if ( continueButton && ! continueButton.classList.contains( 'ncx-loading' ) ) {
					window.clearInterval( poll );
					continueButton.click();
					return;
				}
				if ( attempts >= maxTries ) {
					window.clearInterval( poll );
				}
			}, 100 );
		};
	}

	function readProcessPaymentData( checkoutSuccessData ) {
		if ( ! checkoutSuccessData ) {
			return null;
		}
		var processing = checkoutSuccessData.processingResponse || {};
		var candidates = [
			processing.paymentDetails,
			processing.payment_details,
			processing,
			checkoutSuccessData.paymentDetails,
			checkoutSuccessData.payment_details,
		];
		var payloadSrc = null;
		for ( var i = 0; i < candidates.length; i++ ) {
			if ( candidates[ i ] && candidates[ i ].popNCX ) {
				payloadSrc = candidates[ i ];
				break;
			}
		}
		var popNCX = payloadSrc ? payloadSrc.popNCX : '';

		if ( ! popNCX ) {
			return null;
		}

		try {
			return {
				payload: JSON.parse( popNCX ),
				payUrl: payloadSrc.pay_url || '',
			};
		} catch ( e ) {
			return null;
		}
	}

	var Content = function ( props ) {
		var onCheckoutSuccess = props.eventRegistration.onCheckoutSuccess;
		var emitResponse      = props.emitResponse;

		wp.element.useEffect( function () {
			if ( ! onCheckoutSuccess ) {
				return;
			}

			var unsubscribe = onCheckoutSuccess( function ( checkoutSuccessData ) {
				var parsed = readProcessPaymentData( checkoutSuccessData );

				if ( parsed ) {
					mountWidgetFromPayload( parsed.payload, parsed.payUrl );
					return {
						type: emitResponse.responseTypes.SUCCESS,
					};
				}

				return {
					type: emitResponse.responseTypes.ERROR,
					message: 'Unable to initialize Nochex secure widget.',
				};
			} );

			return unsubscribe;
		}, [ onCheckoutSuccess ] );

		return createElement(
			'div',
			{ className: 'nochexapi-blocks-container' },
			( settings.description || 'Pay securely using Nochex.' )
		);
	};

	var Label = function () {
		return createElement( 'span', null, settings.title || 'Nochex API Widget' );
	};

	registerPaymentMethod( {
		name: 'nochexapi',
		label: createElement( Label, null ),
		content: createElement( Content, null ),
		edit: createElement( 'div', null, settings.title || 'Nochex API Widget' ),
		canMakePayment: function () {
			return true;
		},
		ariaLabel: settings.title || 'Nochex API Widget',
		supports: {
			features: settings.supports || [ 'products' ],
		},
	} );
} )();

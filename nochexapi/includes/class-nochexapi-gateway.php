<?php

use Nochexapi\WC_Nochexapi_Constants AS Nochexapi_CONSTANTS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/nochexapi-helper-trait.php';

class WC_Payment_Gateway_Nochexapi extends WC_Payment_Gateway {
    use NochexapiHelperTrait;

    public function __construct() {

        $this->id                                = Nochexapi_CONSTANTS::GATEWAY_ID;
        $this->icon                              = '';
        $this->has_fields                        = true;
        $this->method_title                      = Nochexapi_CONSTANTS::GATEWAY_TITLE;
        $this->method_description                = Nochexapi_CONSTANTS::GATEWAY_DESCRIPTION;
        $this->init_form_fields();
        $this->init_settings();		
        $this->title                             = $this->get_option( 'title' );
        $this->description                       = $this->get_option( 'description' );
       		
        // Initialise settings
        //Load funcs
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );		
		
		// APC Handler
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'apc' ) );
    }
	
	/**
	 * Perform Automatic Payment Confirmation (APC)
	 *
	 * @access public
	 * @return void
	 */
	 
	function apc() {		
		global $woocommerce;
	
		if($_POST){
			$this->apc = include dirname( __FILE__ ) . '/class-wc-nochex-apccallback.php';
		}
	}

    
    /*
     * Function to preparing of setting fields for different tabs
     */
    public function settings_fields( $merge = false ) {
        $settings                  = include dirname( __FILE__ ) . '/payment-gateway-setting-fields.php';
        
        if( $merge === true ){
            $retArr = [];
            foreach( $settings as $subSettings ){
                if( count( $subSettings ) > 0 ){
                    if( isset( $subSettings['fields'] ) ){
                        if( count( $subSettings['fields'] ) > 0 ){
                            $retArr = array_merge( $retArr, $subSettings['fields'] );
                        }
                    }
                }
            }
            return $retArr;
        }
        return $settings;
    }

    /*
     * Function to initialization of setting fields for different tabs
     */
    public function init_form_fields(){
        $this->form_fields = $this->settings_fields(true);
    }
    
    /*
     * Function to initialization of admin option fields
     */
    public function admin_options() {
        require_once Nochexapi_CONSTANTS::getPluginRootPath() . 'admin/partials/admin-options.php';
    }
    
    /*
     * Function to initialization of gateway icons
     */
    public function get_icon() {
        $icons_str = '<img src="https://www.nochex.com/logobase-secure-images/logobase-banners/clear.png" class="nochexapi-payment-gateways-icon-img" alt="Nochexapi" style="width: 270px;max-width:inherit;max-height:inherit;height: auto;float:unset!important;padding-top: 0px;">'."\n";
	    return apply_filters( 'woocommerce_gateway_icon', $icons_str, $this->id );
    }
    /*
     * Function to initialization of setting fields for different tabs
     */
    
    public function check_plugin(){
        //check for checkout page only.
        if(!empty($this->get_option( 'enabled' ))){
            return true;
        }
        return false;
    }

  public function run(){
       $plugin_basename = Nochexapi_CONSTANTS::getPluginBaseName();
        //check plugin
        if($this->check_plugin()){
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts_cards' ), PHP_INT_MAX );
            add_action( 'wp_ajax_' . Nochexapi_CONSTANTS::GLOBAL_PREFIX . 'feLogger', array( $this, 'feLogger' ) );
            add_action( 'wp_ajax_nopriv_' . Nochexapi_CONSTANTS::GLOBAL_PREFIX . 'feLogger', array( $this, 'feLogger' ) );
        }
        //filter hooks
        add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'plugin_action_links' ) );
        add_action( 'woocommerce_after_checkout_form', array( $this, 'ncx_cards_formhtml' ), 10 );
    }
    
    public function ncx_cards_formhtml(){
        echo '<div id="ncx_form_container"></div>';
    }
    
    public function plugin_action_links( $links ) {
        $plugin_links = array(
            '<a href="admin.php?page=wc-settings&tab=checkout&section='.$this->id.'">' . 'Settings' . '</a>',
        );
        return array_merge( $plugin_links, $links );
    }
    
    public function checkPageShouldRun(){
        global $wp;
        if(is_checkout() === true && empty( $wp->query_vars['order-received'] )){
            return true;
        }
        if(is_wc_endpoint_url( 'order-pay' )){
            return true;
        }
        if(is_add_payment_method_page()){
            return true;    
        }
        return false;
    }
    
    public function payment_scripts_cards() {
        if($this->checkPageShouldRun() === true){
			
            $ncx_pluginVer = Nochexapi_CONSTANTS::VERSION;
            $prefix       = Nochexapi_CONSTANTS::GLOBAL_PREFIX;
                
			wp_register_script( $prefix . 'ncx_cards', plugin_dir_url( dirname( __FILE__ ) ).'assets/js/nochexapi-cardsv2x.js' , ['jquery','wp-util'], $ncx_pluginVer );
            
            wp_register_script( $prefix . 'ncx_fetch', 'https://cdn.jsdelivr.net/npm/whatwg-fetch@3.4.0/dist/fetch.umd.min.js' , ['jquery','wp-util'] , true );
            wp_register_script( $prefix . 'ncx_swal', 'https://cdn.jsdelivr.net/npm/sweetalert2@10/dist/sweetalert2.all.min.js' , ['jquery','wp-util'] , true );
            wp_register_script( $prefix . 'ncx_swal_poly1', 'https://cdnjs.cloudflare.com/ajax/libs/babel-polyfill/7.12.1/polyfill.min.js' , [] , true );
            wp_register_script( $prefix . 'ncx_swal_poly2', 'https://cdn.jsdelivr.net/npm/promise-polyfill@8.1.3/dist/polyfill.js' , [] , true );
            wp_localize_script( $prefix . 'ncx_cards', $prefix . 'CardVars', [
                "subTotalAmount" => (string)WC()->cart->get_total(null),
                "checkoutUrl" => urlencode(wc_get_checkout_url()),
                "pluginPrefix" => $prefix,
                "pluginId" => Nochexapi_CONSTANTS::GATEWAY_ID,
                "jsLogging" => ($this->get_option( 'jsLogging' ) === 'yes' ? true : false),
                "pluginVer" => $ncx_pluginVer,
                "adminUrl" => get_admin_url().'admin-ajax.php',
                "assetsDir" => plugin_dir_url( dirname( __FILE__ ) ).'assets',
                "frameUrlEncoded" => urlencode( get_the_guid( (int)get_option( Nochexapi_CONSTANTS::GLOBAL_PREFIX . 'gateway_cardsv2_iframe' ) ) ),
                "loggedIn" => is_user_logged_in(),
                "feNonce" => wp_create_nonce('ncx_feLogger'),
            ]);
            
            wp_enqueue_script($prefix . 'ncx_fetch');
            wp_enqueue_script($prefix . 'ncx_swal_poly2');
            wp_enqueue_script($prefix . 'ncx_cards');
        }
    }
    
    /*
     * This sends payload to generate checkoutid for checkout form payment gateway or create registration to create saved cards
     */
    public function createCheckoutArray($obj=true,$forceAmount=false,$registrationId=false, $additionalParameters = []){
        if($forceAmount !== false){
            $amount = $forceAmount;
        } else {
            $amount = WC()->cart->get_total(null);
        }
        if((float)$amount === 0){ return false; }
        $registrationIds=[];
        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency'=>get_woocommerce_currency(),
            'standingInstruction.mode'=>'INITIAL',
            'standingInstruction.type'=>'UNSCHEDULED',
            'standingInstruction.source'=>'CIT',
        ];
        if((float)$amount === -0.01){
            if(is_user_logged_in() !== true){
                return false;
            }
            unset($payload['amount']);
            unset($payload['currency']);
            $payload['customer.merchantCustomerId'] = get_current_user_id();
        } 
        
        $array = [
            'method' => 'POST',
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'body' => $payload
        ];
     
        return false;
    }
    
    public function genCheckoutId(){
        $this->writeLog( '----- genCheckoutId -----', 'debug' );
        $responseData = $this->createCheckoutArray(false,-0.01);

        if($responseData){
            wp_send_json_success($responseData);
        } else {
            wp_send_json_error();
        }
    }
    
    /*
     * This function is instantiated when checkout page loads or payment fragment refreshes
     */
    public function genCheckoutIdOrder(){
        $this->writeLog( '----- genCheckoutIdOrder // -----', 'debug' );
		
		wp_send_json_success([
            'uuid' => "abc123",
            'frameurl' => 'nochex.com' ,
        ]);
		
		return true;
    }
    
    public function feLogger(){
        $errors = [];
        $postArr = sanitize_post($_POST);
        if(isset($postArr['fe_nonce'])){
            if(wp_verify_nonce($postArr['fe_nonce'],'ncx_feLogger')){
                $array = wp_strip_all_tags($postArr['msg']);
                $level = 'error';
                if(isset($postArr['level'])){
                    $level = $postArr['level'];
                }
                $this->writeLog('------------feLogger---------','debug');
                $this->writeLog($array,'error');
                wp_send_json_success($postArr);
            } else {
                $errors[] = 'nonce validation failed';
            }
        } else {
            $errors[] = 'fe_nonce nonce not found';
        }
        wp_send_json_error($errors);
    }
    
    public function validatePayForOrder($postArr){
        $validation = [
            'result' => false,
            'refresh' => false,
            'errors' => []
        ];
        if(!is_array($postArr)){
            $validation['errors'][] = 'is not array';
        } else if(!isset($postArr['woocommerce_pay'])){
            $validation['errors'][] = 'is not woocommerce_pay';
        } else {
            if(!isset($postArr['payment_method'])){
            $validation['errors'][] = 'payment_method is not set';
            } 
            if(!isset($postArr['wp_http_referer'])){
                $validation['errors'][] = 'wp_http_referer is not set';
            }
            if($postArr['payment_method'] !== $this->id){
                $validation['errors'][] = 'payment_method not ' . $this->id;
            }
        }
        if(count($validation['errors']) > 0){
            return $validation;
        }        
        if(isset($postArr['terms_field'])){
            if($postArr['terms_field'] === '1'){
                if(!isset($postArr['terms'])){
                    $validation['errors'][] = 'Please read and accept the terms and conditions to proceed with your order.';
                } else {
                    if($postArr['terms'] !== 'on'){
                        $validation['errors'][] = 'Please read and accept the terms and conditions to proceed with your order.';
                    }
                }
            }
        }
        if(count($validation['errors']) > 0){
            return $validation;
        }
        $parseUrl = parse_url($postArr['wp_http_referer']);
        if(!isset($parseUrl['query'])){
            $validation['errors'][] = 'no query args';
            return $validation;
        }
        parse_str($parseUrl['query'], $parseStr);
        if(!isset($parseStr['key'])){
            $validation['errors'][] = 'no key in query args';
            return $validation;
        }
        //$validation['order_key'] = $parseStr['key'];
        $order_id = wc_get_order_id_by_order_key( $parseStr['key'] );
        if($order_id === 0){
            $validation['errors'][] = 'no order found by key: ' . $parseStr['key'];
        } else {
            //$validation['order_id'] = $order_id;
            $order = wc_get_order( $order_id );
            if(!$order){
                $validation['errors'][] = 'could not load order_id: ' . $order_id;
            } else {
                $order_data = $order->get_data();
                //$validation['order_status'] = $order_data['status'];
                if($order_data['status'] !== 'pending'){
                    $validation['errors'][] = 'order is in an invalid status: ' . $order_data['status'];
                } else {
                    //set gateway as payment method
                    $order->set_payment_method( $this->id );
                    //save order
                    $orderId = $order->save();
                    //return success
                    $validation['result'] = true;
                    $validation['order_id'] = $orderId;
                }
            }
        }
        return $validation;
    }
    
    public function orderStatusHandler($status,$order){
        $array=[
            'pending'    => ['result'=>'success', 'redirect' => false, 'refresh' => false, 'reload' => false, 'pending'=>true, 'process' => ["order"=>true]],
            'cancelled'  => ['result'=>'failure', 'redirect' => false, 'refresh' => false, 'reload' => false, 'messages' => ['error' => ['This order has been cancelled. Please retry your order.']]],
            'failed'     => ['result'=>'failure', 'redirect' => false, 'refresh' => false, 'reload' => true, 'messages' => ['error' => ['There was a problem creating your order, please try again.']]],
        ];
        if(array_key_exists($status, $array)){
            return $array[$status];
        }
        return $array['failed'];
    }
    
    public function process_payment($order_id){
        //first write to log if set.
        $this->writeLog('-----------process_payment POST-----------', 'debug');
        $this->writeLog($_POST,'debug');
        
        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
		
        $handler = $this->orderStatusHandler($order_data['status'],$order);
        //check the order_id exists.
        if($order===false){
            $this->writeLog('-----------process_payment Order does not exists-----------', 'debug');
            wc_add_notice('There was a problem creating your order, please try again.', 'error');
			$handler['result'] = "failed";
            
            return $handler;
        }
		
		//print_r($order_data['billing']);
		if(empty($order_data['billing']['phone'])){
			wc_add_notice('There was a problem creating your order, please try again.', 'error');
			$handler['messages'] =  'There was a problem creating your order, please try again.';
			$handler['result'] = "failed";
            $this->writeLog('-----------phone bill------------','debug');
            return $handler;
		}
		
        //reject the failed, cancelled on-hold & success
        if(!isset($handler['pending'])){
            if(isset($handler['messages'])){
                $this->writeLog('-----------process_payment pending order has message issue------------','debug');
                $this->writeLog($handler['messages'],'warning');
                foreach($handler['messages'] as $noticeType => $noticeItems){
                    foreach($noticeItems as $notice){
                        wc_add_notice($notice, $noticeType);
                    }
                }
            }
            $this->writeLog('-----------process_payment if pending order does not have message----------','debug');
            $this->writeLog($handler,'warning');
            return $handler;
        }
        //pending orders!
        
        $additionalParams = [];
        $additionalParams['shopperResultUrl'] = $this->get_return_url( $order );
        $this->writeLog('-----------process_payment shopperResultUrl ----------','debug');
        $this->writeLog($additionalParams,'debug');

        //check for RG slick
        $handler['fullPost'] = $_POST;
        $updateCheckout = false;
        $checkoutId = false;
        $checkoutCode = '';
        $checkoutJson = '{}';
        if(isset($_POST[ Nochexapi_CONSTANTS::GLOBAL_PREFIX . 'checkout_id' ])){
            if(!empty($_POST[ Nochexapi_CONSTANTS::GLOBAL_PREFIX . 'checkout_id' ])){
                $checkoutId = sanitize_text_field($_POST[ Nochexapi_CONSTANTS::GLOBAL_PREFIX . 'checkout_id' ]);
                $updateCheckout = true;
            }
        }
        if($checkoutId === false){
            $this->writeLog('-----------process_payment checkout id missing and create new checkout id-----------','debug');
        }
        $handler['pay_url'] = $this->get_return_url( $order );
        
        //enforce non-checking checkoutId status   ///////////////////// list of cust dets 
        $payload = $this->prepareOrderDataForPayload($order_data,$additionalParams);

        $checkoutJson = json_encode($payload);
        
		$handler['popNCX'] = json_encode($payload);
		
		$handler['result'] = "success";
        
        $this->writeLog($handler,'debug');
        
        return $handler;
    }

    public function prepareOrderDataForPayload($order_data,$additionalParams = []){
        global $wp_version;
		
        $payload = [
            "amount" => $order_data['total'],
            "currency" => $order_data['currency'],
            "merchantTransactionId" => $order_data['id'],
            "optional_2" => 'Enabled',
            "customer.merchantCustomerId" => $order_data['customer_id'],
            "order_key" => $order_data['order_key'],
            "customParameters[SHOPPER_cart_hash]" => $order_data['cart_hash'],
            "cardholder" => (string)(preg_replace('/[^a-z ]/i', '',$order_data['billing']['first_name']).' '. preg_replace('/[^a-z ]/i', '',$order_data['billing']['last_name'])),
            "customeremail" => sanitize_email($order_data['billing']['email']),
            "merchantId" => $this->get_option( 'merchantId' ),
            "apiKey" =>  $this->get_option( 'apikey' ),
            "callbackurl" =>  add_query_arg( 'wc-api', $this->id, home_url( '/' ) ),
            "testMode" =>  ($this->get_option( 'testMode' ) === 'yes' ? true : false),
        ];
        if(isset($order_data['billing']['phone'])){
            if(!empty($order_data['billing']['phone'])){
                $payload["customermobile"] = preg_replace( '/[^0-9]/', '', $order_data['billing']['phone'] );
            }
        }
        if(isset($order_data['billing']['address_1'])){
            if(!empty($order_data['billing']['address_1'])){
                $payload["billingstreet1"] = preg_replace('/[^\da-z ]/i', '',$order_data['billing']['address_1']);
            }
        }
        if(isset($order_data['billing']['address_2'])){
            if(!empty($order_data['billing']['address_2'])){
                $payload["billingstreet2"] = preg_replace('/[^\da-z ]/i', '',$order_data['billing']['address_2']);
            }
        }
        if(isset($order_data['billing']['city'])){
            if(!empty($order_data['billing']['city'])){
                $payload["billingcity"] = preg_replace('/[^a-z ]/i', '',$order_data['billing']['city']);
            }
        }
        if(isset($order_data['billing']['state'])){
            if(!empty($order_data['billing']['state'])){
                $payload["billingstate"] = preg_replace('/[^a-z ]/i', '',$order_data['billing']['state']);
            }
        }
        if(isset($order_data['billing']['postcode'])){
            if(!empty($order_data['billing']['postcode'])){
                $payload["billingpostcode"] = preg_replace('/[^\da-z ]/i', '',$order_data['billing']['postcode']);
            }
        }
        if(isset($order_data['billing']['country'])){
            if(!empty($order_data['billing']['country'])){
                $payload["billingcountry"] = preg_replace('/[^a-z ]/i', '',$order_data['billing']['country']);
            }
        }
		$payload["description"] = $this->getCartItemsOrderData($order_data['id']);
        
        return array_merge($payload, $additionalParams);
        //return $payload;
    }
    
    public function getCartItemsOrderData($order_id){
        $payload = [];
        $cartname = "";
        $order_id = (int)$order_id;
        $oObj = wc_get_order($order_id);
        foreach($oObj->get_items('line_item') as $oItemId => $oItem){			
			$cartname .= $oItem->get_name() . " - ". $oItem->get_quantity() . " x ". $oItem->get_total();
        }
        return $cartname;
    }
    
    public function validate_fields(){
		$sess_order_id = absint( WC()->session->get( 'order_awaiting_payment' ) );
		$sess_cart_hash = WC()->cart->get_cart_hash();
		if($sess_order_id > 0){
			$sess_order = $sess_order_id ? wc_get_order( $sess_order_id ) : false;
			if($sess_order !== false){
				if ( $sess_order->has_cart_hash( $sess_cart_hash ) && $sess_order->has_status( array( 'pending', 'failed' ) ) ) { 
				} else {
					$sess_order->update_status( 'cancelled' , 'superseded by new order' ); 
				}
			}
		}
	return true;
    }
    
    public function add_payment_method() {
        return array(
            'result'   => 'failure',
            'redirect' => wc_get_endpoint_url( 'payment-methods' ),
        );
    }
    
} //end class

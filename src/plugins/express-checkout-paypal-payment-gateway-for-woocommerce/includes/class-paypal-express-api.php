<?php

if (!defined('ABSPATH')) {
    exit;
}

class Eh_PayPal_Express_Payment extends WC_Payment_Gateway {

    protected $request;

    public function __construct() {
        $this->id = 'eh_paypal_express';
        $this->method_title = __('PayPal Express', 'express-checkout-paypal-payment-gateway-for-woocommerce');
        $this->method_description = sprintf(__("Allow customers to directly checkout with PayPal.", 'express-checkout-paypal-payment-gateway-for-woocommerce'));
        $this->has_fields = true;
        $this->supports = array(
            'products'
        );
        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->environment = $this->get_option('environment');
        $this->sandbox_username = $this->get_option('sandbox_username');
        $this->sandbox_password = $this->get_option('sandbox_password');
        $this->sandbox_signature = $this->get_option('sandbox_signature');
        $this->live_username = $this->get_option('live_username');
        $this->live_password = $this->get_option('live_password');
        $this->live_signature = $this->get_option('live_signature');
        $this->express_enabled = $this->get_option('express_enabled') === "yes" ? true : false;
        $this->credit_checkout = $this->get_option('credit_checkout') === "yes" ? true : false;
        $this->button_size = $this->get_option('button_size');
        $this->express_description = $this->get_option('express_description');
        $this->express_on_cart_page = $this->get_option('express_on_cart_page') === "yes" ? true : false;
        $this->business_name = $this->get_option('business_name');
        $this->paypal_allow_override = $this->get_option('paypal_allow_override') === "yes" ? true : false;
        $this->paypal_locale = $this->get_option('paypal_locale') === "yes" ? true : false;
        $this->landing_page = $this->get_option('landing_page');
        // $this->customer_service = $this->get_option('customer_service');
        $this->checkout_logo = $this->get_option('checkout_logo');
        $this->checkout_banner = $this->get_option('checkout_banner');
        $this->skip_review = $this->get_option('skip_review') === "yes" ? true : false;
        $this->policy_notes = $this->get_option('policy_notes');
        $this->paypal_logging = $this->get_option('paypal_logging') === "yes" ? true : false;
        $this->order_completed_status = true;
        $this->invoice_prefix = $this->get_option('invoice_prefix');
        // $this->ipn_url = $this->get_option('ipn_url');
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }
        $this->order_button_text = (isset(WC()->session->eh_pe_checkout)) ? __('Place Order', 'express-checkout-paypal-payment-gateway-for-woocommerce') : __('Proceed to PayPal', 'express-checkout-paypal-payment-gateway-for-woocommerce');
        if ($this->environment === 'sandbox') {
            $this->api_username = $this->sandbox_username;
            $this->api_password = $this->sandbox_password;
            $this->api_signature = $this->sandbox_signature;
            $this->nvp_url = "https://api-3t.sandbox.paypal.com/nvp";
            $this->scr_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        } else {
            $this->api_username = $this->live_username;
            $this->api_password = $this->live_password;
            $this->api_signature = $this->live_signature;
            $this->nvp_url = "https://api-3t.paypal.com/nvp";
            $this->scr_url = "https://www.paypal.com/cgi-bin/webscr";
        }
        // if (!has_action('woocommerce_api_' . strtolower('Eh_PayPal_Express_Payment'))) {
            add_action('woocommerce_api_' . strtolower('Eh_PayPal_Express_Payment'), array($this, 'perform_api_request_paypal'));
        // }
        
        add_action('woocommerce_available_payment_gateways', array($this, 'gateways_hide_on_review'));
        add_action('woocommerce_after_checkout_validation', array($this, 'process_express_checkout'), 10, (WC()->version < '2.7.0') ? 1 : 2);
        add_action('woocommerce_checkout_billing', array($this, 'fill_checkout_fields_on_review'));
        add_action('woocommerce_checkout_fields', array($this, 'hide_checkout_fields_on_review'), 11);
        add_action('woocommerce_before_checkout_billing_form', array($this, 'fill_billing_details_on_review'), 9);
        add_action('woocommerce_review_order_after_submit', array($this, 'add_cancel_order_elements'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_review_order_after_payment', array($this, 'add_policy_notes'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

        if ( function_exists( 'add_image_size' ) ) {
			add_image_size( 'eh_logo_image_size', 190, 90 );
			add_image_size( 'eh_header_image_size', 750, 90 );
		}
    }

    public function init_form_fields() {
        $this->form_fields = include( 'eh-paypal-express-settings-page.php' );
    }

    public function process_admin_options() {
       
        parent::process_admin_options();

        $eh_paypal = get_option("woocommerce_eh_paypal_express_settings");
    
        if(isset($eh_paypal['express_enabled']) && ($eh_paypal['express_enabled'] === 'yes')){
            $eh_paypal['express_enabled'] = 'no';
        }
        if(isset($eh_paypal['credit_checkout']) && ($eh_paypal['credit_checkout'] === 'yes')){
            $eh_paypal['credit_checkout'] = 'no';
        }
        if(isset($eh_paypal['express_on_cart_page']) && ($eh_paypal['express_on_cart_page'] === 'yes')){
            $eh_paypal['express_on_cart_page'] = 'no';
        }
        if(isset($eh_paypal['express_on_checkout_page']) && ($eh_paypal['express_on_checkout_page'] === 'yes')){
            $eh_paypal['express_on_checkout_page'] = 'no';
        }
        update_option('woocommerce_eh_paypal_express_settings',$eh_paypal);
    }

    public function is_available() {
        if ('yes' === $this->enabled) {
            if (!$this->environment && is_checkout()) {
                return false;
            }
            if ('sandbox' === $this->environment) {
                if (!$this->sandbox_username || !$this->sandbox_password || !$this->sandbox_signature) {
                    return false;
                }
            } else {
                if (!$this->live_username || !$this->live_password || !$this->live_signature) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function generate_image_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
        
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data  = wp_parse_args( $data, $defaults );
       
		$value = $this->get_option( $key );
        
		$eh_maybe_hide_add_style    = '';
		$eh_maybe_hide_remove_style = '';

		// For backwards compatibility (customers that already have set a url)
		$eh_value_is_url = filter_var( $value, FILTER_VALIDATE_URL ) !== false;
        
		if ( empty( $value ) || $eh_value_is_url ) {
			$eh_maybe_hide_remove_style = 'display: none;';
		} else {
			$eh_maybe_hide_add_style = 'display: none;';
		}

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
			</th>

			<td class="eh-image-component-wrapper">
				<div class="eh-image-preview-wrapper">
					<?php
					if ( ! $eh_value_is_url ) {
						echo wp_get_attachment_image( $value, 'checkout_logo' === $key ? 'eh_logo_image_size' : 'eh_header_image_size' );
					} else {
						echo sprintf( esc_html__( 'Already using URL as image: %s', 'express-checkout-paypal-payment-gateway-for-woocommerce' ), esc_attr( $value ) );
					}
					?>
				</div>

				<button
					class="button eh_image_upload"
					data-field-id="<?php echo esc_attr( $field_key ); ?>"
					data-media-frame-title="<?php echo esc_attr( __( 'Select a image to upload', 'express-checkout-paypal-payment-gateway-for-woocommerce' ) ); ?>"
					data-media-frame-button="<?php echo esc_attr( __( 'Use this image', 'express-checkout-paypal-payment-gateway-for-woocommerce' ) ); ?>"
					data-add-image-text="<?php echo esc_attr( __( 'Add image', 'express-checkout-paypal-payment-gateway-for-woocommerce' ) ); ?>"
					style="<?php echo esc_attr( $eh_maybe_hide_add_style ); ?>"
				>
					<?php echo esc_html__( 'Add image', 'express-checkout-paypal-payment-gateway-for-woocommerce' ); ?>
				</button>

				<button
					class="button eh_image_remove"
					data-field-id="<?php echo esc_attr( $field_key ); ?>"
					style="<?php echo esc_attr( $eh_maybe_hide_remove_style ); ?>"
				>
					<?php echo esc_html__( 'Remove image', 'express-checkout-paypal-payment-gateway-for-woocommerce' ); ?>
				</button>

				<input type="hidden"
					name="<?php echo esc_attr( $field_key ); ?>"
					id="<?php echo esc_attr( $field_key ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
				/>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

    public function admin_options() {
        include_once("market.php");
        wc_enqueue_js("
                        jQuery( function( $ ) {
                            $('.description').css({'font-style':'normal'});
                            $('.eh-css-class').css({'border-top': 'dashed 1px #ccc','padding-top': '15px','width': '68%'}); 
                            var eh_paypal_express_live              =jQuery(  '#woocommerce_eh_paypal_express_live_username, #woocommerce_eh_paypal_express_live_password, #woocommerce_eh_paypal_express_live_signature').closest( 'tr' );
                            var eh_paypal_express_sandbox           = jQuery( '#woocommerce_eh_paypal_express_sandbox_username, #woocommerce_eh_paypal_express_sandbox_password, #woocommerce_eh_paypal_express_sandbox_signature').closest( 'tr' );
                            var eh_paypal_express_regular           = jQuery( '#woocommerce_eh_paypal_express_title, #woocommerce_eh_paypal_express_description').closest( 'tr' );
                            var eh_paypal_express_express           = jQuery( '#woocommerce_eh_paypal_express_credit_checkout,#woocommerce_eh_paypal_express_button_size,#woocommerce_eh_paypal_express_express_description, #woocommerce_eh_paypal_express_express_on_cart_page,#woocommerce_eh_paypal_express_express_on_checkout_page, #woocommerce_eh_paypal_express_billing_address').closest( 'tr' );
                            var eh_paypal_express_policy            = jQuery('#woocommerce_eh_paypal_express_policy_notes').closest('tr');
                            $( '#woocommerce_eh_paypal_express_environment' ).change(function(){
                                if ( 'live' === $( this ).val() ) {
                                        $( eh_paypal_express_live  ).show();
                                        $( eh_paypal_express_sandbox ).hide();
                                        $( '#environment_alert_desc').html('Obtain your <a target=\'_blank\' href=\'https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run\'> Live</a> API credentials from PayPal account.');
                                } else {
                                        $( eh_paypal_express_live ).hide();
                                        $( eh_paypal_express_sandbox ).show();
                                        $( '#environment_alert_desc').html('Obtain your <a href=\'https://developer.paypal.com/developer/accounts/\'> Sandbox</a> API credentials from PayPal developer account.');
                                    }
                            }).change();
                            $( '#woocommerce_eh_paypal_express_gateway_enabled' ).change(function(){
                                if ( $( this ).is( ':checked' ) ) {
                                        $( eh_paypal_express_regular  ).show();                    
                                } else {
                                        $( eh_paypal_express_regular ).hide();
                                    }
                            }).change();
                            // $( '#woocommerce_eh_paypal_express_express_enabled' ).change(function(){
                            //     if ( $( this ).is( ':checked' ) ) {
                            //             $( eh_paypal_express_express  ).show();
                            //     } else {
                            //             $( eh_paypal_express_express ).hide();
                            //         }
                            // }).change();
                            $( '#woocommerce_eh_paypal_express_skip_review' ).change(function(){
                                if ( $( this ).is( ':checked' ) ) {
                                        $( eh_paypal_express_policy  ).hide();
                                } else {
                                        $( eh_paypal_express_policy ).show();
                                    }
                            }).change();
                        });
                    ");
        parent::admin_options();
    }

    public function payment_scripts() {
        if (is_checkout()) {
            wp_register_style('eh-express-style', EH_PAYPAL_MAIN_URL . 'assets/css/eh-express-style.css');
            wp_enqueue_style('eh-express-style');
            wp_register_script('eh-express-js', EH_PAYPAL_MAIN_URL . 'assets/js/eh-express-script.js');
            wp_enqueue_script('eh-express-js');
            wp_localize_script('eh-express-js','eh_express_checkout_params', array('page_name' => 'checkout'));
        }
    }
    public function admin_scripts(){

        wp_register_style('eh-admin-style', EH_PAYPAL_MAIN_URL . 'assets/css/eh-admin-style.css',array(),EH_PAYPAL_VERSION);
        wp_enqueue_style('eh-admin-style');
        wp_register_script('eh-admin-script', EH_PAYPAL_MAIN_URL . 'assets/js/eh-admin-script.js',array(),EH_PAYPAL_VERSION);
        wp_enqueue_script('eh-admin-script');
    }

    public function perform_api_request_paypal() {
        $checkout_post = WC()->session->post_data;        
        
        if(empty($_GET)) {         // #5015   https://mozilor.atlassian.net/browse/PECPGFW-45   $_GET has empty data on finish_express 
            $parts = parse_url($_SERVER['REQUEST_URI']);
            $query =array();
            parse_str($parts['query'], $query);
            foreach ($query as $key => $value) {
                $_GET[$key] = $value;
            }
        }

        if (!isset($_GET['p']) || !isset($_GET['c'])) {
            $string1 = __( 'Oops !','express-checkout-paypal-payment-gateway-for-woocommerce');
            $string2 = __('Page Not Found','express-checkout-paypal-payment-gateway-for-woocommerce');
            $string3 = __('Back','express-checkout-paypal-payment-gateway-for-woocommerce');
            wp_die(sprintf('<center><h1>%s</h1><h4><b>404</b> %s</h4><br><a href="%s">%s</a></center>',$string1,$string2,wc_get_cart_url(),$string3));
        }
        //sets session variable if order is processed from order pay page and sets '$this->skip_review' to true since disabled case is not supported for order pay page
        if(isset($_GET['pay_for_order'])){
            WC()->session->pay_for_order = array('pay_for_order' => 'true');
        }
        if(isset(WC()->session->pay_for_order['pay_for_order'])){
            $this->skip_review = true;
        }

        if ( ! isset(WC()->session->pay_for_order['pay_for_order']) && (WC()->cart->get_cart_contents_count() === 0)) {
            $string1 = __( 'Oops !','express-checkout-paypal-payment-gateway-for-woocommerce');
            $string2 = __('Your Cart is Empty','express-checkout-paypal-payment-gateway-for-woocommerce');
            $string3 = __('Back','express-checkout-paypal-payment-gateway-for-woocommerce');
            wp_die(sprintf('<center><h1>%s</h1><h4>%s</h4><a href="%s">%s</a></center>',$string1,$string2,wc_get_cart_url(),$string3));
        }

        //checks shipping needed if skip review enabled
        if ( ! isset($_GET['pay_for_order']) && ($this->skip_review)) {
            //checks if shipping is needed, if needed and no method is chosen, page is redirected to cart page, For Virtual product no need of shipping method
            if ( (WC()->cart->needs_shipping()) ) {
                
                $chosen_methods = WC()->session->get('chosen_shipping_methods');
                if(empty($chosen_methods[0])){
                    wc_add_notice(__('No shipping method has been selected.', 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
                    wp_redirect(wc_get_cart_url());
                    die;
                }
            }
        }

        $action_code = sanitize_text_field( $_GET['c'] );
        $page_referer = esc_url_raw( $_GET['p'] );
        switch ($action_code) {
            case 'express_start':
            case 'credit_start' :

                try {
                    //creates new order if payment processing not from pay for order page
                    if(! isset($_GET['pay_for_order'])){ 
                      //creates user account if guest user checks create account checkbox
                       $this->create_account($checkout_post);
                   
                        
                        WC()->session->chosen_payment_method = $this->id;
                        if ((WC()->version < '2.7.0')) {
                            $order_id = WC()->checkout()->create_order();
                        } else {
                            $data = array(
                                "payment_method" => WC()->session->chosen_payment_method
                            );
                            if(isset($checkout_post)){ // to store custom field datas if checkout from checkout page

                                $order_id = WC()->checkout()->create_order($checkout_post);
                            }else{  // to store custom field datas if checkout from cart page
                                $order_id = WC()->checkout()->create_order($data);
                            }
                        }
                        if (is_wp_error($order_id)) {
                            throw new Exception($order_id->get_error_message());
                        }
                        $order = wc_get_order($order_id);
                        $set_address = $this->set_address_to_order($order);
                        
                        
                        //adding shipping details to order when order is created
                        if ( (WC()->cart->needs_shipping()) ) {
                            if( !$order->get_item_count('shipping') ) { // count is 0
                                $chosen_methods = WC()->session->get('chosen_shipping_methods');
                                $shipping_for_package_0 = WC()->session->get('shipping_for_package_0');

                                if (!empty($chosen_methods)) {
                                    if (isset($shipping_for_package_0['rates'][$chosen_methods[0]])) {
                                        $chosen_method = $shipping_for_package_0['rates'][$chosen_methods[0]];
                                        $item = new WC_Order_Item_Shipping();
                                        $item->set_props(array(
                                            'method_title' => $chosen_method->get_label(),
                                            'method_id' => $chosen_method->get_id(),
                                            'total' => wc_format_decimal($chosen_method->get_cost()),
                                            'taxes' => array(
                                                'total' => $chosen_method->taxes,
                                            ),
                                        ));
                                        foreach ($chosen_method->get_meta_data() as $key => $value) {
                                            $item->add_meta_data($key, $value, true);
                                        }
                                        $order->add_item($item);
                                        $order->save();
                                        
                                    }
                                }
                            } 
                        }
                        // $order->calculate_totals();
                    }else{
                        $order_id  = intval( $_GET['order_id'] );
                        $order     = wc_get_order($order_id);
                    }

                    if(isset($_GET['express'])){ //check if request is from standard paypal or using express button
                        $express = sanitize_text_field( $_GET['express'] );
                    }else{
                        $express = 'true';
                    }

                    $request_process = new Eh_PE_Process_Request();
                    $request_params = $this->new_request()->make_request_params
                            (
                            array
                                (
                                'method' => 'SetExpressCheckout',
                                'return_url' => add_query_arg(array('express' => $express, 'p'=> $page_referer), eh_paypal_express_run()->hook_include->make_express_url('express_details')),
                                'cancel_url' => add_query_arg('cancel_express_checkout', 'cancel', esc_url_raw( $order->get_cancel_order_url_raw($page_referer))), //cancel the order when customer clicks cancel and return from paypal page
                                'address_override' => $this->paypal_allow_override,
                                'credit' => ($action_code === 'credit_start') ? true : false,
                                'landing_page' => ( 'login' === $this->landing_page ) ? 'Login' : 'Billing',
                                'business_name' => $this->business_name,
                                'banner' => filter_var( $this->checkout_banner, FILTER_VALIDATE_URL ) ? $this->checkout_banner : wp_get_attachment_image_url( $this->checkout_banner, 'eh_header_image_size' ),
                                'logo' => filter_var( $this->checkout_logo, FILTER_VALIDATE_URL ) ? $this->checkout_logo : wp_get_attachment_image_url( $this->checkout_logo, 'eh_logo_image_size' ),
                                // 'customerservicenumber' => $this->customer_service,
                                // 'notify_url' => ($this->ipn_url === '') ? '' : $this->ipn_url,
                                'instant_payment' => true,
                                'localecode' => $this->store_locale(($this->paypal_locale) ? get_locale() : false),
                                'order_id'=> $order_id,
                                'pay_for_order'=> isset($_GET['pay_for_order']) ? $_GET['pay_for_order'] : false,
                            )
                    );
                    $response = $request_process->process_request($request_params, $this->nvp_url);
                    Eh_PayPal_Log::log_update($response,'Response on Express Start/Credit Start');
                    if (isset($response['TOKEN']) && !empty($response['TOKEN'])) {
                        wp_redirect($this->make_redirect_url($response['TOKEN']));
                    } else {
                        if(isset($response['L_LONGMESSAGE0'])){
                            wc_add_notice(__($response['L_ERRORCODE0'].' error - '.$response['L_LONGMESSAGE0'], 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
                        }elseif(isset($response['http_request_failed'])){
                            wc_add_notice(__($response['http_request_failed'][0], 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
                        }else{
                            wc_add_notice(__('An error occured.Please refresh and try again', 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
                        }
                        wp_redirect(wc_get_cart_url());
                        $this->order_completed_status = FALSE;
                    }
                } catch (Exception $e) {
                    Eh_PayPal_Log::log_update($e,'catch Exception on Express Start/Credit Start');
                    wc_add_notice(__('Redirect to PayPal failed. Please try again later.', 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
                    wp_redirect(wc_get_cart_url());
                }
                exit;
            case 'express_details':
                if (!isset($_GET['token'])) {
                    return;
                }
                $token = esc_attr($_GET['token']);
                try {
                    
                    $request_process = new Eh_PE_Process_Request();
                    $request_params = $this->new_request()->get_checkout_details
                            (
                            array
                                (
                                'METHOD' => 'GetExpressCheckoutDetails',
                                'TOKEN' => $token
                            )
                    );
                    $response = $request_process->process_request($request_params, $this->nvp_url);
                    Eh_PayPal_Log::log_update($response,'Response on Express Details');
                    if ($response['ACK'] == 'Success') {
                        $order_id = $response['PAYMENTREQUESTINFO_0_PAYMENTREQUESTID'];
                        WC()->session->eh_pe_checkout = array(
                            'token' => $token,
                            'shipping' => $this->shipping_parse($response),
                            'order_note' => isset($response['PAYMENTREQUEST_0_NOTETEXT']) ? $response['PAYMENTREQUEST_0_NOTETEXT'] : '',
                            'payer_id' => isset($response['PAYERID']) ? $response['PAYERID'] : '',
                            'order_id' => $order_id,
                        );
                        WC()->session->shiptoname = isset($response['SHIPTONAME']) ? $response['SHIPTONAME'] : $response['FIRSTNAME'] . ' ' . $response['LASTNAME'];
                        WC()->session->payeremail = $response['EMAIL'];
                        WC()->session->chosen_payment_method = get_class($this);
                        wc_clear_notices();
                    } else {
                        wc_add_notice(__('An error occurred, We were unable to process your order, please try again.', 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
                        wp_redirect(wc_get_cart_url());
                        exit;
                    }
                    $order = wc_get_order($order_id);

                    //user's billing address is set to address from paypal response if payment processed by express button
                    $is_express = sanitize_text_field( $_GET['express'] );

                    if(!empty($is_express) && $is_express == 'true'){
                        $order->set_address(WC()->session->eh_pe_checkout['shipping'], 'billing');
                    }
                    $order->set_address(WC()->session->eh_pe_checkout['shipping'], 'shipping');
                    $order->set_payment_method(WC()->session->chosen_payment_method);

                    $billing_phone = isset($response['PHONENUM']) ? $response['PHONENUM'] : WC()->session->post_data['billing_phone'];
                    $billing_phone = isset($response['SHIPTOPHONENUM']) ? $response['SHIPTOPHONENUM'] : $billing_phone; 
                    if (!empty($billing_phone)) {
                        update_post_meta($order_id, '_billing_phone', $billing_phone);
                    }
                                              
                    /* Adding shipping method and cost to the order*/
                    /* // Code for refering how to add shipping costs
                    $shipping_taxes = WC_Tax::calc_shipping_tax( '10', WC_Tax::get_shipping_tax_rates() );
                    $rate   = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', $shipping_taxes, 'flat_rate' );
                    $item   = new WC_Order_Item_Shipping();
                    $item->set_props( array(
                        'method_title' => $rate->label,
                        'method_id'    => $rate->id,
                        'total'        => wc_format_decimal( $rate->cost ),
                        'taxes'        => $rate->taxes,
                    ) );
                    foreach ( $rate->get_meta_data() as $key => $value ) {
                        $item->add_meta_data( $key, $value, true );
                    }
                    $order->add_item( $item );
                    $order->calculate_totals();   */
                    
                   
                    if ($this->skip_review) {

                        if(is_user_logged_in()){

                            update_post_meta($order_id, '_customer_user', get_current_user_id());
                        }

                        $order_comments = isset(WC()->session->post_data['order_comments']) ? WC()->session->post_data['order_comments'] : '';
                        if (!empty($order_comments)) {
                            update_post_meta($order_id, 'order_comments', WC()->session->post_data['order_comments']);
                            $my_post = array(
                                'ID' => $order_id,
                                'post_excerpt' => WC()->session->post_data['order_comments'],
                            );
                            wp_update_post($my_post);
                        }

                        $checkout_post = WC()->session->post_data;
                        $_POST =  $checkout_post;

                        do_action('woocommerce_checkout_order_processed', $order_id, $checkout_post, $order);
                        $this->process_payment($order_id);
                    } else {
                        WC()->session->eh_pe_set= array('skip_review_disabled' => 'true'); //sets session variable for processing payment if skip review is disabled
                        wp_redirect(wc_get_checkout_url());
                    }
                } catch (Exception $e) {
                    Eh_PayPal_Log::log_update($e,'catch Exception on Express Details');
                    wc_add_notice(__('Redirect to PayPal failed. Please try again later.','express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
                    wp_redirect(wc_get_cart_url());
                    $this->order_completed_status = false;
                }
                exit;
            case 'cancel_express':
                $this->cancel_order();
                exit;
            case 'finish_express':

                if (! $this->skip_review) {
                    //checks if shipping is needed, if needed and no method is chosen, page is redirected to checkout page
                    if ( (WC()->cart->needs_shipping()) ) {
                        
                        $chosen_methods = WC()->session->get('chosen_shipping_methods');
                        if(empty($chosen_methods[0])){
                            wc_add_notice(__('No shipping method has been selected.', 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
                            wp_redirect(wc_get_checkout_url());
                            die;
                        }
                    }
                }
                if (!isset($_GET['order_id'])) {
                    $string1 = __( 'Oops !','express-checkout-paypal-payment-gateway-for-woocommerce');
                    $string2 = __('Page Not Found','express-checkout-paypal-payment-gateway-for-woocommerce');
                    $string3 = __('Back','express-checkout-paypal-payment-gateway-for-woocommerce');
                    wp_die(sprintf('<center><h1>%s</h1><h4><b>404</b>%s</h4><br><a href="%s">%s</a></center>',$string1,$string2,wc_get_checkout_url(),$string3));
                }
                try {
                    
                    $order_id = intval( $_GET['order_id'] );
                    $order = wc_get_order($order_id);

                    do_action('eh_paypal_on_start_finish_express',$order,$this->skip_review);

                    //solution for shipping method change not reflecting in order details when skip review is disabled
                    foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
                       
                        $shipping_item_data = $shipping_item_obj->get_data();
                    
                        $shipping_data_id = $shipping_item_data['id'];
                        $shipping_data_method_id = $shipping_item_data['method_id'];
                       
                    }
                    $chosen_methods = WC()->session->get('chosen_shipping_methods');
                    $method = explode(":", $chosen_methods[0]);
                    $method = $method[0];
                    if(! $this->skip_review){
                        
                        $order->remove_item($shipping_data_id);
                  
                        if ( (WC()->cart->needs_shipping()) ) {
                            if( !$order->get_item_count('shipping') ) { // count is 0
                                $chosen_methods = WC()->session->get('chosen_shipping_methods');
                                $shipping_for_package_0 = WC()->session->get('shipping_for_package_0');

                                if (!empty($chosen_methods)) {
                                    if (isset($shipping_for_package_0['rates'][$chosen_methods[0]])) {
                                        $chosen_method = $shipping_for_package_0['rates'][$chosen_methods[0]];
                                        $item = new WC_Order_Item_Shipping();
                                        $item->set_props(array(
                                            'method_title' => $chosen_method->get_label(),
                                            'method_id' => $chosen_method->get_id(),
                                            'total' => wc_format_decimal($chosen_method->get_cost()),
                                            'taxes' => array(
                                                'total' => $chosen_method->taxes,
                                            ),
                                        ));
                                        foreach ($chosen_method->get_meta_data() as $key => $value) {
                                            $item->add_meta_data($key, $value, true);
                                        }
                                        $order->add_item($item);
                                        $order->set_shipping_total(wc_format_decimal($chosen_method->get_cost()));
                                        $order->update_taxes();
                                        $order->set_total( WC()->cart->get_total( 'edit' ) );
                                        $order->save();
                                    }
                                }
                            } 
                        }
                        // $order->calculate_totals();
                    }

                    $order->set_payment_method($this);
                    $request_process = new Eh_PE_Process_Request();
                    $request_params = $this->new_request()->finish_request_params
                            (
                            array
                        (
                        'method' => 'DoExpressCheckoutPayment',
                        'token' => WC()->session->eh_pe_checkout['token'],
                        'payer_id' => WC()->session->eh_pe_checkout['payer_id'],
                        'button' => 'WebToffee PE Checkout',
                        'instant_payment' => true,
                        // 'invoice_prefix' => apply_filters('eh_paypal_invoice_prefix','EH_'),
                        'invoice_prefix' => (isset($this->invoice_prefix)) ? $this->invoice_prefix : apply_filters('eh_paypal_invoice_prefix','EH_'),
                            ), $order
                    );
                    $response = $request_process->process_request($request_params, $this->nvp_url);
                    Eh_PayPal_Log::log_update($response,'Response on Finish Express');
                    if ($response['ACK'] == 'Success' || $response['ACK'] == 'SuccessWithWarning') {

                        //update edited address details to order from order review page
                        if(! $this->skip_review){
                            $data = WC()->session->post_data;
                            //creates user account if guest user checks create account checkbox from order review page
                            $this->create_account($data);
                            
                            //adds user id to order if guest user creates account and get logged in
                            if(is_user_logged_in()){

                                update_post_meta($order_id, '_customer_user', get_current_user_id());
                            }
                            $this->set_address_to_order($order);
                        }
                       
                        unset(WC()->session->pay_for_order);

                        $p_status = $response['PAYMENTINFO_0_PAYMENTSTATUS'];
                        $p_type = $response['PAYMENTINFO_0_TRANSACTIONTYPE'];
                        $p_id = $response['PAYMENTINFO_0_TRANSACTIONID'];
                        $p_reason = $response['PAYMENTINFO_0_PENDINGREASON'];
                        $update = array
                            (
                            'status' => 'Sale',
                            'trans_id' => $p_id
                        );
                        $p_time = str_replace("T", " ", str_replace("Z", " ", $response['PAYMENTINFO_0_ORDERTIME']));
                        switch (strtolower($p_status)) {
                            case 'completed':
                                if (in_array($p_type, array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money'))) {
                                    $order->add_order_note(__('Payment Status : ' . $p_status . '<br>[ ' . $p_time . ' ] <br>Source : ' . $p_type . '.<br>Transaction ID : ' . $p_id, 'express-checkout-paypal-payment-gateway-for-woocommerce'));
                                    $order->payment_complete($p_id);
                                    add_post_meta($order_id, '_eh_pe_details', $update);
                                }
                                break;
                            case 'pending':
                                if (in_array($p_type, array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money'))) {
                                    switch ($p_reason) {
                                        case 'address':
                                            $reason = __('Address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                        case 'authorization':
                                            $reason = __('Authorization: The payment is pending because it has been authorized but not settled. You must capture the funds first.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                        case 'echeck':
                                            $reason = __('eCheck: The payment is pending because it was made by an eCheck that has not yet cleared.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                        case 'intl':
                                            $reason = __('intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                        case 'multicurrency':
                                        case 'multi-currency':
                                            $reason = __('Multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                        case 'order':
                                            $reason = __('Order: The payment is pending because it is part of an order that has been authorized but not settled.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                        case 'paymentreview':
                                            $reason = __('Payment Review: The payment is pending while it is being reviewed by PayPal for risk.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                        case 'unilateral':
                                            $reason = __('Unilateral: The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                        case 'verify':
                                            $reason = __('Verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                        case 'other':
                                            $reason = __('Other: For more information, contact PayPal customer service.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                        case 'none':
                                        default:
                                            $reason = __('No pending reason provided.', 'express-checkout-paypal-payment-gateway-for-woocommerce');
                                            break;
                                    }
                                    $order->update_status('on-hold');
                                    add_post_meta($order_id, '_eh_pe_details', $update);
                                    $order->add_order_note(__('Payment Status : ' . $p_status . '<br>[ ' . $p_time . ' ] <br>Source : ' . $p_type . '.<br>Transaction ID : ' . $p_id . '.<br>Reason : ' . $reason, 'express-checkout-paypal-payment-gateway-for-woocommerce'));
                                    if ((WC()->version < '2.7.0')) {
                                        $order->reduce_order_stock();
                                    } else {
                                        wc_reduce_stock_levels($order_id);
                                    }
                                    WC()->cart->empty_cart();
                                }
                                break;
                            case 'denied' :
                            case 'expired' :
                            case 'failed' :
                            case 'voided' :
                                $order->update_status('cancelled');
                                $order->add_order_note(__('Payment Status : ' . $p_status . ' [ ' . $p_time . ' ] <br>Source : ' . $p_type . '.<br>Transaction ID : ' . $p_id . '.<br>Reason : ' . $p_reason, 'express-checkout-paypal-payment-gateway-for-woocommerce'));
                                break;
                            default:
                                break;
                        }

                        wc_clear_notices();
                        wp_redirect($this->get_return_url($order));

                        //fix for new order email not sending for when Germanized for WooCommerce plugin is active PECPGFW-148
                        $order = wc_get_order($order_id);
                        if(is_plugin_active('woocommerce-germanized/woocommerce-germanized.php')){
                            WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order->get_id(), $order ); 
                        }

                    } else {
                        wc_add_notice(__('An error occurred, We were unable to process your order, please try again.', 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
                        wp_redirect(wc_get_checkout_url());
                    }
                } catch (Exception $e) {
                    Eh_PayPal_Log::log_update($e,'catch Exception on Finish Express');
                    wc_add_notice(__('Redirect to PayPal failed. Please try again later.', 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
                    wp_redirect(wc_get_checkout_url());
                }
            exit;
        }
    }

    public function cancel_order() {
        if (isset(WC()->session->eh_pe_checkout)) {
            unset(WC()->session->eh_pe_checkout);
            if(isset(WC()->session->eh_pe_billing)){
                unset(WC()->session->eh_pe_billing);
            }
            wc_add_notice(__('You have cancelled PayPal Express Checkout. Please try to process your order again.', 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'notice');
            wp_redirect(wc_get_cart_url());
            exit;
        }
    }

    public function hide_checkout_fields_on_review($checkout_fields) {
        $checkout_shipping = isset(WC()->session->eh_pe_checkout['shipping']) ? WC()->session->eh_pe_checkout['shipping'] : '';
        if (isset(WC()->session->eh_pe_checkout) && is_array($checkout_shipping) && !empty($checkout_shipping)) {
            foreach ($checkout_shipping as $key => $value) {
                if (isset($checkout_fields['billing']) && isset($checkout_fields['billing']['billing_' . $key])) {
                    $required = isset($checkout_fields['billing']['billing_' . $key]['required']) && $checkout_fields['billing']['billing_' . $key]['required'];
                    if (!$required || $required && $value) {
                        $checkout_fields['billing']['billing_' . $key]['class'][] = 'eh_pe_checkout';
                        $checkout_fields['billing']['billing_' . $key]['class'][] = 'eh_pe_checkout_fields_hide';
                    }
                }
            }
            $checkout_fields['billing']['billing_phone']['class'][] = 'eh_pe_checkout_fields_fill';
        }
        return $checkout_fields;
    }

    public function fill_billing_details_on_review() {

        if($this->get_option('send_shipping') === 'yes'){ // if need shipping option enabled, store billing details instead of shipping
            $checkout_shipping = WC()->session->eh_pe_billing;
            if( empty($checkout_shipping['first_name'] ) ){
                
                $checkout_shipping = isset(WC()->session->eh_pe_checkout['shipping']) ? WC()->session->eh_pe_checkout['shipping'] : '';
            }
        }else{
            $checkout_shipping = isset(WC()->session->eh_pe_checkout['shipping']) ? WC()->session->eh_pe_checkout['shipping'] : '';
        }

        if (isset(WC()->session->eh_pe_checkout) && !empty($checkout_shipping)) {

            apply_filters('eh_paypal_post_value_update_on_review' ,$_POST, $checkout_shipping ); // PECPGFW-137 $_POST data are empty on review page

            echo
            '
                        <div class="eh_pe_address">
                            <span class="edit_eh_pe_address" data-type="billing">
                                '.__( 'Edit Details','express-checkout-paypal-payment-gateway-for-woocommerce').'
                            </span>
                            <div class="eh_pe_address_text">
                                <table class="eh_pe_address_table">
                                    <tr>
                                        <td>
                                            <span class="heading_address_field">'.__( 'First Name','express-checkout-paypal-payment-gateway-for-woocommerce').'</span>
                                            <br>
                                            ' . WC()->checkout->get_value('billing_first_name') . '
                                        </td>
                                        <td>
                                            <span class="heading_address_field">'.__( 'Last Name','express-checkout-paypal-payment-gateway-for-woocommerce').'</span>
                                            <br>
                                            ' . WC()->checkout->get_value('billing_last_name') . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="heading_address_field">'.__( 'Company Name','express-checkout-paypal-payment-gateway-for-woocommerce').'</span>
                                            <br>
                                            ' . WC()->checkout->get_value('billing_company') . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="heading_address_field">'.__( 'Email Address','express-checkout-paypal-payment-gateway-for-woocommerce').'</span>
                                            <br>
                                            ' . WC()->checkout->get_value('billing_email') . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="heading_address_field">'.__( 'Country','express-checkout-paypal-payment-gateway-for-woocommerce').'</span>
                                            <br>
                                            ' . WC()->checkout->get_value('billing_country') . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="heading_address_field">'.__( 'Address','express-checkout-paypal-payment-gateway-for-woocommerce').'</span>
                                            <br>
                                            ' . WC()->checkout->get_value('billing_address_1') . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="heading_address_field"></span>
                                            ' . WC()->checkout->get_value('billing_address_2') . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="heading_address_field">'.__( 'Town / City','express-checkout-paypal-payment-gateway-for-woocommerce').'</span>
                                            <br>
                                            ' . WC()->checkout->get_value('billing_city') . '
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="heading_address_field">'.__( 'State','express-checkout-paypal-payment-gateway-for-woocommerce').'</span>
                                            <br>
                                            ' . WC()->checkout->get_value('billing_state') . '
                                        </td>
                                        <td>
                                            <span class="heading_address_field">'.__( 'Postcode / ZIP','express-checkout-paypal-payment-gateway-for-woocommerce').'</span>
                                            <br>
                                            ' . WC()->checkout->get_value('billing_postcode') . '
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    ';
        } else {
            return;
        }
    }

    public function fill_checkout_fields_on_review() {
       
        if($this->get_option('send_shipping') === 'yes'){  // if need shipping option enabled, store billing details instead of shipping
            $checkout_billing = WC()->session->eh_pe_billing;
          
            if( empty($checkout_billing['first_name'] ) ){
                
                $checkout_billing = isset(WC()->session->eh_pe_checkout) ? WC()->session->eh_pe_checkout['shipping'] : array();
            }

            $checkout_shipping = isset(WC()->session->eh_pe_checkout) ? WC()->session->eh_pe_checkout['shipping'] : array();
          
            if (isset(WC()->session->eh_pe_checkout) && (is_array($checkout_billing) && !empty($checkout_billing))) {
                foreach ($checkout_billing as $key => $value) {
                    if ($value) {
                        $_POST['billing_' . $key] = $value;
                    }
                }

                if (is_array($checkout_shipping) && !empty($checkout_shipping)) {
                    foreach ($checkout_shipping as $key => $value) {
                        if ($value) {
                            $_POST['shipping_' . $key] = $value;
                        }
                    }
                }
                

                $_POST['billing_email'] = WC()->session->payeremail;
                $order_note = WC()->session->eh_pe_checkout['order_note'];
                if (!empty($order_note)) {
                    $_POST['order_comments'] = WC()->session->eh_pe_checkout['order_note'];
                }
                $this->chosen = true;
            } else {
                return;
            }
        }else{
            $checkout_shipping = isset(WC()->session->eh_pe_checkout['shipping']) ? WC()->session->eh_pe_checkout['shipping'] : '';
            if (isset(WC()->session->eh_pe_checkout) || (is_array($checkout_shipping) && !empty($checkout_shipping))) {
                foreach ($checkout_shipping as $key => $value) {
                    if ($value) {
                        $_POST['billing_' . $key] = $value;
                        $_POST['shipping_' . $key] = $value;
                    }
                }
                $_POST['billing_email'] = WC()->session->payeremail;
                $order_note = WC()->session->eh_pe_checkout['order_note'];
                if (!empty($order_note)) {
                    $_POST['order_comments'] = WC()->session->eh_pe_checkout['order_note'];
                }
                $this->chosen = true;
            } else {
                return;
            }
        }
       
    }

    public function gateways_hide_on_review($gateways) {
        if (isset(WC()->session->eh_pe_checkout)) {
            foreach ($gateways as $id => $name) {
                if ($id !== $this->id) {
                    unset($gateways[$id]);
                }
            }
        }
        return $gateways;
    }

    public function add_policy_notes() {
        if (isset(WC()->session->eh_pe_checkout) && !empty($this->policy_notes)) {
            echo
            '
                <div class="eh_paypal_express_seller_policy">
                    <div class="form-row eh_paypal_express_seller_policy_content">
                        <h3>'.__( 'Seller Policy','express-checkout-paypal-payment-gateway-for-woocommerce').'</h3>
                        <div>' . $this->policy_notes . '</div>
                    </div>
                </div>
            '
            ;
        }
    }

    public function add_cancel_order_elements() {
        if (isset(WC()->session->eh_pe_checkout)) {
            echo '<script>
                    jQuery(function() 
                    {
                        if(jQuery(".eh_pe_address").length)
                        {
                            jQuery(".payment_methods").prop("hidden","hidden");
                        }
                    });
                  </script>';
            $page = get_page_link();
            printf('<a href="' . add_query_arg('p', $page, eh_paypal_express_run()->hook_include->make_express_url('cancel_express')) . '" class="button alt" style="background-color: crimson;">'.__( 'Cancel Order','express-checkout-paypal-payment-gateway-for-woocommerce').'</a>');
        }
    }

    public function process_express_checkout($data = array(), $errors = array()) {
        if (0 != wc_notice_count('error')) {
            return;
        }
        if (isset($errors->errors) && !empty($errors->errors)) {

            return;
        }
        if (isset($_POST['payment_method']) && 'eh_paypal_express' === $_POST['payment_method']) {
            
            $post_value = (array) wp_unslash( wc_clean( $_POST ) );

            $array = array_merge($post_value,$data); 
            WC()->session->post_data = $array; //merged value of $_POST and validated data is set as session data 
            
            $_POST = WC()->session->post_data ;
            if (!isset(WC()->session->eh_pe_checkout)) {
                $page = wc_get_checkout_url();
                $result = array(
                    'result' => 'success',
                    'redirect' => add_query_arg(array('express' => 'false', 'p'=> $page), eh_paypal_express_run()->hook_include->make_express_url('express_start')),
                );
                if (is_ajax()) {
                    wp_send_json($result);
                } else {
                    wp_redirect($result['redirect']);
                }
            }
        }
    }

    public function process_payment($order_id) {

        //if order processed from pay for order page set action for 'SetExpressCheckout' method call
        if(is_wc_endpoint_url( 'order-pay' )){
            $page= get_page_link();
            $return_url = add_query_arg('pay_for_order', $_GET['pay_for_order'], add_query_arg('order_id', $order_id, add_query_arg('p', $page, eh_paypal_express_run()->hook_include->make_express_url('express_start'))));
            $result = array(
                'result' => 'success',
                'redirect' => $return_url,
            );
            wp_redirect($result['redirect']);
        }
        elseif (isset(WC()->session->eh_pe_checkout)) {

            //post data is saved if skip review is disabled to get edited address details
            if(! $this->skip_review){
                WC()->session->post_data = (array) wp_unslash( wc_clean ( $_POST ));
            }

            $page = wc_get_checkout_url();
            $return_url = add_query_arg('order_id', $order_id, add_query_arg('p', $page, eh_paypal_express_run()->hook_include->make_express_url('finish_express')));
            $result = array(
                'result' => 'success',
                'redirect' => $return_url,
            );

            if (is_ajax()) {
                wp_send_json($result);
            } else {
                wp_redirect($result['redirect']);
            }
           
            
        } else {
            wc_add_notice(__('An error occurred, We were unable to process your order, please try again.', 'express-checkout-paypal-payment-gateway-for-woocommerce'), 'error');
            wp_redirect(wc_get_checkout_url());
        }
    }



    /*
    * sets address to order details when order is created
    */
    public function set_address_to_order($order){
         
        $data = WC()->session->post_data;
        $billing_details = array
        (
            'first_name' => empty( $data[ 'billing_first_name' ] ) ? (( WC()->version < '2.7.0' ) ? WC()->session->post_data['billing_first_name'] : WC()->customer->get_billing_first_name()) : wc_clean( $data[ 'billing_first_name' ] ),
            'last_name'  => empty( $data[ 'billing_last_name' ] )  ? (( WC()->version < '2.7.0' ) ? WC()->session->post_data['billing_last_name'] : WC()->customer->get_billing_last_name())   : wc_clean( $data[ 'billing_last_name' ] ),
            'email'      => empty( $data[ 'billing_email' ] )      ? (( WC()->version < '2.7.0' ) ? WC()->session->post_data['billing_email'] : WC()->customer->get_billing_email())           : wc_clean( $data[ 'billing_email' ] ),
            'phone'      => empty( $data[ 'billing_phone' ] )      ? (( WC()->version < '2.7.0' ) ? WC()->session->post_data['billing_phone'] : WC()->customer->get_billing_phone())           : wc_clean( $data[ 'billing_phone' ] ),
            'address_1'  => empty( $data[ 'billing_address_1' ] )  ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_address() : WC()->customer->get_billing_address())                     : wc_clean( $data[ 'billing_address_1' ] ),
            'address_2'  => empty( $data[ 'billing_address_2' ] )  ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_address_2() : WC()->customer->get_billing_address_2())                 : wc_clean( $data[ 'billing_address_2' ] ),
            'city'       => empty( $data[ 'billing_city' ] )       ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_city() : WC()->customer->get_billing_city())                           : wc_clean( $data[ 'billing_city' ] ),
            'postcode'   => empty( $data[ 'billing_postcode' ] )   ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_postcode() : WC()->customer->get_billing_postcode())                   : wc_clean( $data[ 'billing_postcode' ] ),
            'country'    => empty( $data[ 'billing_country' ] )    ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_country() : WC()->customer->get_billing_country())                     : wc_clean( $data[ 'billing_country' ] ),
            'state'      => empty( $data[ 'billing_state' ] )      ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_state() : WC()->customer->get_billing_state())                         : wc_clean( $data[ 'billing_state' ] ),
            'company'    => empty( $data[ 'billing_company' ] )  ? '' :  wc_clean( $data[ 'billing_company' ] ) ,
        );
        $shipping_details = array
        ( 
            'first_name' => empty( $data[ 'shipping_first_name' ] ) ? (( WC()->version < '2.7.0' ) ? WC()->session->post_data['shipping_first_name'] : WC()->customer->get_shipping_first_name()): wc_clean( $data[ 'shipping_first_name' ] ),
            'last_name'  => empty( $data[ 'shipping_last_name' ] )  ? (( WC()->version < '2.7.0' ) ? WC()->session->post_data['shipping_last_name'] : WC()->customer->get_shipping_last_name())  : wc_clean( $data[ 'shipping_last_name' ] ),
            'email'      => empty( $data[ 'billing_email' ] )       ? (( WC()->version < '2.7.0' ) ? WC()->session->post_data['billing_email'] : WC()->customer->get_billing_email())            : wc_clean( $data[ 'billing_email' ] ),
            'phone'      => empty( $data[ 'billing_phone' ] )       ? (( WC()->version < '2.7.0' ) ? WC()->session->post_data['billing_phone'] : WC()->customer->get_billing_phone())            : wc_clean( $data[ 'billing_phone' ] ),
            'address_1'  => empty( $data[ 'shipping_address_1' ] )  ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_address() : WC()->customer->get_shipping_address())                     : wc_clean( $data[ 'shipping_address_1' ] ),
            'address_2'  => empty( $data[ 'shipping_address_2' ] )  ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_address_2() : WC()->customer->get_shipping_address_2())                 : wc_clean( $data[ 'shipping_address_2' ] ),
            'city'       => empty( $data[ 'shipping_city' ] )       ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_city() : WC()->customer->get_shipping_city())                           : wc_clean( $data[ 'shipping_city' ] ),
            'postcode'   => empty( $data[ 'shipping_postcode' ] )   ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_postcode() : WC()->customer->get_shipping_postcode())                   : wc_clean( $data[ 'shipping_postcode' ] ),
            'country'    => empty( $data[ 'shipping_country' ] )    ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_country() : WC()->customer->get_shipping_country())                     : wc_clean( $data[ 'shipping_country' ] ),
            'state'      => empty( $data[ 'shipping_state' ] )      ? (( WC()->version < '2.7.0' ) ? WC()->customer->get_state() : WC()->customer->get_shipping_state())                         : wc_clean( $data[ 'shipping_state' ] ),
            'company'    => empty( $data[ 'shipping_company' ] )  ? '' :   wc_clean( $data[ 'shipping_company' ] ) ,
        );

        WC()->session->eh_pe_billing = $billing_details;
        $order->set_address($billing_details, 'billing');
       
        $order->set_address($shipping_details, 'shipping');
    }


    // create user if create an account is checked while procceed to payment
    public function create_account($checkout_post) {
       
        $_POST = WC()->session->post_data; // PECPGFW-76 , $_POST data are empty on 'pre_user_login' filter hook

        if ( $this->is_registration_needed($checkout_post) ) {

            if (!empty($checkout_post['billing_email'])) {

               
                $maybe_username = sanitize_user($checkout_post['billing_first_name']);
                $counter = 1;
                $user_name = $maybe_username;

                while (username_exists($user_name)) {
                    $user_name = $maybe_username . $counter;
                    $counter++;
                }
                $data = array(
                    'user_login' => $user_name,
                    'user_email' => $checkout_post['billing_email'],
                    'user_pass'  => (isset($checkout_post['account_password']) ? $checkout_post['account_password'] :''),
                );
               
               $userID = wc_create_new_customer($data['user_email'],$data['user_login'],$data['user_pass']);
                if ( is_wp_error( $userID ) ) {
                    wc_add_notice($userID->get_error_message(), 'error');
              
                    wp_redirect(wc_get_checkout_url());
                    exit;
               
                }

               wc_set_customer_auth_cookie( $userID );

               // As we are now logged in, checkout will need to refresh to show logged in data.
               WC()->session->set( 'reload_checkout', true );

               // Also, recalculate cart totals to reveal any role-based discounts that were unavailable before registering.
               WC()->cart->calculate_totals();

                if (!is_wp_error($userID)) {

                    update_user_meta($userID, 'billing_first_name', $checkout_post['billing_first_name']);
                    update_user_meta($userID, 'billing_last_name', $checkout_post['billing_last_name']);
                    update_user_meta($userID, 'billing_address_1', $checkout_post['billing_address_1']);
                    update_user_meta($userID, 'billing_state', $checkout_post['billing_state']);
                    update_user_meta($userID, 'billing_email', $checkout_post['billing_email']);
                    update_user_meta($userID, 'billing_postcode', $checkout_post['billing_postcode']);
                    update_user_meta($userID, 'billing_phone', $checkout_post['billing_phone']);
                    update_user_meta($userID, 'billing_country', $checkout_post['billing_country']);
                    update_user_meta($userID, 'billing_company', $checkout_post['billing_company']);

                    $user_data = wp_update_user(array('ID' => $userID, 'first_name' => $checkout_post['billing_first_name'], 'last_name' => $checkout_post['billing_last_name']));
                   
                
                } else {
                   return true;
                }
            }
        } else {
           return true;
        }
    }

    public function is_registration_needed($checkout_post) {
        
        if(isset($checkout_post['createaccount']) && ($checkout_post['createaccount'] == 1)){
            return true;            
        }
        if(isset($checkout_post['account_password']) && !empty($checkout_post['account_password'])){
            return true;            
        }
        if(! is_user_logged_in()){ 
            if('no' == get_option( 'woocommerce_enable_guest_checkout' )){ //create an account for guest users if 'Allow customers to place orders without an account' option is disabled 
                return true;            
            }
        }
        return false;
    }

    public function shipping_parse($response) {
        if ($this->paypal_allow_override) {
            $post_data = WC()->session->post_data;
        }

        $parts = explode(' ', ($response['SHIPTONAME'])); 
        $name_first = array_shift($parts);
        $name_last = array_pop($parts);
        $name_middle = trim(implode(' ', $parts));

        $shipping_details = array();
        if (isset($response['ADDRESSSTATUS']) && isset($response['FIRSTNAME'])) {
            $shipping_details = array
                (
                'first_name' => isset($response['SHIPTONAME']) ? $name_first : $post_data['billing_first_name']  ,
                'last_name' => isset($response['SHIPTONAME']) ? ( isset($name_middle) ? ($name_middle.' '.(isset($name_last) ? $name_last: ' ')) : ' ' ) : $post_data['billing_last_name'] ,
                'company' =>  (isset(WC()->session->post_data['shipping_company']) ? WC()->session->post_data['shipping_company'] : (isset($response['BUSINESS']) ? $response['BUSINESS'] : '')),
                'email' => ($this->paypal_allow_override ? $post_data['billing_email'] : (isset($response['EMAIL']) ? $response['EMAIL'] : '')),
                'phone' => isset($response['SHIPTOPHONENUM']) ? $response['SHIPTOPHONENUM'] : '',
                'address_1' => isset($response['SHIPTOSTREET']) ? $response['SHIPTOSTREET'] : '',
                'address_2' => isset($response['SHIPTOSTREET2']) ? $response['SHIPTOSTREET2'] : '',
                'city' => isset($response['SHIPTOCITY']) ? $response['SHIPTOCITY'] : '',
                'postcode' => isset($response['SHIPTOZIP']) ? $response['SHIPTOZIP'] : '',
                'country' => isset($response['SHIPTOCOUNTRYCODE']) ? $response['SHIPTOCOUNTRYCODE'] : '',
                'state' => $this->wc_get_state_code($response['SHIPTOCOUNTRYCODE'], $response['SHIPTOSTATE']),
            );

            $post_form_data = WC()->session->post_data;
           $shipping_details = apply_filters('eh_alter_paypal_response', $shipping_details, $post_form_data); 

        }

        return $shipping_details;
    }

    public function wc_get_state_code($country_code, $state) {
        if ($country_code !== 'US' && isset(WC()->countries->states[$country_code])) {
            $local_states = WC()->countries->states[$country_code];
            if (!empty($local_states) && in_array($state, $local_states)) {
                foreach ($local_states as $key => $val) {
                    if ($val === $state) {
                        return $key;
                    }
                }
            }
        }
        return $state;
    }

    public function new_request() {
        return new Eh_PE_Request_Built($this->api_username, $this->api_password, $this->api_signature);
    }

    public function make_redirect_url($token) {
        //if skip review is enabled, change paypal review page button text from 'continue' to 'pay Now'
        if($this->skip_review){
            $pair = array(
                'cmd' => '_express-checkout',
                'token' => $token,
                'useraction' => 'commit',
                
            );
        }else{
            $pair = array(
                'cmd' => '_express-checkout',
                'token' => $token,
                
            );
        }
        
        return add_query_arg($pair, $this->scr_url);
    }

    public function store_locale($locale) {
        $safe_locales = array(

        'da_DK',
		'de_DE',
		'en_AU',
		'en_GB',
		'en_US',
		'es_ES',
		'fr_CA',
		'fr_FR',
		'he_IL',
		'id_ID',
		'it_IT',
		'ja_JP',
		'nl_NL',
		'pl_PL',
		'pt_BR',
		'pt_PT',
		'ru_RU',
		'sv_SE',
		'th_TH',
		'tr_TR',
		'zh_CN',
		'zh_HK',
        'zh_TW',

        );
        if (!in_array($locale, $safe_locales)) {
            $locale = 'en_US';
        }
        return $locale;
    }

    public function file_size($bytes) {
        $result = 0;
        $bytes = floatval($bytes);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            ),
        );

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem["VALUE"]) {
                $result = $bytes / $arItem["VALUE"];
                $result = str_replace(".", ".", strval(round($result, 2))) . " " . $arItem["UNIT"];
                break;
            }
        }
        return $result;
    }

    public function get_icon() {
        $image_path = apply_filters('eh_paypal_checkout_icon_url',EH_PAYPAL_MAIN_URL ."assets/img/gateway_icon.svg");
        $icon = "<img src=\"$image_path\"/>";
        return $icon;
    }

}

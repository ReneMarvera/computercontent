<?php

if (!defined('ABSPATH')) {
    exit;
}
if (!defined('ABSPATH')) {
    exit;
}

$eh_paypal = get_option("woocommerce_eh_paypal_express_settings");

if(isset($eh_paypal['express_enabled'])){

    if(isset($eh_paypal['express_on_cart_page']) && ($eh_paypal['express_on_cart_page'] === 'yes')){
        $cart = 'cart';
        if(isset($eh_paypal['credit_checkout']) && ($eh_paypal['credit_checkout'] === 'yes')){
            $cart_cc = 'cart';
        }else{
            $cart_cc = '';
        }
    }else{
        $cart = ''; $cart_cc = '';
    }
    if(isset($eh_paypal['express_on_checkout_page']) && ($eh_paypal['express_on_checkout_page'] === 'yes')){
        $checkout = 'checkout';
        if(isset($eh_paypal['credit_checkout']) && ($eh_paypal['credit_checkout'] === 'yes')){
            $checkout_cc = 'checkout';
        }else{
            $checkout_cc = '';
        }
    }else{
        $checkout = ''; $checkout_cc = '';
    }
}else{
    $cart = 'cart'; $checkout = 'checkout'; 
    $cart_cc = 'cart'; $checkout_cc = 'checkout'; 
}
$file_size=(file_exists(wc_get_log_file_path('eh_paypal_express_log'))?$this->file_size(filesize(wc_get_log_file_path('eh_paypal_express_log'))):'');

return array(
    'paypal_prerequesties' => array(
        'type' => 'title',
        'description' => sprintf("<div class='eh_wt_info_div'><p><b>".__( 'Pre-requisites:','express-checkout-paypal-payment-gateway-for-woocommerce' )."</b></p><p> ".__( 'Requires a PayPal Business account linked with confirmed identity, email, and bank account.','express-checkout-paypal-payment-gateway-for-woocommerce' )."</p><p> ".__( 'To get the API credentials:','express-checkout-paypal-payment-gateway-for-woocommerce' )."</p><ul class='eh_wt_notice_bar_style'><li> ".__( 'Log into your PayPal business account.','express-checkout-paypal-payment-gateway-for-woocommerce' )." </li> <li>".__( 'Click Activity near the top of the page and select API Access.','express-checkout-paypal-payment-gateway-for-woocommerce' )."</li> <li>".__( 'Scroll to NVP/SOAP API Integration (Classic) and click Manage API Credentials.','express-checkout-paypal-payment-gateway-for-woocommerce' )."</li> <li>".__( 'Create keys if not done already. Else, copy the API Username, API Password, and Signature and paste it into the respective fields of the plugin.','express-checkout-paypal-payment-gateway-for-woocommerce' )."</li> </ul></div><p><a target='_blank' href='https://www.webtoffee.com/paypal-express-checkout-payment-gateway-woocommerce-user-guide/'>  ".__('Read documentation', 'express-checkout-paypal-payment-gateway-for-woocommerce')." </a></p>"),
    ),
    'credentials_title' => array(
        'title' => sprintf(__('PayPal Credentials', 'express-checkout-paypal-payment-gateway-for-woocommerce')),
        'type' => 'title',
        'description' => __('Select Live mode to accept payments and Sandbox mode to test payments.', 'express-checkout-paypal-payment-gateway-for-woocommerce') 
    ),
    'environment' => array(
        'title' => __('Environment', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'options' => array(
            'sandbox' => __('Sandbox mode', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
            'live' => __('Live mode', 'express-checkout-paypal-payment-gateway-for-woocommerce')
        ),
        'description' => sprintf(__('<div id="environment_alert_desc"></div>', 'express-checkout-paypal-payment-gateway-for-woocommerce')),
        'default' => 'sandbox',
        'desc_tip' => __('Choose Sandbox mode to test payment using test API keys. Switch to live mode to accept payments with PayPal using live API keys.', 'express-checkout-paypal-payment-gateway-for-woocommerce')
    ),
    'sandbox_username' => array(
        'title' => __('Sandbox API username', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'text',
        'default' => ''
    ),
    'sandbox_password' => array(
        'title' => __('Sandbox API password', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'password',
        'default' => ''
    ),
    'sandbox_signature' => array(
        'title' => __('Sandbox API signature', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'password',
        'default' => ''
    ),
    'live_username' => array(
        'title' => __('Live API username', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'text',
        'default' => ''
    ),
    'live_password' => array(
        'title' => __('Live API password', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'password',
        'default' => ''
    ),
    'live_signature' => array(
        'title' => __('Live API signature', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'password',
        'default' => ''
    ),
    'gateway_title' => array(
        'type' => 'title',
        'class'=> 'eh-css-class'
    ),
    'enabled' => array(
        'title' => __('PayPal Payment Gateway', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'label' => __('Enable', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'checkbox',
        'description' => __('Enable to have PayPal payment method on the checkout page.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => 'no',
        'desc_tip' => true
    ),
    'title' => array(
        'title' => __('Title', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'text',
        'description' => __('Input title for the payment gateway displayed at the checkout.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => __('PayPal', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => true
    ),
    'description' => array(
        'title' => __('Description', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'textarea',
        'css' => 'width:25em',
        'description' => __('Input description for the payment gateway displayed at the checkout.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => __('Secure payment via PayPal.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => true
    ),
   
    'express_title' => array(
        'title' => sprintf(__('PayPal Express Checkout Button', 'express-checkout-paypal-payment-gateway-for-woocommerce')),
        'type' => 'title',
        'description' => __('Add Express Checkout to your store for faster PayPal transactions.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'class'=> 'eh-css-class'
    ),
    
    'express_button_on_pages' => array(
        'title' => __('Show Express button on  <a  class="thickbox" href="'.EH_PAYPAL_MAIN_URL . 'assets/img/express_button_preview.png?TB_iframe=true&width=100&height=100"> <small> [Preview] </small> </a>', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'multiselect',
        'class' => 'chosen_select',
        'css' => 'width: 350px;',
        'desc_tip' => __('Displays PayPal Express button on chosen pages.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'options' => array(
            'cart' => 'Cart page',
            'checkout' => 'Checkout page',
        ),
        'default' => array(
            $cart,
            $checkout,
        )
    ),
    'credit_button_on_pages' => array(
        'title' => __('Show Express credit button on <a  class="thickbox" href="'.EH_PAYPAL_MAIN_URL . 'assets/img/credit_button_preview.png?TB_iframe=true&width=100&height=100"> <small> [Preview] </small> </a>', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'multiselect',
        'class' => 'chosen_select',
        'css' => 'width: 350px;',
        'desc_tip' => __('Displays a PayPal Credit button on selected pages. By using PayPal Credit, store owner will receive the payment upfront but customers can opt for financing and pay over time.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'options' => array(
            'cart' => 'Cart page',
            'checkout' => 'Checkout page',
        ),
        'default' => array(
            $cart_cc,
            $checkout_cc,
        ),
    ),
    'express_description' => array(
        'title' => __('Description', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'textarea',
        'css' => 'width:25em',
        'description' => __('Input description displayed above the PayPal Express button.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => __('Reduce the number of clicks with PayPal Express.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => true
    ),
    'button_size' => array(
        'title' => __('Button size', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'description' => __('Choose the size of the button as either small, medium or large.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => 'medium',
        'desc_tip' => true,
        'options' => array(
            'small' => __('Small', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
            'medium' => __('Medium', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
            'large' => __('Large', 'express-checkout-paypal-payment-gateway-for-woocommerce')
        )
    ),
  
    'abilities_title' => array(
        'title' => sprintf(__('Branding', 'express-checkout-paypal-payment-gateway-for-woocommerce')),
        'type' => 'title',
        'description' => sprintf(__('Set your brand identity at the PayPal end by giving a brand name, logo, banner etc. It will be visible for customers on the PayPal site on choosing to pay via PayPal.', 'express-checkout-paypal-payment-gateway-for-woocommerce')),
        'class'=> 'eh-css-class',
    ),
    'business_name' => array(
        'title' => __('Brand name', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'text',
        'description' => __('Input the name of your store that will appear on the PayPal end.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => __(get_bloginfo('name', 'display'), 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => true
    ),
   
    'landing_page' => array(
        'title' => __('Landing page', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'options' => array(
            'login' => __('Login', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
            'billing' => __('Billing', 'express-checkout-paypal-payment-gateway-for-woocommerce')
        ),
        'description' => sprintf(__('Customers will be redirected to the chosen page. By default, the billing page is taken.', 'express-checkout-paypal-payment-gateway-for-woocommerce')),
        'default' => 'billing',
        'desc_tip' => true
    ),
    'checkout_logo' => array(
        'title' => __('Logo (190 x 90)', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'image',
        'description' => __('Upload a company logo that will appear on the PayPal end. Image requires an SSL host.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => true,
    ),
    'checkout_banner' => array(
        'title' => __('Header (750 x 90)', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'image',
        'description' => __('Upload a header image that will appear on the PayPal end. Image requires an SSL host.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => true,
    ),
   
    'paypal_locale' => array(
        'title' => __('PayPal locale', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'checkbox',
        'label' => __('Use store locale', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => 'yes',
        'description' => __('Check to set the PayPal locale same as the store locale.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => true
    ),

    // 'payment_action' => array(
    //     'title' => __('Payment Action', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
    //     'type' => 'select',
    //     'class' => 'wc-enhanced-select',
    //     'options' => array(
    //         'sale' => __('Sale', 'express-checkout-paypal-payment-gateway-for-woocommerce')
    //     ),
    //     'description' => sprintf(__('Select whether you want to capture the payment or not.', 'express-checkout-paypal-payment-gateway-for-woocommerce')),
    //     'default' => 'sale',
    //     'desc_tip' => true
    // ),
    'advanced_option_title' => array(
        'title' => sprintf(__('Advanced Options', 'express-checkout-paypal-payment-gateway-for-woocommerce')),
        'type' => 'title',
        'class'=> 'eh-css-class'
    ),
    'invoice_prefix' => array(
        'title' => __('Invoice prefix', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'text',
        'description' => __('Enter an invoice prefix to identify transactions from your site.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => __('', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => true,
        'placeholder' => 'EH_',
    ),
    'paypal_allow_override' => array(
        'title' => __('Address override', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable to prevent checkout address being changed at the PayPal end.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => 'no',
        'description' => __('Enabling this will affect Express checkout and PayPal will strictly verify the address.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => false
    ),
    'send_shipping'   => array(
		'title'       => __( 'Shipping details', 'express-checkout-paypal-payment-gateway-for-woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable to send shipping details to PayPal instead of billing.', 'express-checkout-paypal-payment-gateway-for-woocommerce' ),
        'default'     => 'no',
        'description' => __('PayPal allows us to send only one among shipping/billing addresses. We advise you to validate PayPal Seller protection to send shipping details to PayPal.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip'    => false,
	),
   
    'skip_review' => array(
        'title' => __('Skip review page', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => 'yes',
        'description' => __('Enable to skip the review page (Customers returned from PayPal will be presented with a final review page which includes the order details) and move to the site directly.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => true
    ),
    'policy_notes' => array(
        'title' => __('Seller policy', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'textarea',
        'css' => 'width:25em',
        'description' => __('Enter the seller protection policy or customized text which will be displayed in order review page.', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'default' => __('You are Protected by ' . get_bloginfo('name', 'display') . ' Policy', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'desc_tip' => true
    ),
    'log_title' => array(
        'title' => sprintf(__('Debug Logs', 'express-checkout-paypal-payment-gateway-for-woocommerce')),
        'type' => 'title',
        'class'=> 'eh-css-class',
        'description' => sprintf(__('Records PayPal payment transactions into WooCommerce status log. <a href="' . admin_url("admin.php?page=wc-status&tab=logs") . '" target="_blank"> View log </a>', 'express-checkout-paypal-payment-gateway-for-woocommerce'))
    ),
    'paypal_logging' => array(
        'title' => __(' Log', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'label' => __('Enable', 'express-checkout-paypal-payment-gateway-for-woocommerce'),
        'type' => 'checkbox',
        'description' => sprintf(__('<span style="color:green">Log File</span>: ' . strstr(wc_get_log_file_path('eh_paypal_express_log'), 'eh_paypal_express_log') . ' ( ' . $file_size . ' ) ', 'express-checkout-paypal-payment-gateway-for-woocommerce')),
        'default' => 'yes',
        'desc_tip' => __(' Enable to record PayPal payment transactions in a log file.', 'express-checkout-paypal-payment-gateway-for-woocommerce')
    ),
    
);


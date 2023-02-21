<?php

class CartUtilityRestController extends WP_REST_Controller {
    var $base_route = 'app-utility/v1';

    function __construct(){
        add_action( 'rest_api_init', array( $this, 'init_endpoints' ) );
    }

    public function init_endpoints() {


        register_rest_route( $this->base_route, '/totals', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'woocommerce_calc_cart_totals' ),
            'permission_callback' => '__return_true'
        ));
    }

    // After a lot of research to the way woo-commerce uses persistent cart I have created a working solution.
    function woocommerce_calc_cart_totals( WP_REST_Request $request ) {

        $body = array('line_items', 'billing', 'shipping_lines');

        $tax_total = 0.0;
        $shipping_total = 0.0;

        foreach ($body as $key) {
            if ( !isset($request[$key]) ) {
                return rest_ensure_response(array(
                    'message' => 'missing requried properties',
                    'status' => 'failed',
                    'code' => 400,
                ));
            }
        }

        $cart_products = (array) $request['line_items'];
        $billing = (array) $request['billing'];
        $shipping = isset($request['shipping']) ? (array) $request['shipping'] : false;
        $shipping_lines = $request['shipping_lines'];

        require( WC_ABSPATH . 'includes/wc-cart-functions.php' );
        require_once( WC_ABSPATH . 'includes/wc-notice-functions.php' );

        if(!function_exists('WC') && !class_exists('WC_Session_Handler') && !class_exists('WC_Customer') && !class_exists('WC_Cart') && !class_exists('WC_Product_Factory')){
            return rest_ensure_response(array(
                'message' => 'missing requried wc classes',
                'status' => 'failed',
                'code' => 400,
            ));
        }

        $woocommerce = WC();
        $woocommerce->session = new WC_Session_Handler();
        $woocommerce->session->init();
        $woocommerce->customer = new WC_Customer( );
        $product_factory = new WC_Product_Factory();

        $woocommerce->customer->set_billing_city($billing['city']);
        $woocommerce->customer->set_billing_state($billing['state']);
        $woocommerce->customer->set_billing_postcode($billing['postcode']);
        $woocommerce->customer->set_billing_country($billing['country']);

        if($shipping){
            $woocommerce->customer->set_shipping_city($shipping['city']);
            $woocommerce->customer->set_shipping_state($shipping['state']);
            $woocommerce->customer->set_shipping_postcode($shipping['postcode']);
            $woocommerce->customer->set_shipping_country($shipping['country']);
        }

        $tax_rates = $this->wooc_tax_rates($woocommerce->customer);

        $woocommerce->cart = new WC_Cart();
        $woocommerce->cart->empty_cart();
        foreach($cart_products as $values) {
            $quantity = (int) $values['quantity'];
            $product_id = (int) $values['product_id'];

            if(isset($values['variation_id'])){
                $woocommerce->cart->add_to_cart($product_id, $quantity, $values['variation_id']);
            }else{
                $woocommerce->cart->add_to_cart($product_id, $quantity);
            }
        }

        if(count($shipping_lines) >= 1){
            $shipping_total = (float) $shipping_lines[0]['total'];
        }

        // if ($woocommerce->cart->needs_shipping()){ }

        // The tax calculation for tax-inclusive prices is:
        // tax_amount = price - ( price / ( ( tax_rate_% / 100 ) + 1 ) )

        // The tax calculation for tax-exclusive prices is:
        // tax_amount = price * ( tax_rate_% / 100 )
            
        $shipping_tax = 0.0;
        $cart_taxes = array();
        if ( wc_tax_enabled()) {
            foreach ( $woocommerce->cart->get_cart_item_tax_classes() as $tkey => $tax ) {
                $key = array_search($tax, array_column($tax_rates, 'tax_class'));
                $rate = (float) $tax_rates[$key]['rate'];
                $cart_taxes[] = array($tax => $rate);
            }

            if(count($cart_taxes) == 1){
                $rate = (float) array_values($cart_taxes[0])[0];
                if($woocommerce->cart->display_prices_including_tax()){
                    // calc shipping tax
                    $shipping_tax =  $shipping_total - ($shipping_total * ($rate / 100));
        
                    // calc cart tax => iterate cart items and apply tax formula
                    foreach ($woocommerce->cart->get_cart() as $key => $value) {
                        $p = $product_factory->get_product($value['product_id']);
                        $p_tax = (float) $p->get_price() - ((float) $p->get_price() * ($rate / 100));
                        $tax_total += $value['quantity'] * $p_tax;
                    }
                }else{
                    // calc shipping tax
                    $shipping_tax = $shipping_total * ($rate / 100);
        
                    // calc cart tax => iterate cart items and apply tax formula
                    foreach ($woocommerce->cart->get_cart() as $key => $value) {
                        $p = $product_factory->get_product($value['product_id']);
                        $p_tax = (float) $p->get_price() * ($rate / 100);
                        $tax_total += $value['quantity'] * $p_tax;
                    }
                }
            }elseif(count($cart_taxes) >= 2){
                // select which tax class to use or applly ??? 
            }
		}

        $cart_totals = new WC_Cart_Totals( $woocommerce->cart );
        $subtotal = $cart_totals->get_totals()['items_subtotal'];

        $tax_total = (float) number_format($tax_total, 2, '.', '');
        $shipping_total = (float) number_format($shipping_total, 2, '.', '');
        $shipping_tax = (float) number_format($shipping_tax, 2, '.', '');
        $total_to_pay = (float) number_format($subtotal + $shipping_total + ($tax_total + $shipping_tax), 2, '.', '');

        return rest_ensure_response([
            'subtotal' => $subtotal,
            'shipping_total' => $shipping_total,
            'cart_tax' => $tax_total,
            'shipping_tax' => $shipping_tax,
            'tax_total' => $shipping_tax + $tax_total,
            'total' => $total_to_pay
        ]);
    }

    function wooc_tax_rates(WC_Customer $customer){
        $location = array(
            'country'   => $customer->get_shipping_country() ? $customer->get_shipping_country() : $customer->get_billing_country(),
            'state'     => $customer->get_shipping_state() ? $customer->get_shipping_state() : $customer->get_billing_state(),
            'city'      => $customer->get_shipping_city() ? $customer->get_shipping_city() : $customer->get_billing_city(),
            'postcode'  => $customer->get_shipping_postcode() ? $customer->get_shipping_postcode() : $customer->get_billing_postcode(),
        );
        
        $output = array(); // Initialiizing (for display)
        
        // Loop through tax classes
        foreach ( wc_get_product_tax_class_options() as $tax_class => $tax_class_label ) {
        
            // Get the tax data from customer location and product tax class
            $tax_rates = WC_Tax::find_rates( array_merge(  $location, array( 'tax_class' => $tax_class ) ) );
        
            // Finally we get the tax rate (percentage number) and display it:
            if( ! empty($tax_rates) ) {
                $rate_id      = array_keys($tax_rates);
                $rate_data    = reset($tax_rates);
        
                $rate_id      = reset($rate_id);        // Get the tax rate Id
                $rate         = $rate_data['rate'];     // Get the tax rate
                $rate_label   = $rate_data['label'];    // Get the tax label
                $is_compound  = $rate_data['compound']; // Is tax rate compound
                $for_shipping = $rate_data['shipping']; // Is tax rate used for shipping
        
                // set for display
                $output[] = array(
                    'tax_class' => $tax_class,
                    'tax_class_label' => $tax_class_label,
                    'rate' => $rate,
                    'rate_label' => $rate_label,
                    'rate_id' => $rate_id,
                    'is_compound' => $is_compound,
                    'for_shipping' => $for_shipping
                );
            }
        }

        return $output;
    }
}
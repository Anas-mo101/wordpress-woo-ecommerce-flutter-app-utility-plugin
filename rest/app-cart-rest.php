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

        $body = array('products');

        foreach ($body as $key) {
            if ( !isset($request[$key]) ) {
                return rest_ensure_response(array(
                    'message' => 'missing requried properties',
                    'status' => 'failed',
                    'code' => 400,
                ));
            }
        }

        $cart_products = (array) $request['products'];

        if(!function_exists('WC') && !class_exists('WC_Session_Handler') && !class_exists('WC_Customer') && !class_exists('WC_Cart')){
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
        $woocommerce->cart = new WC_Cart();

        foreach($cart_products as $values) {
            $quantity = (int) $values['quantity'];
            $product_id = (int) $values['product_id'];

            if(isset($values['variation_id'])){
                $woocommerce->cart->add_to_cart($product_id, $quantity, $values['variation_id']);
            }else{
                $woocommerce->cart->add_to_cart($product_id, $quantity);
            }
        }

        $cart_totals = new WC_Cart_Totals( $woocommerce->cart );

        $totals = $cart_totals->get_totals();

        $woocommerce->cart->empty_cart();

        return rest_ensure_response($totals);
    }

}
<?php


class AR{
    
    function __construct(){
        add_action('woocommerce_product_options_general_product_data', [$this,'woocommerce_product_custom_fields']);
        add_action('woocommerce_admin_process_product_object', [$this,'woocommerce_product_custom_fields_save']);
    }

    function init_directory(){

    }


    function woocommerce_product_custom_fields() {
        global $product_object;

        echo '<div class=" product_custom_field ">';

        // Custom Product Text Field
        woocommerce_wp_text_input( array( 
            'id'          => 'tipologiaAppunto',
            'label'       => __('Tipologia appunto:', 'woocommerce'),
            'placeholder' => '',
            'desc_tip'    => 'true' // <== Not needed as you don't use a description
        ) );

        echo '</div>';
    }

        // Save admin product custom setting field(s) values
    function woocommerce_product_custom_fields_save( $product ) {
        if ( isset($_POST['tipologiaAppunto']) ) {
            $product->update_meta_data( 'tipologiaAppunto', sanitize_text_field( $_POST['tipologiaAppunto'] ) );
        }
    }
}
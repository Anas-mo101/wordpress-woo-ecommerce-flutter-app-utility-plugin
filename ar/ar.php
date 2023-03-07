<?php


class AR{
    
    function __construct(){
        add_action('woocommerce_product_options_general_product_data', [$this,'woocommerce_product_custom_fields']);
        add_action('woocommerce_admin_process_product_object', [$this,'woocommerce_product_custom_fields_save']);

        add_filter( 'mime_types', array($this, 'mime_types'), 1, 1 );
    }

    function mime_types($mime){
        $mime['deepar'] = 'application/deepar'; 
        return $mime;
    }


    function woocommerce_product_custom_fields() {
        global $product_object;

        $id = $product_object->get_id();
        $filename = get_post_meta($id, 'try_on_deepar_effect_file', true );

        ?>

        <script>
            var media_selector_frame;
            const mediaFileSelector = () => {
                if (media_selector_frame) media_selector_frame = null;
                media_selector_frame = wp.media({
                    title: 'Select .deepar file',
                    button: {
                        text: 'Insert'
                    },
                    multiple: false,
                    library: {
                        type: ['deepar']
                    },
                    uploader: {
                        type: ['deepar']
                    }
                }).on('select', function () {
                    var attachment = media_selector_frame.state().get('selection').first().toJSON();
                    document.getElementById(`try_on_deepar_effect_file`).value = attachment.url;
                });
                media_selector_frame.open();
            }

            const clearFile = () => {
                document.getElementById(`try_on_deepar_effect_file`).value = '';
            }
        </script>
        
        <div style="display: flex; gap: 20px; align-items: center;" class=" product_custom_field ">
            <p> Try On Effect File (.deepar): </p>
            <input readonly value="<?= $filename ?>" class="short" type="text" name="try_on_deepar_effect_file" id="try_on_deepar_effect_file">
            <button onclick="mediaFileSelector()" type="button" > upload file </button>
            <button onclick="clearFile()" type="button" > clear </button>
        </div>  

        <?php

    }

        // Save admin product custom setting field(s) values
    function woocommerce_product_custom_fields_save( $product ) {
        if ( isset($_POST['try_on_deepar_effect_file']) ) {
            $product->update_meta_data( 'try_on_deepar_effect_file', $_POST['try_on_deepar_effect_file'] );
        }
    }
}
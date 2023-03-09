<?php




class Ads{

    var $custom_feilds = ['app_ads_forward_link'];

    var $ad_post_type = 'app_ads';

    var $ad_interval_option_keys = 'app_ads_display_interval';

    function __construct(){
        add_action( 'init', array($this, 'register_ads_post' ) );

        add_action('admin_menu', array($this,'sub_menu_callback'));
        add_action( 'save_post', array($this, 'save_custom_fields' ), 1, 2 );
        add_action( 'do_meta_boxes', array($this, 'remove_default_custom_fields' ), 10, 3 );
        add_action( 'admin_menu', array($this, 'create_custom_fields' ) );

        if(! get_option($this->ad_interval_option_keys)){
            add_option($this->ad_interval_option_keys, '1');
        }

        require_once 'app-ads-rest.php';

        new AppAdsRest($this->ad_post_type);
    }

    function sub_menu_callback(){
        add_submenu_page(
            'edit.php?post_type=app_ads',
            'Settings',
            'Settings',
            'manage_options',      
            'ads_settings',
            function () {
                require_once WP_PLUGIN_DIR . '/wp-wc-flutter-app-utility/ads/ads-settings.php'; 
            }
        );
    }

    function remove_default_custom_fields( $type, $context, $post ) {
        foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
            remove_meta_box( 'postcustom', $this->ad_post_type, $context );
        }
    }

    function create_custom_fields(){
        if ( function_exists( 'add_meta_box' ) ) {
            add_meta_box(
                'my-custom-inbox', 
                'Details',
                array($this, 'display_inbox' ), 
                $this->ad_post_type, 
                'normal', 
                'high'
            );
        }
    }

    function display_inbox() {
        global $post;  
        if ( $post->post_type == $this->ad_post_type ) {
            $link = get_post_meta($post->ID, 'app_ads_forward_link', true);

            ?>
                
                <div style="display: flex; gap: 20px;">
                    <h2> <b>Ad Forward Link:</b> </h2>
                    <input type="url" type="text" name="app_ads_forward_link" value="<?php echo esc_html( $link ); ?>">
                </div>
            
            <?php
        } 
    }

    function save_custom_fields( $post_id, $post ) {
        if ( !current_user_can( 'edit_post', $post_id ) ) return;

        foreach ( $this->custom_feilds as $customField ) {
            if ( isset( $_POST[ $customField ] ) && trim( $_POST[ $customField ] ) ) {
                $value = $_POST[ $customField ];
                if($customField == 'app_ads_forward_link'){
                    update_post_meta( $post_id, $customField, $value );
                }
            }
        }

    }

    function register_ads_post(){
        register_post_type($this->ad_post_type,array(
            'labels' => array(
                'name' => _x('App Ads', 'post type general name'),
                'singular_name' => _x('App Ads', 'post type singular name'),
                'add_new' => _x('Add New', 'App Ads'),
                'add_new_item' => __('Add New App Ads'),
                'edit_item' => __('Email Inbox'),
                'new_item' => __('New App Ads'),
                'view_item' => __('View App Ads'),
                'search_items' => __('Search App Ads'),
                'not_found' =>  __('No App Ads found'),
                'not_found_in_trash' => __('No App Ads found in Trash'),
                'parent_item_colon' => '',
                'menu_name' => 'App Ads'
            ),
            'public' => true,
            'publicly_queryable' => false,
            'post_status' => 'publish',
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,  
            'rewrite' => array('slug'=>'app-ads'),
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title','thumbnail', 'editor')
        ));
    }

}














?>
<?php


if( ! defined('ABSPATH') ){ die; }

class AppAdsRest extends WP_REST_Controller {

    var $custom_feilds = ['app_ads_forward_link'];

    var $base_route = 'app-utility/v1';

    var $ad_post_type = 'app_ads';

    var $ad_interval_option_keys = 'app_ads_display_interval';

    function __construct(){
        // register post type
        add_action('init', array($this, 'init_ads') );

        // create post rest crud 
        add_action( 'rest_api_init', array( $this, 'init_endpoints' ) );
    }

    public function init_endpoints() {

        register_rest_route( $this->base_route, '/ads', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_ads' ),
            'permission_callback' => '__return_true'
        ));

        register_rest_route( $this->base_route, '/ads/all', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_all_ads' ),
            'permission_callback' => '__return_true'
        ));

        register_rest_route( $this->base_route, '/ads/settings', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_ads_settings' ),
            'permission_callback' => '__return_true'
        ));
        
    }

    function sub_menu_callback(){
        add_submenu_page(
            'edit.php?post_type=app_ads', //$parent_slug
            'Settings',  //$page_title
            'Settings',        //$menu_title
            'manage_options',           //$capability
            'app_ads_settings',//$menu_slug
            function () { // anonymous callback function
                require_once WP_PLUGIN_DIR . '/wp-wc-flutter-app-utility/assets/ads-settings.php'; 
            }
        );
    }

    public function init_ads(){
        $this->register_ads_post();

        add_action('admin_menu', array($this,'sub_menu_callback'));
        add_action( 'save_post', array($this, 'save_custom_fields' ), 1, 2 );
        add_action( 'do_meta_boxes', array($this, 'remove_default_custom_fields' ), 10, 3 );
        add_action( 'admin_menu', array($this, 'create_custom_fields' ) );

        if(! get_option($this->ad_interval_option_keys)){
            add_option($this->ad_interval_option_keys, '1');
        }
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

    function get_ads(WP_REST_Request $request){

        $auth = Jwt_Auth_Public::validate_rest_token($request);
        if ( is_wp_error( $auth ) ) {
			return $auth;
		}

        $user_ads_posts = get_posts(array(
            'posts_per_page' => 9999999,
            'post_type' => $this->ad_post_type,
        ));

        $num_of_ads = count($user_ads_posts);

        if ( $num_of_ads < 1 ) {
            return new WP_Error( 'failed', 'no ads exists', array( 'status' => 400 ) );
        }

        $selected_post_ad = $user_ads_posts[array_rand($user_ads_posts)];
        $ad_link = get_post_meta( $selected_post_ad->ID, 'app_ads_forward_link', true ) ?? '';
        $featured_img_url = get_the_post_thumbnail_url( $selected_post_ad->ID,'full');

        return rest_ensure_response([
            'ad_id' => $selected_post_ad->ID,
            'title' => $selected_post_ad->post_title,
            'content' => $selected_post_ad->post_content,
            'image' => $featured_img_url,
            'link' => $ad_link
        ]);
    }


    function get_all_ads(WP_REST_Request $request){

        $auth = Jwt_Auth_Public::validate_rest_token($request);
        if ( is_wp_error( $auth ) ) {
			return $auth;
		}

        $user_ads_posts = get_posts(array(
            'posts_per_page' => 10,
            'post_type' => $this->ad_post_type,
        ));

        $ads = [];

        foreach ($user_ads_posts as $post) {
            $ad_link = get_post_meta( $post->ID, 'app_ads_forward_link', true ) ?? '';
            $featured_img_url = get_the_post_thumbnail_url( $post->ID,'full');
    

            $ads[] = [
                'ad_id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'image' => $featured_img_url,
                'link' => $ad_link
            ];
        }

        return rest_ensure_response($ads);
    }

    function get_ads_settings(WP_REST_Request $request){

        $auth = Jwt_Auth_Public::validate_rest_token($request);
        if ( is_wp_error( $auth ) ) {
			return $auth;
		}

        $interval = get_option($this->ad_interval_option_keys);

        return rest_ensure_response([
            'interval' => $interval,
        ]);
    }
}
<?php


if( ! defined('ABSPATH') ){ die; }

class AppAdsRest extends WP_REST_Controller {

    var $base_route = 'app-utility/v1';

    var $ad_post_type;

    var $ad_interval_option_keys = 'app_ads_display_interval';

    function __construct($post_type){

        $this->ad_post_type = $post_type;

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
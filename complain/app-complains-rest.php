<?php


if( ! defined('ABSPATH') ){ die; }


class AppComplainsRest extends WP_REST_Controller {

    var $complains_statuses = [];

    var $base_route = 'app-utility/v1';

    var $ac_post_type;

    function __construct($post_type, $statues){

        $this->ac_post_type = $post_type;
        $this->complains_statuses = $statues;

        // create post rest crud 
        add_action( 'rest_api_init', array( $this, 'init_endpoints' ) );
    }

    public function init_endpoints() {

        register_rest_route( $this->base_route, '/complains', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_user_complains' ),
            'permission_callback' => '__return_true'
        ));

        register_rest_route( $this->base_route, '/complain', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'create_new_complain' ),
            'permission_callback' => '__return_true'
        ));

        register_rest_route( $this->base_route, '/complain/responed', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'response_to_complain' ),
            'permission_callback' => '__return_true'
        ));


        register_rest_route( $this->base_route, '/complain/single', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_user_complain' ),
            'permission_callback' => '__return_true'
        ));
    }

    function create_new_complain( WP_REST_Request $request ) {

        $auth = Jwt_Auth_Public::validate_rest_token($request);
        if ( is_wp_error( $auth ) ) {
			return $auth;
		}

        $body = array('user_email', 'message', 'title', 'reason');

        $body_data = json_decode($request->get_body(), true);

        foreach ($body as $key) {
            if ( !isset($body_data[$key]) && $body_data[$key] == '') {
                return new WP_Error( 'failed', 'missing requried properties', array( 'status' => 400 ) );
            }
        }

        $user_email = $body_data['user_email'];
        $user_id = $auth['data']['user_id'];
        // $user_id = $body_data['user_id'];
        $user_title = $body_data['title'];
        $user_reason = $body_data['reason'];
        $user_message = $body_data['message'];

        $result = wp_insert_post(array(
            'post_type'       => "app_complains",
            'post_title'      => $user_email . ' - ' . $user_title,
            'post_name'       => $user_email . ' - ' . $user_title, 
            'post_status'     => "publish",
            'comment_status'  => "closed",
            'ping_status'     => "closed",
            'meta_input'      => array(
                'app_complain_user_email' => $user_email,
                'app_complain_user_id' => $user_id,
                'app_complain_user_title' => $user_title,
                'app_complain_user_reason' => $user_reason,
                'app_complain_user_messages' => '[]',
                'app_complain_status' => $this->complains_statuses[0]
            )
        ));

        if( $result && ! is_wp_error( $result ) ) {
            $complain_id = $result;

            require_once WP_PLUGIN_DIR . '/wp-wc-flutter-app-utility/complain/chatroom-control.php';

            $control = new ChatroomControl();
            $msgs_json = $control->client_init_chat($complain_id, $user_id, $user_message);
            

            return rest_ensure_response([
                'complain_id' => $complain_id,
                'user_email' => $user_email,
                'user_id' => $user_id,
                'user_title' => $user_title,
                'user_reason' => $user_reason,
                'user_messages' => $msgs_json,
                'complain_status' => $this->complains_statuses[0]
            ]);
        }
    }

    function response_to_complain( WP_REST_Request $request ) {

        $auth = Jwt_Auth_Public::validate_rest_token($request);
        if ( is_wp_error( $auth ) ) {
			return $auth;
		}

        $body = array('message', 'complain_id');

        $body_data = json_decode($request->get_body(), true);

        foreach ($body as $key) {
            if ( !isset($body_data[$key]) ) {
                return new WP_Error( 'failed', 'missing requried properties', array( 'status' => 400 ) );
            }
        }

        $user_id = $auth['data']['user_id'];
        // $user_id = $body_data['user_id'];
        $user_message = $body_data['message'];
        $complain_id = $body_data['complain_id'];

        require WP_PLUGIN_DIR . '/wp-wc-flutter-app-utility/complain/chatroom-control.php';

        $control = new ChatroomControl();
        $msgs_json = $control->client_send_chat($complain_id, $user_id, $user_message);


        $user_email = get_post_meta( $complain_id, 'app_complain_user_email', true );
        $user_id = get_post_meta( $complain_id, 'app_complain_user_id', true );
        $user_title = get_post_meta( $complain_id, 'app_complain_user_title', true );
        $user_reason = get_post_meta( $complain_id, 'app_complain_user_reason', true );
        $user_message = get_post_meta( $complain_id, 'app_complain_user_messages', true );
        $msgs_json = json_decode($user_message, true);
        $user_status = get_post_meta( $complain_id, 'app_complain_status', true );


        $user_complain = [
            'complain_id' => $complain_id,
            'user_email' => $user_email,
            'user_id' => $user_id,
            'user_title' => $user_title,
            'user_reason' => $user_reason,
            'user_messages' => $msgs_json,
            'complain_status' => $user_status
        ];


        return rest_ensure_response($user_complain);
    }


    function get_user_complains(WP_REST_Request $request){

        $auth = Jwt_Auth_Public::validate_rest_token($request);
        if ( is_wp_error( $auth ) ) {
			return $auth;
		}

        $user_id = $auth['data']['user_id'];

        // $user_id = $request->get_param('user_id');

        // if ( !isset($user_id) || $user_id == '') {
        //     return new WP_Error( 'failed', 'user id is mising', array( 'status' => 400 ) );
        // }

        $user_complains_posts = get_posts(array(
            'posts_per_page' => -1,
            'post_type' => $this->ac_post_type,
            'meta_query' => array(
                array(
                    'key'     => 'app_complain_user_id',
                    'value'   => sanitize_text_field($user_id),
                    'compare' => 'LIKE',
                ),
            )
        ));

        $user_complains = [];

        foreach ($user_complains_posts as $post) {
            $user_email = get_post_meta( $post->ID, 'app_complain_user_email', true );
            $user_id = get_post_meta( $post->ID, 'app_complain_user_id', true );
            $user_title = get_post_meta( $post->ID, 'app_complain_user_title', true );
            $user_reason = get_post_meta( $post->ID, 'app_complain_user_reason', true );
            $user_message = get_post_meta( $post->ID, 'app_complain_user_messages', true );
            $msgs_json = json_decode($user_message, true);
            $user_status = get_post_meta( $post->ID, 'app_complain_status', true );
    

            $user_complains[] = [
                'complain_id' => $post->ID,
                'user_email' => $user_email,
                'user_id' => $user_id,
                'user_title' => $user_title,
                'user_reason' => $user_reason,
                'user_messages' => $msgs_json,
                'complain_status' => $user_status
            ];
        }

        return rest_ensure_response($user_complains);
    }


    function get_user_complain(WP_REST_Request $request){

        $auth = Jwt_Auth_Public::validate_rest_token($request);
        if ( is_wp_error( $auth ) ) {
			return $auth;
		}

        $complain_id = $request->get_param('complain_id');

        if ( !isset($complain_id) || $complain_id == '') {
            return new WP_Error( 'failed', 'complain id is mising', array( 'status' => 400 ) );
        }

        if ( ! get_post_status( $complain_id ) ) {
            return new WP_Error( 'failed', 'complain does not exsit', array( 'status' => 400 ) );
        }

        $user_email = get_post_meta( $complain_id, 'app_complain_user_email', true );
        $user_id = get_post_meta( $complain_id, 'app_complain_user_id', true );
        $user_title = get_post_meta( $complain_id, 'app_complain_user_title', true );
        $user_reason = get_post_meta( $complain_id, 'app_complain_user_reason', true );
        $user_message = get_post_meta( $complain_id, 'app_complain_user_messages', true );
        $msgs_json = json_decode($user_message, true);
        $user_status = get_post_meta( $complain_id, 'app_complain_status', true );


        $user_complains = [
            'complain_id' => $complain_id,
            'user_email' => $user_email,
            'user_id' => $user_id,
            'user_title' => $user_title,
            'user_reason' => $user_reason,
            'user_messages' => $msgs_json,
            'complain_status' => $user_status
        ];

        return rest_ensure_response($user_complains);
    }

}
<?php


if( ! defined('ABSPATH') ){ die; }

class AppComplainsRest extends WP_REST_Controller {

    var $complains_statuses = ['pending_review', 'ongoing_investigation', 'resolved'];

    var $customFields = [                
        'app_complain_user_email',
        'app_complain_user_id',
        'app_complain_user_title',
        'app_complain_user_reason',
        'app_complain_user_messages',
        'app_complain_status'
    ];

    var $base_route = 'app-utility/v1';

    var $ac_post_type = 'app_complains';

    function __construct(){
        // register post type
        add_action('init', array($this, 'init_complains') );

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

    public function init_complains(){
        $this->register_complains_post();

        // add_action( 'restrict_manage_posts', array($this, 'add_section_filter' ) );
        // add_filter( 'pre_get_posts', array($this, 'filter_complains' ) );

        // add_filter( 'manage_app_complains_posts_columns', array($this, 'set_custom_edit_columns' ) );
        // add_action( 'manage_app_complains_posts_custom_column' , array($this, 'custom_column' ), 10, 2 );

        add_action( 'save_post', array($this, 'save_custom_fields' ), 1, 2 );
        add_action('admin_enqueue_scripts', array($this, 'admin_edit_scripts') );

        add_action( 'do_meta_boxes', array($this, 'remove_default_custom_fields' ), 10, 3 );
        add_action( 'admin_menu', array($this, 'create_custom_fields' ) );
    }

    // function set_custom_edit_columns($columns) {
    //     $columns['source_form'] = __( 'Form Name', 'your_text_domain' );

    //     return $columns;
    // }

    // function custom_column( $column, $post_id ) {
    //     switch ( $column ) {
    //         case 'source_form' :
    //             $wpcf7_id = get_post_meta($post_id, 'mc_wpcf7_form_title', true);
    //             echo esc_html( $wpcf7_id ); 
    //             break;
    //     }
    // }

    function remove_default_custom_fields( $type, $context, $post ) {
        foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
            remove_meta_box( 'postcustom', $this->ac_post_type, $context );
        }
    }

    function create_custom_fields(){
        if ( function_exists( 'add_meta_box' ) ) {
            add_meta_box(
                'my-custom-inbox', 
                'Details',
                array($this, 'display_inbox' ), 
                $this->ac_post_type, 
                'normal', 
                'high'
            );
        }
    }

    function admin_edit_scripts( $hook ) {
        global $post;
        $dir = plugin_dir_url( __FILE__ );
        if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
            if ( $this->ac_post_type === $post->post_type ) {    
                wp_enqueue_style( 'chatroom', $dir . 'admin/style/chatroom-style.css' );
            }
        }
    }


    function display_inbox() {
        global $post;  
        if ( $post->post_type == $this->ac_post_type ) {

            $user_id = get_post_meta($post->ID, 'app_complain_user_id', true);
            $user_email = get_post_meta($post->ID, 'app_complain_user_email', true);
            $complain_title = get_post_meta($post->ID, 'app_complain_user_title', true);
            $complain_reason = get_post_meta($post->ID, 'app_complain_user_reason', true);
            $complain_status = get_post_meta($post->ID, 'app_complain_status', true);

            $messages = json_decode( get_post_meta($post->ID, 'app_complain_user_messages', true), true);

            ?>
                <h2> <b>Complain Title:</b> <?php echo esc_html( $complain_title ); ?> </h2>
                <h2> <b>User Id:</b> <?php echo esc_html( $user_id ); ?> </h2>
                <h2> <b>User Email:</b> <?php echo esc_html( $user_email ); ?> </h2>
                <h2> <b>Reason for Complain:</b> <?php echo esc_html( $complain_reason ); ?> </h2>
                
                <div style="display: flex; gap: 20px;">
                    <h2> <b>Complain Status:</b> </h2>
                    <select name="app_complain_status" >
                        <?php foreach ($this->complains_statuses as $value) : ?>
                            <option <?= $value == $complain_status ? 'selected' : '' ?> value="<?= $value ?>"> <?= $value ?> </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <hr style="border-top: 3px solid #bbb;">
                <div style="display: flex; justify-content: space-between;">
                    <h2> <b> Messages </b> </h2>
                    <a href=""><h2> <b> Reload </b> </h2></a>
                </div>
                <?php require_once WP_PLUGIN_DIR . '/wp-wc-flutter-app-utility/assets/chatroom.php'; ?>
                <hr style="border-top: 3px solid #bbb;">
            
            <?php
           
        } 
    }

    function save_custom_fields( $post_id, $post ) {
        if ( !current_user_can( 'edit_post', $post_id ) ) return;

        foreach ( $this->customFields as $customField ) {
            if ( isset( $_POST[ $customField ] ) && trim( $_POST[ $customField ] ) ) {
                $value = $_POST[ $customField ];

                if($customField == 'app_complain_user_messages'){
                    
                    $current_user = wp_get_current_user();
                    $msgs_old_json = get_post_meta($post_id,'app_complain_user_messages',true);
                    $msgs_old = json_decode($msgs_old_json, true);
                    $new_msg = [
                        'sender' => 'admin',
                        'sender_id' =>  $current_user->ID,
                        'message' => $value,
                        'sending_datetime' => date("D M d, Y G:i")
                    ];
                    $msgs_old[] = $new_msg; 
                    $msgs_json = json_encode($msgs_old);
                    
                    update_post_meta( $post_id, $customField, $msgs_json );

                }elseif($customField == 'app_complain_status'){
                    update_post_meta( $post_id, $customField, $value );
                }
            }
        }

    }

    function add_section_filter($post_type ) {
        if( $this->ac_post_type !== $post_type ) return;

        // sanitize form name
        $section = sanitize_text_field( $_GET[ 'source_form' ] ) ;

        if ( !isset( $_GET[ 'source_form' ] ) || empty( $_GET[ 'source_form' ] ) ) return;

        // get wpcf7 form list
        $args = array( 'post_type' => 'wpcf7_contact_form', 'posts_per_page' => 999999, 'post_status' => 'publish');
        $wpcf7_forms = get_posts($args);

        ?>
            <script>
                document.querySelector('#posts-filter > div.tablenav.top > div:nth-child(2)').innerHTML = '';
            </script>
            <select name="source_form">
                <option value=""> Form Name </option>
                <?php foreach ( $wpcf7_forms as $forms ): 
                    $selected = $forms->post_title == $section ? 'selected="selected"' : ''; ?>
                    <option <?php echo $selected; ?> value="<?php echo esc_attr( $forms->post_title ); ?>"><?php echo esc_html( $forms->post_title ); ?></option>
                <?php endforeach; ?>
            </select>
        <?php
    }

    // function filter_complains( $query ) {
    //     if ( !$query->is_main_query() ) return;

    //     // sanitize form name
    //     $section = sanitize_text_field( $_GET[ 'source_form' ] ) ;

    //     if ( !isset( $section ) || empty( $section ) ) return;

    //     $meta_query = array(
    //         array(
    //             'key' => 'mc_wpcf7_form_title',
    //             'value' => $section,
    //             'compare' => '='
    //         )
    //     );
    //     $query->set( 'meta_query', $meta_query );
    // }

    function register_complains_post(){
        register_post_type('app_complains',array(
            'labels' => array(
                'name' => _x('App Complains', 'post type general name'),
                'singular_name' => _x('App Complains', 'post type singular name'),
                'add_new' => _x('Add New', 'App Complains'),
                'add_new_item' => __('Add New App Complains'),
                'edit_item' => __('Email Inbox'),
                'new_item' => __('New App Complains'),
                'view_item' => __('View App Complains'),
                'search_items' => __('Search App Complains'),
                'not_found' =>  __('No App Complains found'),
                'not_found_in_trash' => __('No App Complains found in Trash'),
                'parent_item_colon' => '',
                'menu_name' => 'App Complains'
            ),
            'public' => true,
            'publicly_queryable' => false,
            'post_status' => 'publish',
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,  
            'rewrite' => array('slug'=>'app-complains'),
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title','custom-fields')
        ));
    }

    function create_new_complain( WP_REST_Request $request ) {

        $auth = Jwt_Auth_Public::validate_rest_token($request);
        if ( is_wp_error( $auth ) ) {
			return $auth;
		}

        $body = array('user_email', 'user_id', 'message', 'title', 'reason');

        $body_data = json_decode($request->get_body(), true);

        foreach ($body as $key) {
            if ( !isset($body_data[$key]) && $body_data[$key] == '') {
                return new WP_Error( 'failed', 'missing requried properties', array( 'status' => 400 ) );
            }
        }

        $user_email = $body_data['user_email'];
        $user_id = $body_data['user_id'];
        $user_title = $body_data['title'];
        $user_reason = $body_data['reason'];
        $user_message = $body_data['message'];

        /// append message

        $msgs = [];
        $new_msg = [
            'sender' => 'client',
            'sender_id' =>  $user_id,
            'message' => $user_message,
            'sending_datetime' => date("D M d, Y G:i")
        ];
        $msgs[] = $new_msg; 

        // ===

        $current_user = wp_get_current_user();
        $id = $current_user->ID;

        $new_msg = [
            'sender' => 'admin',
            'sender_id' =>  $id,
            'message' => 'Thanks for reaching out, a customer support agent will get in touch with you shortly.',
            'sending_datetime' => date("D M d, Y G:i")
        ];
        $msgs[] = $new_msg; 
        $msgs_json = json_encode($msgs);

        /// ===


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
                'app_complain_user_messages' => $msgs_json,
                'app_complain_status' => $this->complains_statuses[0]
            )
        ));

        if( $result && ! is_wp_error( $result ) ) {
            $complain_id = $result;

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

        $body = array('user_id', 'message', 'complain_id');

        $body_data = json_decode($request->get_body(), true);

        foreach ($body as $key) {
            if ( !isset($body_data[$key]) ) {
                return new WP_Error( 'failed', 'missing requried properties', array( 'status' => 400 ) );
            }
        }

        $user_id = $body_data['user_id'];
        $user_message = $body_data['message'];
        $complain_id = $body_data['complain_id'];

        /// append message
        $msgs_old_json = get_post_meta($complain_id,'app_complain_user_messages',true);
        $msgs_old = json_decode($msgs_old_json, true);
        $new_msg = [
            'sender' => 'client',
            'sender_id' =>  $user_id,
            'message' => $user_message,
            'sending_datetime' => date("D M d, Y G:i")
        ];
        $msgs_old[] = $new_msg; 
        $msgs_json = json_encode($msgs_old);
        ///


        update_post_meta($complain_id,'app_complain_user_messages', $msgs_json);


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

        $user_id = $request->get_param('user_id');

        if ( !isset($user_id) || $user_id == '') {
            return new WP_Error( 'failed', 'user id is mising', array( 'status' => 400 ) );
        }

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
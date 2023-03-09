<?php




class Complain{

    var $complains_statuses = ['pending_review', 'ongoing_investigation', 'resolved'];

    var $ac_post_type = 'app_complains';

    var $customFields = [                
        'app_complain_user_email',
        'app_complain_user_id',
        'app_complain_user_title',
        'app_complain_user_reason',
        'app_complain_user_messages',
        'app_complain_status'
    ];


    function __construct(){

        add_action( 'init', array($this, 'register_complains_post' ) );

        add_action( 'admin_menu', array($this, 'sub_menu_callback' ) );
        add_action( 'admin_menu', array($this, 'create_custom_fields' ) );

        add_action( 'save_post', array($this, 'save_custom_fields' ), 1, 2 );
        add_action('admin_enqueue_scripts', array($this, 'admin_edit_scripts') );

        add_action( 'do_meta_boxes', array($this, 'remove_default_custom_fields' ), 10, 3 );

        require_once 'app-complains-rest.php';

        new AppComplainsRest($this->ac_post_type, $this->complains_statuses);
    }

    function sub_menu_callback(){
        add_submenu_page(
            'edit.php?post_type=app_complains',
            'Chatroon Settings',
            'Chatroom Settings',
            'manage_options',      
            'complains_chatroom_settings',
            function () {
                require_once WP_PLUGIN_DIR . '/wp-wc-flutter-app-utility/complain/chatroom-settings.php'; 
            }
        );
    }

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
        $dir = plugin_dir_url( dirname( __FILE__ ) );

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
                <?php require_once WP_PLUGIN_DIR . '/wp-wc-flutter-app-utility/complain/chatroom.php'; ?>
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
}

?>
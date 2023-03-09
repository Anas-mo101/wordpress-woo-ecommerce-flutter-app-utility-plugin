<?php

class ChatroomControl{

    var $message_key = 'app_complain_user_messages';
    
    var $complain_init_message = 'complain_init_message_key';

    function __construct(){

        if(! get_option($this->complain_init_message)){
            $init_msg = 'Thanks for reaching out, a customer support agent will get in touch with you shortly.';
            add_option($this->complain_init_message, $init_msg);
        }
    }


    public function client_init_chat($post_id, $cid, $message){
        $msgs = [];

        $new_msg = [
            'sender' => 'client',
            'sender_id' =>  $cid,
            'message' => $message,
            'sending_datetime' => date("D M d, Y G:i")
        ];
        $msgs[] = $new_msg; 

        $msgs[] = $this->welcome_response();

        $msgs_json = json_encode($msgs);
        update_post_meta( $post_id, $this->message_key, $msgs_json );

        return $msgs;
    }


    public function client_send_chat($post_id, $cid, $message){
        $msgs_old_json = get_post_meta($post_id, $this->message_key,true);
        $msgs_old = json_decode($msgs_old_json, true);

        $new_msg = [
            'sender' => 'client',
            'sender_id' =>  $cid,
            'message' => $message,
            'sending_datetime' => date("D M d, Y G:i")
        ];
        $msgs_old[] = $new_msg; 

        $msgs_json = json_encode($msgs_old);
        update_post_meta($post_id, $this->message_key, $msgs_json);

        return $msgs_old;
    }

    private function welcome_response(){
        $current_user = wp_get_current_user();
        $id = $current_user->ID;

        $message = get_option($this->complain_init_message);

        $new_msg = [
            'sender' => 'bot',
            'sender_id' =>  $id,
            'message' => $message,
            'sending_datetime' => date("D M d, Y G:i")
        ];

        return $new_msg;
    }

    private function auto_response($input){

        $current_user = wp_get_current_user();
        $id = $current_user->ID;
        $new_msg = [
            'sender' => 'bot',
            'sender_id' =>  $id,
            'message' => 'Thanks for reaching out, a customer support agent will get in touch with you shortly.',
            'sending_datetime' => date("D M d, Y G:i")
        ];
        return $new_msg;
    }
}
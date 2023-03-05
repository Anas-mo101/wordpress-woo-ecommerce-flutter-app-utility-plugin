<?php

function get_chat_avatar_url( $id_or_email, $args = null ) {
	$args = get_avatar_data( $id_or_email, $args );
	return $args['url'];
}

global $post;
$messages_text = get_post_meta($post->ID, 'app_complain_user_messages', true) ?? '[]';
$messages = json_decode($messages_text, true);
$user_id = get_post_meta($post->ID, 'app_complain_user_id', true);
$current_user = wp_get_current_user();

$admin_avatar = get_chat_avatar_url($current_user->ID);
$user_avatar = get_chat_avatar_url($user_id);

?>

<style>
    #chatbox_super_container .row {
        justify-content: center;
        margin: auto;
        width: 800px;
        height: 600px;
    }

    #chatbox_super_container .col-4 {
        background: #333;
    }

    #chatbox_super_container table {
        width: 100%;
    }

    #chatbox_super_container td {
        height: 40px;
        padding: 15px;
        line-height: 200%;
        color: #aaa;
        font-size: 14px;
        position: relative;
    }

    #chatbox_super_container .col-4 tr:hover {
        cursor: pointer;
        background: rgba(200, 200, 200, 0.5);
    }

    #chatbox_super_container .col-4 .fa-search {
        position: absolute;
        top: 40%;
        right: 20%;
    }

    #chatbox_super_container .box {
        display: flex;
    }

    #chatbox_super_container img {
        width: 50px;
        border-radius: 50%;
        margin-right: 10px;
    }

    #chatbox_super_container .box p {
        margin-bottom: 0;
    }

    #chatbox_super_container .box p:nth-child(1) {
        margin-bottom: 0;
        color: #fff;
    }

    #chatbox_super_container .notice {
        background: green;
        border-radius: 50%;
        padding: 2px 5px;
        color: #fff;
    }

    /* right */
    #chatbox_super_container .col-6 {
        background: #f6f6f6;
        overflow-y: auto;
    }

    #chatbox_super_container .col-6 tr:nth-child(1) {
        box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
    }

    #chatbox_super_container .name {
        box-sizing: border-box;
        display: flex;
        justify-content: space-between;
    }

    #chatbox_super_container .col-6 i {
        padding: 10px;
    }

    #chatbox_super_container .col-6 tr:nth-child(2) {
        border-bottom: 1px solid #ccc;
    }

    #chatbox_super_container .chat {
        height: 400px;
        overflow-y: auto;
    }

    #chatbox_super_container .chat img {
        width: 40px;
        height: 40px;
        margin-right: 20px;
    }

    #chatbox_super_container .dialog {
        padding: 3%;
        margin-right: 10px;
        max-width: 250px;
        border-radius: 0.5rem;
        position: relative;
        background: #ccc;
        color: black;
    }

    #chatbox_super_container .dialog::before {
        content: "";
        position: absolute;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
    }

    #chatbox_super_container .other,
    .self {
        margin-bottom: 20px;
        display: flex;
    }

    #chatbox_super_container .other .dialog::before {
        left: -5%;
        border-right: 15px solid #ccc;
    }

    #chatbox_super_container .self .dialog::before {
        right: -5%;
        border-left: 15px solid #9eea9e;
    }

    #chatbox_super_container .self {
        flex-direction: row-reverse;
    }

    #chatbox_super_container .self .dialog {
        background: #9eea9e;
        margin: 0 15px;
    }

    #chatbox_super_container .chat p {
        align-self: flex-end;
        margin-bottom: 0;
    }

    #chatbox_super_container .read {
        transform: translate(30px, 30px);
    }

    #chatbox_super_container .msg {
        height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        flex-direction: row;
    }

    #chatbox_super_container .msg input {
        background-color: #f6f6f6 !important;
        font-size: 18px
    }

    div#wpfooter {
        display: none;
    }
</style>


<div id="chatbox_super_container">
    <div class="col-6 px-0 shadow">
        <table>
            <tr>
                <td>
                    <div class="chat">

                        <?php foreach ($messages as $value) :
                            if($value['sender'] == 'client'){ ?>
                                <div class="other">
                                    <img src="<?= $user_avatar ?>">
                                    <div class="dialog"> <?= $value['message'] ?> </div>
                                    <p> <?= $value['sending_datetime'] ?> </p>
                                </div>
                            <?php } else { ?>
                                <div class="self">
                                    <img src="<?= $admin_avatar ?>">
                                    <div class="dialog"> <?= $value['message'] ?> </div>
                                    <p> <?= $value['sending_datetime'] ?> </p>
                                </div>
                            <?php } ?>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div style="display: flex; justify-content: space-between;justify-content: flex-end;" class="msg">
                        <input name="app_complain_user_messages" style="width: 100%;" type="text" placeholder="send responed" class="form-control">
                        <input  type="submit" value="Send">
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

</div>
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

<link rel="stylesheet" href="<?= WP_PLUGIN_DIR ?>/wp-wc-flutter-app-utility/admin/style/chatroom-style.css">

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
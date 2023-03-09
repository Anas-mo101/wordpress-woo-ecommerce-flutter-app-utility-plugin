<?php 

if($_POST){
    if ( isset( $_POST[ 'app_complain_init_message' ] ) && trim( $_POST[ 'app_complain_init_message' ] ) ) {
        $value = $_POST[ 'app_complain_init_message' ];

        update_option('complain_init_message_key', $value);
    }
}

$message = get_option('complain_init_message_key');

?>

<div style="margin: 30px 0">
    <h1 style="margin: auto 0;"> Complains Chatroom Settings </h1>
</div>

<form action="<?= get_permalink($page_id); ?>" method="post">

    <div style="margin: 30px 0; display: flex; gap: 20px; flex-wrap: wrap; align-items: center;">
        <p> <b>Auto Response For Complain Initialization</b> </p>
        <input name="app_complain_init_message" type="text" type="text" value="<?php echo esc_html( $message ); ?>">
    </div> 

    <input type="submit" value="Save">
</form>

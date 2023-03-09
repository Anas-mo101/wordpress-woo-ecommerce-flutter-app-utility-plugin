<?php 

if($_POST){
    if ( isset( $_POST[ 'app_ads_interval' ] ) && trim( $_POST[ 'app_ads_interval' ] ) ) {
        $value = $_POST[ 'app_ads_interval' ];
        update_option('app_ads_display_interval', $value);
    }
}

$interval = get_option('app_ads_display_interval');

?>

<div style="margin: 30px 0">
    <h1 style="margin: auto 0;"> Application Advertisings Settings </h1>
</div>

<form action="<?= get_permalink($page_id); ?>" method="post">

    <div style="margin: 30px 0; display: flex; gap: 20px; flex-wrap: wrap; align-items: center;">
        <h2> <b>Ad Interval (duration between ads in minutes):</b> </h2>
        <input name="app_ads_interval" type="number" type="text" value="<?php echo esc_html( $interval ); ?>">
    </div> 

    <input type="submit" value="Save">
</form>

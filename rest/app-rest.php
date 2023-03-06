<?php 


class AppRest{

    function __construct(){
        require 'app-cart-rest.php';
        require 'app-complains-rest.php';
        require 'app-ads-rest.php';

        new CartUtilityRestController();
        new AppComplainsRest();
        new AppAdsRest();
    }
}


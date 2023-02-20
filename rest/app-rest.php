<?php 


class AppRest{

    function __construct(){
        require 'app-cart-rest.php';

        new CartUtilityRestController();
    }
}


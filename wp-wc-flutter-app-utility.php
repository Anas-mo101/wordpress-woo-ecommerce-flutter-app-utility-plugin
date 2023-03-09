<?php

/**
 * Plugin Name:       App Utility Plugin
 * Description:       A plugin that provides nessesacary function to mobile app via rest api
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Anmo
 */

if( ! defined('ABSPATH') ){ die; }

if(!class_exists('AppUtility')){
    class AppUtility{

        function __construct(){
            $auth = new Jwt_Auth();
	        $auth->run();
            new AppRest();
            new AR();
            new Complain();
            new Ads();
        }

        function deactivate(){
            flush_rewrite_rules();
        }

        function activiate(){

            flush_rewrite_rules();
        }
    }
}


if(class_exists('AppUtility')){
    require_once 'rest/app-rest.php';
    require 'auth/includes/class-jwt-auth.php';
    require 'ar/ar.php';
    require 'complain/complain.php';
    require 'ads/ads.php';

    $remoteFileManager = new AppUtility();
    register_activation_hook( __FILE__, array($remoteFileManager, 'activiate') );
    register_deactivation_hook( __FILE__, array($remoteFileManager, 'deactivate') );
}
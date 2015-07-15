<?php
    require('config.php');
    require_once('vendor/autoload.php');

    if(isset($_POST['logout_token'])) {

        \Clef\Clef::initialize(APP_ID, APP_SECRET);
        try {
            $clef_id = \Clef\Clef::get_logout_information($_POST["logout_token"]);

            require('mysql.php');
            update_logged_out_at($clef_id, time(), $mysql);

            die(json_encode(array('success' => true)));
        } catch (Exception $e) {
           die(json_encode(array('error' => $e->getMessage())));
        }
    }
?>
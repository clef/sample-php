<?php
    require('config.php');

    if(isset($_POST['logout_token'])) {

        $postdata = http_build_query(
            array(
                'logout_token' => $_REQUEST['logout_token'],
                'app_id' => APP_ID,
                'app_secret' => APP_SECRET
            )
        );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context  = stream_context_create($opts);
        $response = file_get_contents(CLEF_BASE_URL."logout", false, $context);

        $response = json_decode($response, true);

        if (isset($response['success']) && isset($response['clef_id'])) {
            require('mysql.php');
            update_logged_out_at($response['clef_id'], time(), $mysql);
        }
    }
?>
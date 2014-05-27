<?php

require_once('config.php');

if (!session_id())
    session_start();

if (isset($_GET["code"]) && $_GET["code"] != "") {
    $code = $_GET["code"];
    $postdata = http_build_query(
        array(
            'code' => $code,
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

    // get oauth code for the handshake
    $context  = stream_context_create($opts);
    $response = file_get_contents(CLEF_BASE_URL."authorize", false, $context);

    if($response) {
        $response = json_decode($response, true);

        // if there's an error, Clef's API will report it
        if(!isset($response['error'])) {
            $access_token = $response['access_token'];

            $opts = array('http' =>
                array(
                    'method'  => 'GET'
                )
            );

            $url = CLEF_BASE_URL."info?access_token=".$access_token;

            // exchange the oauth token for the user's info
            $context  = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);
            if($response) {
                $response = json_decode($response, true);

                // again, make sure nothing went wrong
                if(!isset($response['error'])) {

                    $result = $response['info'];

                    // reset the user's session
                    if (isset($result['id'])&&($result['id']!='')) {
                        //remove all the variables in the session
                        session_unset();
                        // destroy the session
                        session_destroy();
                        if (!session_id())
                            session_start();

                        $clef_id = $result['id'];

                        $_SESSION['name']     = $result['first_name'].' '.$result['last_name'];
                        $_SESSION['email']    = $result['email'];
                        $_SESSION['user_id']  = $clef_id;
                        $_SESSION['logged_in_at'] = time();  // timestamp in unix time

                        require_once('mysql.php');

                        $user = get_user($clef_id, $mysql);
                        if (!$user) {
                            insert_user($clef_id, $result['first_name'], $mysql);
                        }

                        // send them to the member's area!
                        header("Location: members_area.php");
                    }
                } else {
                    echo "Log in with Clef failed, please try again.";
                }
            }
        } else {
            echo "Log in with Clef failed, please try again.";
        }

    } else {
        echo "Log in with Clef failed, please try again.";
    }
}
?>


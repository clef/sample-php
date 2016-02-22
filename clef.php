<?php

require_once('config.php');
require_once('vendor/autoload.php');

function validate_state($state) {
    $is_valid = isset($_SESSION['state']) && strlen($_SESSION['state']) > 0 && $_SESSION['state'] == $state;
    if (!$is_valid) {
        header('HTTP/1.0 403 Forbidden');
        echo "The state parameter didn't match what was passed in to the Clef button.";
        exit;
    } else {
        unset($_SESSION['state']);
    }
    return $is_valid;
}

if (!session_id()) {
    session_start();
}

if (isset($_GET["code"]) && $_GET["code"] != "") {
    /* validate_state($_GET["state"]); */

    $configuration = new \Clef\Configuration(array(
        "id" => APP_ID,
        "secret" => APP_SECRET,
        "keypair" => "file:///Users/jessepollak/clefy/clef/common/tests/fixtures/test.pem",
        "api_base" => "http://arya.dev:5000/api"
    ));
    \Clef\Clef::configure($configuration);

    try {
        $response = \Clef\Clef::get_login_information($_GET["code"]);
        $result = $response->info;

        // reset the user's session
        if (isset($result->id) && ($result->id != '')) {
            //remove all the variables in the session
            session_unset();
            // destroy the session
            session_destroy();
            if (!session_id())
                session_start();

            $clef_id = $result->id;

            $_SESSION['user_id']  = $clef_id;
            $_SESSION['user_public_key'] = $result->public_key->bundle;

            $payload = array(
                "nonce" => bin2hex(openssl_random_pseudo_bytes(16)),
                "clef_id" => $clef_id,
                "redirect_url" => 'http://localhost:8888/verify.php',
                "session_id" => $_REQUEST['session_id']
            );

            // We store the payload in the browser session so we can verify the nonce later
            $_SESSION['clef_payload'] = $payload;
            $_SESSION['logged_in_at'] = time();  // timestamp in unix time

            $signed_payload = \Clef\Clef::sign_login_payload($payload);
            header("Location: http://arya.dev:5000/api/v1/validate?payload=" . \Clef\Clef::encode_payload($signed_payload));
            die();

            /* require_once('mysql.php'); */

            /* $user = get_user($clef_id, $mysql); */
            /* if (!$user) { */
            /*     insert_user($clef_id, $result->first_name, $mysql); */
            /* } */

            // send them to the member's area!
            /* header("Location: members_area.php"); */
        }
    } catch (Exception $e) {
       echo "Login with Clef failed: " . $e->getMessage();
    }
}
?>


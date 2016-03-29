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
        "keypair" => "file:///Users/jackyalcine/clef/common/tests/fixtures/test.pem",
        "passphrase" => "betafinex",
        "api_base" => "http://arya.dev:5000/api"
      ));

    \Clef\Clef::configure($configuration);

    $response = \Clef\Clef::get_login_information($_GET["code"]);
    $result = $response["info"];

    // reset the user's session
    if (isset($result["id"]) && ($result["id"])){
        error_log(implode($_REQUEST));
        //remove all the variables in the session
        session_unset();
        // destroy the session
        session_destroy();
        if (!session_id())
            session_start();

        $clef_id = $result["id"];
        $_SESSION["user_id"]  = $clef_id;
        $_SESSION["user_key"] = $result["public_key"];

        $payload = array(
            "nonce" => bin2hex(openssl_random_pseudo_bytes(16)),
            "clef_id" => $clef_id,
            "redirect_url" => 'http://localhost:8888/verify-custom-action.php',
            "session_id" => $_REQUEST["session_id"],
            "type" => "withdrawal",
            "description" => "You requested to withdraw " . mt_rand(5, 50) . " BTC from your account."
        );

        // We store the payload in the browser session so we can verify the nonce later
        $_SESSION['clef_payload'] = $payload;
        $_SESSION['logged_in_at'] = time();  // timestamp in unix time

        $signed_payload = \Clef\Clef::sign_custom_payload($payload);
        header("Location: http://arya.dev:5000/api/v1/validate?payload=" . \Clef\Clef::encode_payload($signed_payload));
        die();
    }
}
?>



<?php

require_once('config.php');
require_once('vendor/autoload.php');

if (!session_id()) {
    session_start();
}

if (isset($_GET["payload"]) && $_GET["payload"] != "") {
    $configuration = new \Clef\Configuration(array(
        "id" => APP_ID,
        "secret" => APP_SECRET,
        "keypair" => "file:///Users/jessepollak/clefy/clef/common/tests/fixtures/test.pem",
        "api_base" => "http://arya.dev:5000/api"
    ));
    \Clef\Clef::configure($configuration);

    $payload_bundle = \Clef\Clef::decode_payload($_GET["payload"]);
    \Clef\Clef::verify_login_payload($payload_bundle, $_SESSION['user_public_key']);

    echo("User verified.");
    exit();
}
?>


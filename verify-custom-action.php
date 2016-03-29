<?php

require_once('config.php');
require_once('vendor/autoload.php');

error_log(print_r($_SESSION, true), 0);

$payload_str = base64_decode($_REQUEST["payload"]);
$payload_json = json_decode($payload_str);

$payload_data = $payload_json->payload_json;
$payload_sigs = $payload_json->signatures;

$user_pubkey_info = $_SESSION["user_key"];
$user_pubkey_str = $user_pubkey_info->bundle;
$user_pubkey_res = openssl_get_publickey($user_pubkey_str);

if ($user_pubkey_res) {
  $verify_result = openssl_verify(
    $payload_data,
    base64_decode($payload_sigs->user->signature),
    $user_pubkey_res,
    split("-", $payload_sigs->user->type)[1]
  );

  if ($verify_result == 1) { print "We're all good!"; }
  elseif ($verify_result == 0) { print "Gosh darn it; got a failure."; }
  elseif ($verify_result == -1) { print "An unknown error occurred."; }

} else {
  print "Got an invalid public key for the user.";
}

?>

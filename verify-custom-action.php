<?php

require_once('config.php');
require_once('vendor/autoload.php');

error_log(print_r($_SESSION, true), 0);

$payload_str = base64_decode($_REQUEST["payload"]);
$payload_json = json_decode($payload_str);

$payload_data = $payload_json->payload_json;
$payload_sigs = $payload_json->signatures;
$got_sigs_for = array_keys(get_object_vars($payload_json->signatures));

print "Got signatures for " . join(", ", $got_sigs_for) . "<br/ >";

if (in_array("user", $got_sigs_for)) {
  print "The user confirmed!";
} else {
  print "The user denied.";
}

?>

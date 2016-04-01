<?php

require_once('config.php');
require_once('vendor/autoload.php');

error_log(print_r($_SESSION, true), 0);

$payload_str = base64_decode($_REQUEST["payload"]);
$payload_json = json_decode($payload_str);

$payload_data = $payload_json->payload_json;
$payload_sigs = $payload_json->signatures;

print "Got signatures for " . join(", ", array_keys($payload_json->signatures));

?>

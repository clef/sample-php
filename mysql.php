<?php

require_once('config.php');
// initialize mysql
$mysql = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if($mysql->connect_errno > 0){
    die('Unable to connect to database [' . $mysql->connect_error . ']');
}

function get_user($id, $mysql) {
    $query = "SELECT * FROM ". DB_NAME .".users WHERE clef_id='{$id}'";

    if (!$result = $mysql->query($query)) {
        die('There was an error running the query: ' . $mysql->error);
    }
    $user = $result->fetch_assoc();
    if (!$user || sizeof($user) == 0) return false;
    return $user;
}

function insert_user($id, $name, $mysql) {
    $id = $mysql->escape_string($id);
    $name = $mysql->escape_string($name);

    $query = "INSERT INTO ". DB_NAME .".users (clef_id, name) VALUES ('{$id}', '{$name}');";
    if (!$result = $mysql->query($query)) {
        die('There was an error running the query: ' . $mysql->error);
    }

    return $result;
}

function update_logged_out_at($id, $time, $mysql) {
    $query = "UPDATE ". DB_NAME .".users SET logged_out_at={$time} WHERE clef_id='{$id}';";
    if (!$result = $mysql->query($query)) {
        die('There was an error running the query: ' . $mysql->error);
    }
    return $result;
}
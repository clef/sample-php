<?php
    require('config.php');

    session_start();

    // don't let those filthy nonmembers in here
    if(!isset($_SESSION["user_id"])) {
        header("Location: index.php");
    }

    require('mysql.php');
    $user = get_user($_SESSION['user_id'], $mysql);
    if (!$user) header("Location: index.php");

    if (isset($user['logged_out_at'])) {
        $logged_out_at = $user['logged_out_at'];
        if (!isset($_SESSION['logged_in_at']) || $_SESSION['logged_in_at'] < $logged_out_at) {
            session_destroy();
            header('Location: index.php');
        }
    }
?>

<!-- =======================================================-->
<!DOCTYPE html>
<html>
<head>
<title>PHP Sample</title>
</head>
<body>
    <div class='user-info'>
        <h2>Welcome to the exclusive member's area!  Here's your info:</h2>
        <h3>Clef ID: <?=$_SESSION["user_id"]?></h3>
        <h3>Name: <?=$_SESSION['name']?></h3>
        <h3>Email: <?=$_SESSION['email']?></h3>
    </div>
</body>
</html>


<?php
    session_start();
    $DB_USER = "root";
    $DB_PASSWORD = "root";
    $DB_HOST = "localhost";
    $DB_NAME = "clef-test";

    // don't let those filthy nonmembers in here
    if(!isset($_SESSION["user_id"])) {
        header("Location: index.php");
    } 

    $uid = $_SESSION['user_id'];

    $mysql = mysqli_connect($DB_HOST, $DB_USER, $DB_PASSWORD);
    $query = "SELECT logged_out_at FROM {$DB_NAME}.users WHERE clef_id='{$uid}';";
    
    if($response = mysqli_query($mysql, $query)) {
        $rows = mysqli_fetch_assoc($response);

        $logged_out_at = $rows['logged_out_at'];

        if(!isset($_SESSION['logged_in_at']) || $_SESSION['logged_in_at'] < $logged_out_at) { // or if the user is logged out with Clef
            session_destroy(); // log the user out on this site

            header("Location: index.php");
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


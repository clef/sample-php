<?php require_once('config.php'); ?>
<?php

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function generate_state_parameter() {
    if (isset($_SESSION['state'])) {
        return $_SESSION['state'];
    } else {
        $state = base64url_encode(openssl_random_pseudo_bytes(32));
        $_SESSION['state'] = $state;
        return $state;
    }
}

if (!session_id()) {
    session_start();
}
$state = generate_state_parameter();

?>
<!-- =======================================================-->
<!DOCTYPE html>
<html>
    <head>
        <title>PHP Sample</title>
    </head>
    <body>
        <script src='http://clef.tk/v3/clef.js'
                class='clef-button'
                data-app-id='<?php echo APP_ID ?>'
                data-redirect-url='http://apricot-tart-62163.herokuapp.com/clef-confirm-action.php'
                data-state='<?php echo $state ?>'>
        </script>
    </body>
</html>

# Clef + PHP
![license:mit](https://img.shields.io/badge/license-mit-blue.svg)

## Getting started
Clef is secure two-factor auth without passwords. With the wave of their phone, users can log in to your site — it's like :sparkles: magic :sparkles:! 

Get started in three easy steps:
* Download the [iOS](https://itunes.apple.com/us/app/clef/id558706348) or [Android](https://play.google.com/store/apps/details?id=io.clef&hl=en) app on your phone 
* Sign up for a Clef developer account at [https://www.getclef.com/developer](https://www.getclef.com/developer) and create an application. That's where you'll get your API credentials (`app_id` and `app_secret`) and manage settings for your Clef integration.
* Follow the directions below to integrate Clef into your site's log in flow. 

## Usage
We'll walk you through the full Clef integration with PHP below. You can also run this sample app [locally](#running-this-sample-app).

### Adding the Clef button

The Clef button is the entry point into the Clef experience. Adding it to your site is as easy as dropping a `script` tag wherever you want the button to show up. 

Set the `data-redirect-url` to the URL in your app where you will complete the OAuth handshake. You'll also want to set `data-state` to an unguessable random string. <br>

```javascript

<script type='text/javascript' 
    class='clef-button'
    src='https://clef.io/v3/clef.js' 
    data-app-id='<?php echo APP_ID ?>' 
    data-redirect-url='http://localhost:8888/clef.php'
    data-state='<?php echo $state ?>'>
</script>
```
*See the code in [action](/index.php#L27-L32) or read more [here](http://docs.getclef.com/v1.0/docs/adding-the-clef-button).*<br>

### Completing the OAuth handshake
Once you've set up the Clef button, you need to be able to handle the OAuth handshake. This is what lets you retrieve information about a user after they authenticate with Clef. The easiest way to do this is to use the [Clef API wrapper for PHP](https://github.com/clef/clef-php), which you can install via `Composer` or by manually including the files.

To use it, pass your `app_id` and `app_secret` to the initializer:

    \Clef\Clef::initialize(APP_ID, APP_SECRET);


Then at the route you created for the OAuth callback, access the `code` URL parameter and exchange it for user information. 

Before exchanging the `code` for user information, you first need to verify the `state` parameter sent to the callback to make sure it's the same one as the one you set in the button. (You can find implementations of the <code><a href="/clef.php#L5-L14" target="_blank">validate_state</a></code> and <code><a href="/index.php#L8-L19" target="_blank">generate_state_parameter</a></code> functions in `clef.php` and `index.php`.) 

```php
<?php
require_once('config.php');
require_once('vendor/autoload.php');

if (!session_id()) {
    session_start();
}

if (isset($_GET["code"]) && $_GET["code"] != "") {
    validate_state($_GET["state"]);
    \Clef\Clef::initialize(APP_ID, APP_SECRET);
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
            $_SESSION['name']     = $result->first_name .' '. $result->last_name;
            $_SESSION['email']    = $result->email;
            $_SESSION['user_id']  = $clef_id;
            $_SESSION['logged_in_at'] = time();  // timestamp in unix time
            require_once('mysql.php');
            $user = get_user($clef_id, $mysql);
            if (!$user) {
                insert_user($clef_id, $result->first_name, $mysql);
            }
            // send them to the member's area!
            header("Location: members_area.php");
        }
    } catch (Exception $e) {
       echo "Login with Clef failed: " . $e->getMessage();
    }
}
?>
```
*See the code in [action](https://github.com/clef/sample-php/blob/master/clef.php#L24-L27) or read more [here](http://docs.getclef.com/v1.0/docs/authenticating-users).*<br>

### Logging users out 
Logout with Clef allows users to have complete control over their authentication sessions. Instead of users individually logging out of each site, they log out once with their phone and are automatically logged out of every site they used Clef to log into.

To make this work, you need to [set up](#setting-up-timestamped-logins) timestamped logins, handle the [logout webhook](#handling-the-logout-webhook) and [compare the two](#checking-timestamped-logins) every time you load the user from your database. 

#### Setting up timestamped logins
Setting up timestamped logins is easy. You just add a timestamp to the session everywhere in your application code that you do the Clef OAuth handshake:

```php
$_SESSION['logged_in_at'] = time();
```

*See the code in [action](/clef.php#L83) or read more [here](http://docs.getclef.com/v1.0/docs/checking-timestamped-logins)*

#### Handling the logout webhook
Every time a user logs out of Clef on their phone, Clef will send a `POST` to your logout hook with a `logout_token`. You can exchange this for a Clef ID:

```php
<?php
    require('config.php');
    require_once('vendor/autoload.php');
    
    if(isset($_POST['logout_token'])) {
        \Clef\Clef::initialize(APP_ID, APP_SECRET);
        try {
            $clef_id = \Clef\Clef::get_logout_information($_POST["logout_token"]);
            require('mysql.php');
            update_logged_out_at($clef_id, time(), $mysql);
            die(json_encode(array('success' => true)));
        } catch (Exception $e) {
           die(json_encode(array('error' => $e->getMessage())));
        }
    }
?>
```
*See the code in [action](/logout_hook.php#L9) or read more [here](http://docs.getclef.com/v1.0/docs/handling-the-logout-webhook).*<br>

You'll want to make sure you have a `logged_out_at` attribute on your `User` model. Also, don't forget to specify this URL as the `logout_hook` in your Clef application settings so Clef knows where to notify you.

#### Checking timestamped logins
Every time you load user information from the database, you'll want to compare the `logged_in_at` session variable to the user `logged_out_at` field. If `logged_out_at` is after `logged_in_at`, the user's session is no longer valid and they should be logged out of your application.

```php
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
```
*See the code in action [here](/members_area.php#L1-L22) or read more [here](http://docs.getclef.com/v1.0/docs/checking-timestamped-logins)*

## Running this sample app 
To run this sample app, clone the repo:

```
$ git clone https://github.com/clef/sample-php.git
```

Then configure your local database and run on localhost:8000.
```
# create clef database with MySQL command line
mysql > CREATE DATABASE clef_test;
mysql > USE clef_test;
mysql > CREATE TABLE users (clef_id VARCHAR(32), name VARCHAR(64));
mysql > exit;

$ php -S localhost:8000
```

## Documentation
You can find our most up-to-date documentation at [http://docs.getclef.com](http://docs.getclef.com/). It covers additional topics like customizing the Clef button and testing your integration.

## Support
Have a question or just want to chat? Send an email to [support@getclef.com](mailto: support@getclef.com).

We're always around, but we do an official Q&A every Friday from 10am to noon PST :) — would love to see you there! 

## About 
Clef is an Oakland-based company building a better way to log in online. We power logins on more than 80,000 websites and are building a beautiful experience and inclusive culture. Read more about our [values](https://getclef.com/values), and if you like what you see, come [work with us](https://getclef.com/jobs)!






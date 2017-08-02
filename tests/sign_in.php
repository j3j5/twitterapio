<?php

require dirname(__DIR__) . '/vendor/autoload.php'; // Autoload files using Composer autoload

use j3j5\TwitterApio;

session_start();

$api = new TwitterApio();
$current_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$current_url = mb_substr($current_url, 0, mb_strpos($current_url, '?') - 1);
if (isset($_REQUEST['function'])) {
    if ($_REQUEST['function'] == 'login') {
        // your SIGN IN WITH TWITTER button should point to this route
        $sign_in_twitter = true;
        $force_login = false;
        $callback_url = $current_url . '?function=callback';
        // Make sure we make this request w/o tokens, overwrite the default values in case of login.
        $api->reconfigure(['token' => '', 'secret' => '']);
        $token = $api->getRequestToken($callback_url);
        if (isset($token['oauth_token_secret'])) {
            $url = $api->getAuthorizeUrl($token, $sign_in_twitter, $force_login);

            $_SESSION['oauth_state'] =  'start';
            $_SESSION['oauth_request_token'] = $token['oauth_token'];
            $_SESSION['oauth_request_token_secret'] = $token['oauth_token_secret'];

            header("Location: $url");
            exit;
        }
        header("Location: $current_url?function=error");
        exit;
    } elseif ($_REQUEST['function'] == 'callback') {
        // You should set this route on your Twitter Application settings as the callback
        // https://apps.twitter.com/app/YOUR-APP-ID/settings
        if (isset($_SESSION['oauth_request_token'])) {
            $request_token = [
                'token' => $_SESSION['oauth_request_token'],
                'secret' => $_SESSION['oauth_request_token_secret'],
            ];

            $api->reconfigure($request_token);

            $oauth_verifier = false;
            if (isset($_REQUEST['oauth_verifier'])) {
                $oauth_verifier = $_REQUEST['oauth_verifier'];
            }

            // get_access_token() will reset the token for you
            $token = $api->getAccessToken($oauth_verifier);
            if (!isset($token['oauth_token_secret'])) {
                header("Location: $current_url?function=error&error=" . urlencode(print_r($token, true)));
                exit;
            }
        }

        $credentials = $api->get('account/verify_credentials');

        if (is_array($credentials) && !isset($credentials['errors'])) {
            // $credentials contains the Twitter user object with all the info about the user.
            // Add here your own user logic, store profiles, create new users on your tables...you name it!
            // Typically you'll want to store at least, user id, name and access tokens
            // if you want to be able to call the API on behalf of your users.

            // This is also the moment to log in your users
            echo("Congrats! You've successfully signed in!<br/>");
            echo('This is your user object:<br/>');
            print_r($credentials);
            exit;
        }
        var_dump('Crab! Something went wrong while signing you up!');
        exit;
    } elseif ($_REQUEST['function'] == 'error') {
        // Handle the possible error
        var_dump('error!!');
        if (isset($_REQUEST['error'])) {
            var_dump($_REQUEST['error']);
        }
        exit;
    }
}

// Default landing page
$html = '
	<html>
		<head>
		<title>Sign in with Twitter</title>
		</head>
		<body>
		<a href="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '?function=login"><div>Sign in with Twitter</div><a>
		</body>
	</html>
';
echo $html;
exit;

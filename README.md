TwitterApio
============

TwitterApio is a small wrapper to use Twitter's API from you PHP app.

## Installation

Add `j3j5/twitterapio` to `composer.json`.
```
"j3j5/twitterapio": "dev-master"
```

Run `composer update` to pull down the latest version of Twitter.

## Configuration

Open up the `config.php` included with the package and set there all your consumer keys and tokens.

Alternatively, you can set your own config array and use it to overwrite the config file when you create the first instance of TwitterApio. The twitter
config must be as follows:

```php
$twitter_settings = array(
	'consumer_key'		=> 'YOUR_CONSUMER_KEY',
	'consumer_secret'	=> 'YOUR_CONSUMER_SECRET',
	'token'				=> 'A_USER_TOKEN',
	'secret'			=> 'A_USER_TOKEN_SECRET',
);

$api = new TwitterApio($twitter_settings);
```

## Use

Once you have created your own instance of the library, you can use any of the public methods to request from Twitter's API.

If you decide to set your tokens from your own app instead of from the config file:
```php
$twitter_settings = array(
	'consumer_key'		=> 'YOUR_CONSUMER_KEY',
	'consumer_secret'	=> 'YOUR_CONSUMER_SECRET',
	'token'				=> 'A_USER_TOKEN',
	'secret'			=> 'A_USER_TOKEN_SECRET',
);

$api = new TwitterApio($twitter_settings);

// Now you can do all type of requests
$credentials = $api->get('account/verify_credentials');
$tweet = $api->post('statuses/update', array('status' => 'Testing TwitterApio!!!'));
```

Or the more interesting ones...the ones with iterators!!
```php
$username = "masaenfurecida";
$tweets = array();
// getTimeline() can be used with any endpoint that returns a timeline (like statuses/mentions_timeline, statuses/home_timeline)
foreach($api->getTimeline('statuses/user_timeline', array('screen_name' => $username, 'count' => 200)) as $page) {
	if(is_array($page) ) {
		$tweets = array_merge($tweets, $page);
	}
}

$followers = array();
foreach($api->getFollowers(array('screen_name' => $username, 'count' => 5000)) as $page) {
	if(is_array($page) ) {
		$followers = array_merge($followers, $page);
	}
}

$friends = array();
foreach($api->getFriends(array('screen_name' => $username, 'count' => 5000)) as $page) {
	if(is_array($page) ) {
		$friends = array_merge($friends, $page);
	}
}
```

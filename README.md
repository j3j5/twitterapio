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

Alternatively, you can set your own config array and use it to overwrite the config file. The twitter
config must be as follows:

```php
$twitter_settings = array(
	'consumer_key'		=> 'YOUR_CONSUMER_KEY',
	'consumer_secret'	=> 'YOUR_CONSUMER_SECRET',
	'token'				=> 'A_USER_TOKEN',
	'secret'			=> 'A_USER_TOKEN_SECRET',
);
```

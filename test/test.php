<?php


require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload


use j3j5\TwitterApio;

$api = new TwitterApio();

$result = $api->get('account/verify_credentials');

if(isset($result['id'])) {
	echo "Your user @{$result['screen_name']} has ID '{$result['id']}' and name '{$result['name']}'.". PHP_EOL;
} else {
	$config_path = dirname(__DIR__) . "/src/j3j5/config.php";
	echo "An error occured, did you fill the config.php file?". PHP_EOL;
	echo "It should be on $config_path" . PHP_EOL;
}

return;

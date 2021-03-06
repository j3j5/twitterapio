<?php


require dirname(__DIR__) . '/vendor/autoload.php'; // Autoload files using Composer autoload

use j3j5\TwitterApio;

$api = new TwitterApio();

$all_tweets = [];
$i = 1;
$max_pages = 2;
$username = '#streetart';
$args = ['count' => 10, 'include_rts' => false];
foreach ($api->search($username, $args) as $tweets) {
    echo "Retrieving page $i ";
    if (!empty($tweets) && is_array($tweets)) {
        echo 'with ' . count($tweets) . ' tweets.' . PHP_EOL;
        $all_tweets = array_merge($all_tweets, $tweets);
    } else {
        echo '(empty).' . PHP_EOL;
    }

    if ($i == $max_pages) {
        break;
    }
    $i++;
}
echo PHP_EOL . PHP_EOL;
echo count($all_tweets) . ' retrieved in total from the API.' . PHP_EOL;

foreach ($all_tweets as $tweet) {
    echo "{$tweet['created_at']}: {$tweet['text']}" . PHP_EOL;
}
return;

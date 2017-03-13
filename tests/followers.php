<?php


require dirname(__DIR__) . '/vendor/autoload.php'; // Autoload files using Composer autoload

use j3j5\TwitterApio;

$api = new TwitterApio();

$followers_ids = [];
$i = 1;
$max_pages = 5;
$username = 'twitterapi';
foreach ($api->get_followers(['screen_name' => $username, 'count' => 5]) as $followers) {
    echo "Retrieving page $i ";
    if (!empty($followers) && is_array($followers)) {
        echo 'with ' . count($followers) . ' followers.' . PHP_EOL;
        $followers_ids = array_merge($followers_ids, $followers);
    } else {
        echo '(empty).' . PHP_EOL;
    }

    if ($i == $max_pages) {
        break;
    }
    $i++;
}
echo PHP_EOL . PHP_EOL;
echo count($followers_ids) . ' retrieved in total from the API.' . PHP_EOL;
foreach ($followers_ids as $id) {
    echo $id . PHP_EOL;
}

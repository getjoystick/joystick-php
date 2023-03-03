<?php

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/../helpers/get-content-ids.php');

$filesystemAdapter = new Local(__DIR__);
$filesystem = new Filesystem($filesystemAdapter);

$filesystemCache = new FilesystemCachePool($filesystem);

$config = \Joystick\ClientConfig::create()
    ->setApiKey(getenv('JOYSTICK_API_KEY'))
    ->setSemVer("0.0.1")
    ->setCacheExpirationSeconds(10)
    ->setCache($filesystemCache);

$client = \Joystick\Client::create($config);

$numberOfCalls = 1;
$getContents = function () use ($client, &$numberOfCalls) {
    $before = microtime(true);
    $client->getContents(getContentIdsFromEnv(), [
        'serialized' => false,
        'fullResponse' => true,
    ]);
    $after = microtime(true);
    $roundExecutionTime = number_format($after - $before, 2);
    echo "Request #$numberOfCalls is finished in $roundExecutionTime sec\n";
    $numberOfCalls++;
};

for ($i = 0; $i < 5; $i++) {
    $getContents();
}

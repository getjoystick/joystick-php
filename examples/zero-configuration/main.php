<?php

// Uses library from the parent folder, not from the packagist or repository
require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../helpers/get-content-ids.php');

$config = \Joystick\ClientConfig::create()
    ->setApiKey('JOYSTICK_API_KEY')
    ->setSemVer('0.0.1')
    ->setParams(['b' => 'b value', 'a' => 'a value'])
    ->setUserId('USER-ID');

$client = \Joystick\Client::create($config);

$getContents = function () use ($client) {
    return $client->getContents(getContentIdsFromEnv(), ['serialized' => true, 'fullResponse' => true]);
};

echo json_encode($getContents(), JSON_PRETTY_PRINT);

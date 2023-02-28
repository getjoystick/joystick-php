<?php
// Uses library from the parent folder, not from the packagist or repository
require(__DIR__ . '/../vendor/autoload.php');

$config = \Joystick\ClientConfig::create()
    ->setApiKey(getenv('JOYSTICK_API_KEY'));

$client = \Joystick\Client::create($config);

// Specify here your `contentIds` to run it locally
$result = $client->getContents(["test-my-php-library", "sample-second"], [
    'serialized' => true,
    'fullResponse' => true,
]);

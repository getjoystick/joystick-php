<?php
// Uses library from the parent folder, not from the packagist or repository
require(__DIR__ . '/../vendor/autoload.php');

$config = \Joystick\ClientConfig::create()
    ->setApiKey(getenv('JOYSTICK_API_KEY'))
    ->setExpiration(3); 

$client = \Joystick\Client::create($config);


$getContents = function ()  use ($client) {
    // Specify here your `contentIds` to run it locally
    return $client->getContents(["test-my-php-library", "sample-second"], [
        'serialized' => false,
        'fullResponse' => true,
    ]);
};

echo json_encode($getContents(), JSON_PRETTY_PRINT);

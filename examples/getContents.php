<?php

require(__DIR__ . '/../vendor/autoload.php');

$config = \Joystick\ClientConfig::create()
    ->setApiKey(getenv('JOYSTICK_API_KEY'))
    ->setSemVer("0.0.1");
$client = \Joystick\Client::create($config);

$result = $client->getContents(["test-my-php-library", "sample-second"], [
    'serialized' => true,
    'fullResponse' => true,
]);


var_dump($result);
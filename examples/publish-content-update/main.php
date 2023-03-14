<?php

// Uses library from the parent folder, not from the packagist or repository
require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../helpers/get-content-ids.php');

$config = \Joystick\ClientConfig::create()
    ->setApiKey(getenv('JOYSTICK_API_KEY'));


$client = \Joystick\Client::create($config);
$client->publishContentUpdate('sample-second', [
    'description' => 'Asd asd!',
    'content' => new stdClass()// ['hello' => 'Some new content for this sample'],
]);

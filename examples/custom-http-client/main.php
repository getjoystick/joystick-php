<?php

declare(strict_types=1);

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/PerformanceMeasurementClient.php');
require(__DIR__ . '/../helpers/get-content-ids.php');

$http_client_autodiscover = getenv('AUTODISCOVER') === 'true';
$http_client_timeout = getenv('TIMEOUT');
$http_client_measure_performance = getenv('MEASURE_PERFORMANCE') === 'true';

if ($http_client_autodiscover && ($http_client_timeout || $http_client_measure_performance)) {
    throw new LogicException('It is not possible to set timeout on autodiscovered http client');
}

$config = \Joystick\ClientConfig::create()
    ->setApiKey(getenv('JOYSTICK_API_KEY'))
    ->setSemVer("0.0.1");

if (!$http_client_autodiscover) {
    $httpClient = HttpClient::create(['timeout' => $http_client_timeout ?: 5]);

    $symfonyHttpClient = new Psr18Client($httpClient);

    $psr18Client = $http_client_measure_performance
        ? new PerformanceMeasurementClient($symfonyHttpClient)
        : $symfonyHttpClient;

    $config->setHttpClient($psr18Client);
}

$client = \Joystick\Client::create($config);

$fetchedData = $client->getContents(getContentIdsFromEnv(), [
    'serialized' => true,
    'fullResponse' => true,
]);

echo json_encode($fetchedData, JSON_PRETTY_PRINT);

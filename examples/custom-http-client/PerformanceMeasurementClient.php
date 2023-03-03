<?php

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;


class PerformanceMeasurementClient implements ClientInterface
{
    private $httpClient;

    public function __construct(Psr18Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $before = microtime(true);
        $result = $this->httpClient->sendRequest($request);
        $after = microtime(true);
        $secondsToMakeRequest = number_format($after - $before, 2);
        $requestUri = $request->getUri();
        echo "It takes $secondsToMakeRequest seconds to make request to $requestUri\n\n";
        return $result;
    }

    public function __call($name, $arguments)
    {
        $instance = $this->httpClient;
        return call_user_func_array(
            array($instance, $name),
            $arguments
        );
    }
}

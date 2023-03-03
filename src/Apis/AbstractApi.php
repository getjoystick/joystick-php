<?php

namespace Joystick\Apis;

use Joystick\ClientConfig;
use Joystick\ClientServices;
use Joystick\Exceptions\BadRequestException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

abstract class AbstractApi
{
    /**
     * @readonly
     * @var ClientConfig
     */
    protected $config;

    /**
     * @readonly
     * @var ClientServices
     */
    protected $clientServices;

    private function __construct(ClientConfig $config, ClientServices $clientServices)
    {
        $this->config = $config;
        $this->clientServices = $clientServices;
    }

    public static function create(ClientConfig $config, ClientServices $clientServices): self
    {
        return new static($config, $clientServices);
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     * @param $body
     * @return mixed
     * @throws ClientExceptionInterface|\RuntimeException|BadRequestException
     */
    protected function makeJoystickRequest(string $httpMethod, string $uri, $body)
    {
        $request = $this->config->getRequestFactory()
            ->createRequest(
                $httpMethod,
                $uri
            )
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('x-api-key', $this->config->getApiKey())
            ->withBody($this->config->getStreamFactory()->createStream(json_encode($body)));

        $response = $this->config->getHttpClient()->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw $this->mapHttpResponseToException($response);
        }

        return json_decode((string)$response->getBody(), true);
    }


    /**
     * @param ResponseInterface $response
     * @return BadRequestException|\RuntimeException
     */
    private function mapHttpResponseToException(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();

        switch ($statusCode) {
            case 400:
                return new BadRequestException((string)$response->getBody());
            default:
                return new \RuntimeException(
                    "Joystick returned status code $statusCode (body: {$response->getBody()})"
                );
        }
    }

    /**
     * @return CacheInterface
     */
    protected function getCache(): CacheInterface
    {
        return $this->config->getCache();
    }
}

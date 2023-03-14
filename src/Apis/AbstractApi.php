<?php

declare(strict_types=1);

namespace Joystick\Apis;

use Joystick\Exceptions;
use Joystick\ClientConfig;
use Joystick\ClientServices;
use Joystick\Exceptions\Api\Http\BadRequest;
use Joystick\Exceptions\Api\Http\ServerError;
use Joystick\Exceptions\Api\Http\UnknownError;
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

    protected function __construct(ClientConfig $config, ClientServices $clientServices)
    {
        $this->config = $config;
        $this->clientServices = $clientServices;
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     * @param mixed[] $body
     * @return mixed
     * @throws ClientExceptionInterface|\RuntimeException|BadRequest
     */
    protected function makeJoystickRequest(string $httpMethod, string $uri, $body)
    {
        $apiKey = $this->config->getApiKey();
        $jsonEncodedBody = json_encode($body);

        assert($apiKey !== null, 'API key should be present');
        assert(
            json_last_error() === JSON_ERROR_NONE && $jsonEncodedBody !== false,
            'Body to Joystick API is not JSON encodable'
        );
        $request = $this->config->getRequestFactory()
            ->createRequest(
                $httpMethod,
                $uri
            )
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('x-api-key', $apiKey)
            ->withBody($this->config->getStreamFactory()->createStream($jsonEncodedBody));

        $response = $this->config->getHttpClient()->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw $this->mapHttpResponseToException($response);
        }

        $decodedJson = json_decode((string)$response->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exceptions\Api\Exception('Incorrect JSON was returned from Joystick API');
        }

        return $decodedJson;
    }


    /**
     * @param ResponseInterface $response
     * @return BadRequest|\RuntimeException
     */
    private function mapHttpResponseToException(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();

        $errorMessage = "Joystick returned status code $statusCode (body: {$response->getBody()})";
        if ($statusCode >= 400 && $statusCode < 500) {
            return new BadRequest($errorMessage);
        } elseif ($statusCode >= 500) {
            return new ServerError($errorMessage);
        } else {
            return new UnknownError($errorMessage);
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

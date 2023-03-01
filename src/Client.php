<?php

declare(strict_types=1);

namespace Joystick;

use Assert\Assert;
use Joystick\CacheKeyBuilder;
use Joystick\Exceptions\BadRequestException;
use Psr\Http\Message\ResponseInterface;
use stdClass;

class Client
{
    /**
     * @var ClientConfig
     */
    private $config;

    /**
     * @var ClientServices
     */
    private $clientServices;

    private function __construct()
    {
    }

    public static function create(ClientConfig $config, ClientServices $services = null): self
    {
        // Validation
        Assert::that($config->getApiKey(), 'API key')->notEmpty();

        // Instantiation
        $client = new static();
        $client->config = clone $config;
        $client->clientServices = !$services ? new ClientServices($config) : $services;

        return $client;
    }

    /**
     * Getting Multiple Pieces of Content via API:
     * https://docs.getjoystick.com/api-reference-combine/
     *
     *
     * @param string[] $contentIds  List of content identifiers
     * @param array{refresh: boolean, serialized: boolean, fullResponse: boolean} $options
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\Http\Client\RequestExceptionInterface
     * @throws \Psr\Http\Client\NetworkExceptionInterface
     */
    public function getContents(array $contentIds, array $options = [])
    {
        $this->validateGetContents($contentIds, $options);

        $contentIdsSorted =  array_merge([], $contentIds);
        sort($contentIdsSorted);
        $cacheKey = $this->clientServices->getCacheKeyBuilder()->build([
            $contentIdsSorted,
            $options['serialized'] ?? null,
            $options['fullResponse'] ?? null,
        ]);

        if (empty($options['refresh'])) {
            if ($cachedResult = $this->getCache()->get($cacheKey)) {
                return $cachedResult;
            }
        }

        $requestQueryParams =  array_merge(
            [
                'c' => json_encode($contentIds),
                'dynamic' => 'true',
            ],
            !empty($options['serialized']) ? ['responseType' =>  'serialized'] : []
        );

        $requestQueryParamsSerialized = http_build_query($requestQueryParams);

        $requestBody = array_merge(
            [
                'u' => $this->config->getUserId() ?? '',
                'p' => $this->config->getParams() ?? new stdClass(),
            ],
            $this->config->getSemVer() ? ['v' => $this->config->getSemVer()] : []
        );

        $request = $this->config->getRequestFactory()
            ->createRequest(
                'GET',
                'https://api.getjoystick.com/api/v1/combine/?' . $requestQueryParamsSerialized
            )
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('x-api-key', $this->config->getApiKey())
            ->withBody($this->config->getStreamFactory()->createStream(json_encode($requestBody)));

        $response = $this->config->getHttpClient()->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw $this->mapHttpResponseToException($response);
        }

        $result = json_decode((string)$response->getBody(), true);
        $this->getCache()->set($cacheKey, $result, $this->config->getExpiration());

        return $result;
    }


    private function validateGetContents(array $contentIds, array $options = [])
    {
        $assertion = Assert::lazy()
            ->that($contentIds, 'contentIds', 'contentIds')->minCount(1)->all()->string();
        if (isset($options['refresh'])) {
            $assertion->that($options['refresh'], 'refresh')->boolean();
        }
        if (isset($options['serialized'])) {
            $assertion->that($options['serialized'], 'serialized')->boolean();
        }
        if (isset($options['fullResponse'])) {
            $assertion->that($options['fullResponse'], 'fullResponse')->boolean();
        }
        $assertion->verifyNow();
    }

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

    public function clearCache(): bool
    {
        return $this->getCache()->clear();
    }

    private function getCache()
    {
        return $this->config->getCache();
    }
}

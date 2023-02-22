<?php

declare(strict_types=1);

namespace Joystick;

use Assert\Assert;
use Joystick\Exceptions\MultipleContentApi;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

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
        $client->clientServices = !$services ? ClientServices::create($client->config) : $services;

        return $client;
    }

    /**
     * Getting Multiple Pieces of Content via API:
     * https://docs.getjoystick.com/api-reference-combine/
     *
     * @param string[] $contentIds List of content identifiers
     * @param array{refresh: boolean, serialized: boolean, fullResponse: boolean} $options
     *
     * @return array. Keys are the `contentIds`. When `fullResponse` is `true`, the value will be raw response from API,
     *                when `false` – your content.
     *
     * @throws MultipleContentApi if any error happens with provided content ids.
     * e.g. if content id "myConfig01" is correct, but "myConfi02" is misspelled
     * @throws ClientExceptionInterface
     * @throws RequestExceptionInterface
     * @throws NetworkExceptionInterface
     * @throws InvalidArgumentException
     */
    public function getContents(array $contentIds, array $options = [])
    {
        return $this->clientServices->getMultipleContentApi()->getContents($contentIds, $options);
    }

    /**
     * Getting single piece of Content via API
     * @see getContents
     */
    public function getContent(string $contentId, array $options = [])
    {
        return $this->clientServices->getMultipleContentApi()->getContents([$contentId], $options)[$contentId];
    }

    /**
     * @param string $contentId
     * @param array{description: string, content: mixed, dynamicContentMap: array} $params
     * @return void
     * @throws ClientExceptionInterface
     */
    public function publishContentUpdate(string $contentId, array $params): void
    {
        $this->clientServices->getSingleContentApi()->publishContentUpdate($contentId, $params);
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

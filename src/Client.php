<?php

namespace Joystick;

use Assert\Assert;

use stdClass;

class Client
{
    private ClientConfig $config;

    private function __construct()
    {
    }

    public static function create(ClientConfig $config)
    {
        $client = new static();
        $client->config = $config;

        return $client;
    }

    public function getContents(array $contentIds, array $options = [])
    {
        $assertion = Assert::lazy()
            ->that($this->config->getApiKey())->notEmpty()
            ->that($contentIds, 'contentIds')->all()->string();
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


        $requestQueryParams =  [
            'c' => json_encode($contentIds),
            'dynamic' => 'true',
            ...!empty($options['serialized']) ? ['responseType' =>  'serialized'] : []
        ];
        $requestQueryParamsSerialized = http_build_query($requestQueryParams, '', null);
        $requestBody = [
            'u' => $this->config->getUserId() ?? '',
            'p' => $this->config->getParams() ?? new stdClass,
            ...$this->config->getSemVer() ? ['v' => $this->config->getSemVer()] : [],
        ];
        $request = $this->config->getRequestFactory()
            ->createRequest('GET', 'https://api.getjoystick.com/api/v1/combine/?' . $requestQueryParamsSerialized)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('x-api-key', $this->config->getApiKey())
            ->withBody($this->config->getStreamFactory()->createStream(json_encode($requestBody)));

        $response = $this->config->getHttpClient()->sendRequest($request);

        if (($statusCode = $response->getStatusCode()) !== 200) {
            // TODO: Make better exceptions handling
            throw new \RuntimeException("Joystick returned status code $statusCode (body: {$response->getBody()})");
        }

        return $response;
    }
}

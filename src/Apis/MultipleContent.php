<?php

declare(strict_types=1);

namespace Joystick\Apis;

use Assert\Assert;
use Joystick\ClientConfig;
use Joystick\ClientServices;
use Joystick\Exceptions\Api\MultipleContentApi;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

class MultipleContent extends AbstractApi
{
    /**
     * @param ClientConfig $config
     * @param ClientServices $clientServices
     * @return self
     */
    public static function create(ClientConfig $config, ClientServices $clientServices): MultipleContent
    {
        return new self($config, $clientServices);
    }


    /**
     * Getting Multiple Pieces of Content via API:
     * https://docs.getjoystick.com/api-reference-combine/
     *
     *
     * @param string[] $contentIds List of content identifiers
     * @param array{refresh?: boolean, serialized?: boolean, fullResponse?: boolean} $options
     *
     * @return array<string, mixed>
     *
     * @throws ClientExceptionInterface
     * @throws RequestExceptionInterface
     * @throws NetworkExceptionInterface
     * @throws InvalidArgumentException
     */
    public function getContents(array $contentIds, array $options = []): array
    {
        $this->validateSignature($contentIds, $options);
        $normalizedOptions = $this->normalizeOptions($options);

        $cacheKey = $this->buildCacheKey($contentIds, $normalizedOptions);

        if (!$normalizedOptions['refresh']) {
            if ($cachedResult = $this->getCache()->get($cacheKey)) {
                assert(is_array($cachedResult));
                return $cachedResult;
            }
        }

        $requestQueryParams = array_merge(
            [
                'c' => json_encode($contentIds),
                'dynamic' => 'true',
            ],
            $normalizedOptions['serialized'] ? ['responseType' => 'serialized'] : []
        );

        $requestQueryParamsSerialized = http_build_query($requestQueryParams);

        $requestBody = array_merge(
            [
                'u' => $this->config->getUserId() ?? '',
                'p' => $this->config->getParams() ?? new \stdClass(),
            ],
            $this->config->getSemVer() ? ['v' => $this->config->getSemVer()] : []
        );

        $decodedResponse = $this->makeJoystickRequest(
            'POST',
            'https://api.getjoystick.com/api/v1/combine/?' . $requestQueryParamsSerialized,
            $requestBody
        );

        assert(is_array($decodedResponse));

        $this->validateGetContentsResponse($decodedResponse);

        $processedResponse = $decodedResponse;
        if (!$normalizedOptions['fullResponse']) {
            $processedResponse = $this->mapGetContentsResponse($processedResponse);
        }

        $this->getCache()->set($cacheKey, $processedResponse, $this->config->getCacheExpirationSeconds());

        return $processedResponse;
    }

    /**
     * @param string[] $contentIds
     * @param array<string, mixed> $normalizedOptions
     * @return string
     */
    private function buildCacheKey(array $contentIds, array $normalizedOptions): string
    {
        $contentIdsSorted = array_merge([], $contentIds);
        sort($contentIdsSorted);
        return $this->clientServices->getCacheKeyBuilder()->build([
            $contentIdsSorted,
            $normalizedOptions['serialized'],
            $normalizedOptions['fullResponse'],
        ]);
    }

    /**
     * @param array{refresh?: boolean, serialized?: boolean, fullResponse?: boolean} $options
     * @return array{refresh: boolean | null, serialized: boolean, fullResponse: boolean | null}
     */
    private function normalizeOptions(array $options): array
    {
        $normalizedOptions = [];
        $normalizedOptions['refresh'] = $options['refresh'] ?? null;
        $normalizedOptions['serialized'] = $options['serialized'] ?? $this->config->getSerialized();
        $normalizedOptions['fullResponse'] = $options['fullResponse'] ?? null;

        return $normalizedOptions;
    }

    /**
     * @param array<string, array{data: mixed}> $decodedResponse
     * @return array<string, mixed>
     */
    private function mapGetContentsResponse(array $decodedResponse): array
    {
        return array_map(function ($content) {
            return $content['data'];
        }, $decodedResponse);
    }

    /**
     * @param string[] $contentIds
     * @param array{refresh?: boolean, serialized?: boolean, fullResponse?: boolean} $options
     * @return void
     *
     * @throws \Assert\InvalidArgumentException
     */
    private function validateSignature(array $contentIds, array $options = [])
    {
        $assertion = Assert::lazy()
            ->that($contentIds, 'contentIds', 'contentIds')->minCount(1)->all()->string()->notEmpty();
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

    /**
     * @param array<string, string|mixed[]> $decodedResponse
     * @return void
     */
    private function validateGetContentsResponse($decodedResponse)
    {
        $errors = [];
        foreach ($decodedResponse as $contentId => $content) {
            // String in the response of the Multiple Content API === error in the provided data
            if (is_string($content)) {
                $errors[] = $content;
            }
        }
        if (!empty($errors)) {
            throw MultipleContentApi::create($errors);
        }
    }
}

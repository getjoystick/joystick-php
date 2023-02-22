<?php

declare(strict_types=1);

namespace Joystick\Apis;

use Assert\Assert;
use Joystick\Exceptions\MultipleContentApi;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

class MultipleContent extends AbstractApi
{
    /**
     * Getting Multiple Pieces of Content via API:
     * https://docs.getjoystick.com/api-reference-combine/
     *
     *
     * @param string[] $contentIds List of content identifiers
     * @param array{refresh: boolean, serialized: boolean, fullResponse: boolean} $options
     *
     * @throws ClientExceptionInterface
     * @throws RequestExceptionInterface
     * @throws NetworkExceptionInterface
     * @throws InvalidArgumentException
     */
    public function getContents(array $contentIds, array $options = [])
    {
        $this->validateSignature($contentIds, $options);
        $normalizedOptions = $this->normalizeOptions($options);

        $cacheKey = $this->buildCacheKey($contentIds, $normalizedOptions);

        if (!$normalizedOptions['refresh']) {
            if ($cachedResult = $this->getCache()->get($cacheKey)) {
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

        $this->validateGetContentsResponse($decodedResponse, $normalizedOptions['serialized']);

        $processedResponse = $decodedResponse;
        if (!$normalizedOptions['fullResponse']) {
            $processedResponse = $this->mapGetContentsResponse($processedResponse);
        }

        $this->getCache()->set($cacheKey, $processedResponse, $this->config->getCacheExpirationSeconds());

        return $processedResponse;
    }

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

    private function normalizeOptions(array $options): array
    {
        $normalizedOptions = [];
        $normalizedOptions['refresh'] = $options['refresh'] ?? null;
        $normalizedOptions['serialized'] = $options['serialized'] ?? $this->config->getSerialized();
        $normalizedOptions['fullResponse'] = $options['fullResponse'] ?? null;

        return $normalizedOptions;
    }

    private function mapGetContentsResponse(array $decodedResponse): array
    {
        return array_map(function ($content) {
            return $content['data'];
        }, $decodedResponse);
    }

    private function validateSignature(array $contentIds, array $options = [])
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

    private function validateGetContentsResponse($decodedResponse, $isSerialized)
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

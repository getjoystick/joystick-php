<?php

declare(strict_types=1);

namespace Joystick;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;

class ClientConfig
{
    public const DEFAULT_EXPIRATION_TIME_SECONDS = 300;

    /**
     * Magic number, if you want another number – just set standard cache provider via `setCache`
     */
    public const MAX_ITEMS_IN_CACHE = 1000;

    /**
     * @var ClientInterface $httpClient
     */
    private $httpClient;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string | null
     */
    private $userId = null;

    /**
     * @var mixed[]|null
     */
    private $params = null;

    /**
     * @var string|null
     */
    private $semVer = null;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var bool
     */
    private $serialized;

    /**
     * Cache expiration in seconds. Default to 300.
     * @var int
     */
    private $cacheExpirationSeconds = self::DEFAULT_EXPIRATION_TIME_SECONDS;

    private function __construct()
    {
        $this->httpClient = Psr18ClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $this->cache = new ArrayCachePool(static::MAX_ITEMS_IN_CACHE);
        $this->serialized = false;
    }

    public static function create(): ClientConfig
    {
        return new self();
    }


    /**
     * @return string
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     * @return static
     */
    public function setApiKey(string $apiKey): self
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException(
                'API key passed to ' . __METHOD__ . ' should not be an empty string'
            );
        }
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserId(): ?string
    {
        return $this->userId ?? null;
    }

    /**
     * @param string $userId
     * @return static
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return mixed[]|null
     */
    public function getParams(): ?array
    {
        return $this->params ?? null;
    }

    /**
     * @param mixed[] $params
     * @return static
     */
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setParamValue(string $key, $value): self
    {
        if (!$this->params) {
            $this->params = [];
        }
        $this->params[$key] = $value;
        return $this;
    }


    /**
     * @return string
     */
    public function getSemVer(): ?string
    {
        return $this->semVer ?? null;
    }

    /**
     * @param string $semVer
     * @return static
     */
    public function setSemVer(string $semVer): self
    {
        // Regex proposed at https://semver.org/ , but without prerelease data (hyphen after numbers)
        $semverRegex = '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)$/';
        if (preg_match($semverRegex, $semVer) !== 1) {
            throw new \InvalidArgumentException('Provided semantic version for ' . __METHOD__ . ' is not valid');
        }
        $this->semVer = $semVer;
        return $this;
    }

    /**
     * Amount of time to cache in seconds
     * @return int
     */
    public function getCacheExpirationSeconds(): int
    {
        return $this->cacheExpirationSeconds;
    }

    /**
     * Amount of time to cache in seconds
     * @param int $cacheExpirationSeconds Amount of time to cache in seconds
     * @return static
     */
    public function setCacheExpirationSeconds(int $cacheExpirationSeconds): self
    {
        if ($cacheExpirationSeconds < 0) {
            throw new \InvalidArgumentException('Provided cache expiration for ' . __METHOD__ . ' is not valid');
        }
        $this->cacheExpirationSeconds = $cacheExpirationSeconds;
        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    /**
     * @param ClientInterface $httpClient
     * @return static
     */
    public function setHttpClient(ClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    /**
     * @return RequestFactoryInterface
     */
    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    /**
     * @param RequestFactoryInterface $requestFactory
     * @return static
     */
    public function setRequestFactory(RequestFactoryInterface $requestFactory): self
    {
        $this->requestFactory = $requestFactory;
        return $this;
    }

    /**
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * @param StreamFactoryInterface $streamFactory
     * @return static
     */
    public function setStreamFactory(StreamFactoryInterface $streamFactory): self
    {
        $this->streamFactory = $streamFactory;
        return $this;
    }

    /**
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     *
     * @param CacheInterface $cache
     * @return static
     */
    public function setCache(CacheInterface $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSerialized(): bool
    {
        return $this->serialized;
    }

    /**
     * @param bool $serialized
     * @return static
     */
    public function setSerialized(bool $serialized): self
    {
        $this->serialized = $serialized;
        return $this;
    }
}

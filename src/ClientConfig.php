<?php

declare(strict_types=1);

namespace Joystick;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;

class ClientConfig
{
    public const DEFAULT_EXPIRATION_TIME_MINS = 10;

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
     * @var string
     */
    private $userId;

    /**
     * @var array
     */
    private $params;

    /**
     * @var string
     */
    private $semVer;

    /**
     * @var \Psr\SimpleCache\CacheInterface
     */
    private $cache;

    /**
     * Amount of time to cache in minutes. Default = 10
     * @var int
     */
    private $expiration = self::DEFAULT_EXPIRATION_TIME_MINS;

    private function __construct()
    {
        $this->httpClient = Psr18ClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $this->cache = new ArrayCachePool();
    }

    public static function create()
    {
        return new static();
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
     * @return self
     */
    public function setApiKey(string $apiKey): self
    {
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
     * @return self
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): ?array
    {
        return $this->params ?? null;
    }

    /**
     * @param array $params
     * @return self
     */
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    public function setParamValue(string $key, $value): self
    {
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
     * @return self
     */
    public function setSemVer(string $semVer): self
    {
        // Regex proposed at https://semver.org/ , but without prerelease data (hyphen after numbers)
        $semverRegex = '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)$/';
        if (preg_match($semverRegex, $semVer) !== 1) {
            throw new \LogicException('Provided semantic version for ' . __METHOD__ . ' is not valid');
        }
        $this->semVer = $semVer;
        return $this;
    }

    /**
     * Amount of time to cache in minutes. Default = 10
     * @return int
     */
    public function getExpiration(): int
    {
        return $this->expiration;
    }

    /**
     * Amount of time to cache in minutes. Default = 10
     * @param int $expiration Amount of time to cache in minutes. Default = 10
     * @return self
     */
    public function setExpiration(int $expiration): self
    {
        $this->expiration = $expiration;
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
     * @return self
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
     * @return self
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
     * @return self
     */
    public function setStreamFactory(StreamFactoryInterface $streamFactory): self
    {
        $this->streamFactory = $streamFactory;
        return $this;
    }

    /**
     * 
     * @return 
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * 
     * @param $cache 
     * @return self
     */
    public function setCache(CacheInterface $cache): self
    {
        $this->cache = $cache;
        return $this;
    }
}

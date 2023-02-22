<?php

namespace Joystick;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;


class ClientConfig
{
    private ClientInterface $httpClient;

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    private string $apiKey;

    private string $userId;

    private array $params;

    private string $semVer;



    /**
     * Amount of time to cache in minutes. Default = 10
     */
    private int $expiration = 10;

    private function __construct()
    {
        $this->httpClient = Psr18ClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    public static function create()
    {
        return new static();
    }


    /**
     * @return string
     */
    public function getApiKey(): string
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
}

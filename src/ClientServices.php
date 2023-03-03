<?php

declare(strict_types=1);

namespace Joystick;

class ClientServices
{
    /**
     * @var CacheKeyBuilder
     */
    private $cacheKeyBuilder;

    /**
     * @var Apis\MultipleContent
     */
    private $multipleContentApi;

    private function __construct()
    {
    }

    public static function create(ClientConfig $config)
    {
        $instance = new static();
        $instance->cacheKeyBuilder = new CacheKeyBuilder($config);
        $instance->multipleContentApi =  Apis\MultipleContent::create($config, $instance);

        return $instance;
    }

    /**
     * @return CacheKeyBuilder
     */
    public function getCacheKeyBuilder()
    {
        return $this->cacheKeyBuilder;
    }

    /**
     * @param CacheKeyBuilder $cacheKeyBuilder
     * @return self
     */
    public function setCacheKeyBuilder($cacheKeyBuilder): self
    {
        $this->cacheKeyBuilder = $cacheKeyBuilder;
        return $this;
    }

    /**
     * @return Apis\MultipleContent
     */
    public function getMultipleContentApi(): Apis\MultipleContent
    {
        return $this->multipleContentApi;
    }

    /**
     * @param Apis\MultipleContent $multipleContentApi
     * @return ClientServices
     */
    public function setMultipleContentApi(Apis\MultipleContent $multipleContentApi): ClientServices
    {
        $this->multipleContentApi = $multipleContentApi;
        return $this;
    }
}

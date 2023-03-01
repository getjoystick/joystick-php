<?php

declare(strict_types=1);

namespace Joystick;

class ClientServices
{
    /**
     * @var CacheKeyBuilder
     */
    private $cacheKeyBuilder;

    public function __construct(ClientConfig $config)
    {
        $this->cacheKeyBuilder = new CacheKeyBuilder($config);
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
}

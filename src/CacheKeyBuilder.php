<?php

declare(strict_types=1);

namespace Joystick;

class CacheKeyBuilder
{
    /**
     * @var ClientConfig
     */
    private $config;

    public function __construct(ClientConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param mixed[] $additionalSegments
     * @return string
     */
    public function build(array $additionalSegments): string
    {
        if ($params = $this->config->getParams()) {
            ksort($params);
        }
        $keySegments = array_merge([
            $this->config->getApiKey(),
            $params,
            $this->config->getSemVer(),
            $this->config->getUserId()
        ], $additionalSegments);

        $encodedKeySegments = json_encode($keySegments);

        assert(!empty($encodedKeySegments));

        return hash('sha256', $encodedKeySegments);
    }
}

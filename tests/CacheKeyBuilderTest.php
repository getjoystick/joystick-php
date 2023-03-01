<?php

declare(strict_types=1);

namespace Joystick\Tests;

use Joystick\CacheKeyBuilder;
use Joystick\ClientConfig;
use PHPUnit\Framework\TestCase;

class CacheKeyBuilderTest extends TestCase
{

    /**
     * @var CacheKeyBuilder
     */
    private $cacheKeyBuilder;

    protected function setUp(): void
    {
    }

    public function testConfigWithZeroConfiguredConfig()
    {
        $config = ClientConfig::create();
        $cacheKeyBuilder = new CacheKeyBuilder($config);

        $expected = hash('sha256', json_encode([
            null,
            null,
            null,
            null,
        ]));

        $result = $cacheKeyBuilder->build([]);

        $this->assertSame($expected, $result);
        $this->assertIsString($result);
    }

    public function testFullyConfiguredConfig()
    {
        $apiKey = 'api-key';
        $params = ['param1' => 'value1'];
        $semVer = '0.0.2';
        $userId = 'user-id';

        $config = ClientConfig::create()
            ->setApiKey($apiKey)
            ->setParams($params)
            ->setSemVer($semVer)
            ->setUserId($userId);

        $expected = hash('sha256', json_encode([
            $apiKey,
            $params,
            $semVer,
            $userId,
        ]));

        $cacheKeyBuilder = new CacheKeyBuilder($config);
        $result = $cacheKeyBuilder->build([]);

        $this->assertSame($expected, $result);
        $this->assertIsString($result);
    }


    public function testParamsOrder()
    {
        $params = ['yParam1' => 'value of y param', 'xParam1' => 'value of x param'];
        $paramsWithAnotherOrder = ['xParam1' => 'value of x param', 'yParam1' => 'value of y param'];

        $config = ClientConfig::create()
            ->setParams($params);


        $cacheKeyBuilder = new CacheKeyBuilder($config);
        $first = $cacheKeyBuilder->build([]);

        $config = ClientConfig::create()
            ->setParams($paramsWithAnotherOrder);
        $cacheKeyBuilder = new CacheKeyBuilder($config);

        $second = $cacheKeyBuilder->build([]);

        $this->assertIsString($first);
        $this->assertSame($first, $second);
    }
}

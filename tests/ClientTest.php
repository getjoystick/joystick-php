<?php

declare(strict_types=1);

namespace Joystick\Tests;

use Assert\InvalidArgumentException;
use GuzzleHttp\Psr7\HttpFactory;
use Joystick\Apis\MultipleContent;
use Joystick\Client;
use Joystick\ClientConfig;
use Joystick\ClientServices;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;

class ClientTest extends TestCase
{
    private $config;

    public const API_KEY = 'api-key';
    public const USER_ID_VALUE = 'USER_ID_VALUE';


    protected function setUp(): void
    {
        $httpClient = $this->prophesize(ClientInterface::class);
        $requestFactory = new HttpFactory();
        $streamFactory = new HttpFactory();
        $this->config = ClientConfig::create()
            ->setHttpClient($httpClient->reveal())
            ->setRequestFactory($requestFactory)
            ->setStreamFactory($streamFactory);
    }

    public function testNoApiKey()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::create($this->config);
    }

    public function testShouldClearCache()
    {
        $expectedClearCacheResult = true;
        $cache = $this->prophesize(CacheInterface::class);
        $cache->clear()->willReturn($expectedClearCacheResult)->shouldBeCalledOnce();

        $config = $this->config
            ->setApiKey(self::API_KEY)
            ->setCache($cache->reveal());

        $client = Client::create(
            $config
        );
        $clearCacheResult = $client->clearCache();

        $this->assertEquals($expectedClearCacheResult, $clearCacheResult);
    }

    public function testGetContentsShouldCallServiceMethod()
    {
        $contentIds = ['cid1', 'cid2'];
        $options = ['fullResponse' => true, 'serialized' => true];

        $multipleContent = $this->prophesize(MultipleContent::class);
        $multipleContent->getContents($contentIds, $options)->shouldBeCalledOnce();

        $this->config->setApiKey(self::API_KEY);
        $clientServices = ClientServices::create($this->config)->setMultipleContentApi($multipleContent->reveal());
        $client = Client::create($this->config, $clientServices);

        $client->getContents($contentIds, $options);
    }

    public function testGetContentShouldCallServiceMethod()
    {
        $contentId = 'cid1';
        $options = [];
        $values = ['myProperty1' => 'myProperty1Value'];

        $multipleContent = $this->prophesize(MultipleContent::class);
        $multipleContent
            ->getContents([$contentId], $options)
            ->willReturn(['cid1' => $values])
            ->shouldBeCalledOnce();

        $this->config->setApiKey(self::API_KEY);
        $clientServices = ClientServices::create($this->config)->setMultipleContentApi($multipleContent->reveal());
        $client = Client::create($this->config, $clientServices);

        $content = $client->getContent($contentId, $options);
        $this->assertSame($values, $content);
    }
}

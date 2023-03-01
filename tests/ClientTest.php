<?php

declare(strict_types=1);

namespace Joystick\Tests;

use Assert\InvalidArgumentException;
use GuzzleHttp\Psr7\HttpFactory;
use Joystick\CacheKeyBuilder;
use Joystick\Client;
use Joystick\ClientConfig;
use Joystick\ClientServices;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class ClientTest extends TestCase
{
    private $httpClient;
    private $requestFactory;
    private $streamFactory;
    private $config;

    public const API_KEY = 'api-key';
    public const USER_ID_VALUE = 'USER_ID_VALUE';


    protected function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->requestFactory = new HttpFactory();
        $this->streamFactory = new HttpFactory();
        $this->config = ClientConfig::create()
            ->setHttpClient($this->httpClient->reveal())
            ->setRequestFactory($this->requestFactory)
            ->setStreamFactory($this->streamFactory);
    }

    public function testNoApiKey()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::create($this->config);
    }

    public function testGetContentsNoContentIds()
    {
        $this->expectException(InvalidArgumentException::class);
        $client = Client::create($this->config->setApiKey(self::API_KEY));
        $client->getContents([]);
    }

    /**
     * @dataProvider nonBooleanValues
     */
    public function testGetContentsIncorrectRefreshOption($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $client = Client::create($this->config->setApiKey(self::API_KEY));
        $client->getContents(['content-id'], ['refresh' => $value]);
    }
    /**
     * @dataProvider nonBooleanValues
     */
    public function testGetContentsIncorrectSerializedOption($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $client = Client::create($this->config->setApiKey(self::API_KEY));
        $client->getContents(['content-id'], ['serialized' => $value]);
    }

    /**
     * @dataProvider nonBooleanValues
     */
    public function testGetContentsIncorrectFullResponseOption($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $client = Client::create($this->config->setApiKey(self::API_KEY));
        $client->getContents(['content-id'], ['fullResponse' => $value]);
    }

    public static function nonBooleanValues()
    {
        return [
            ['true'],
            ['false'],
            [0],
            [1],
            [0.0],
            [1.1],
            [[]],
            [new \stdClass()],
        ];
    }

    public function testGetContentsMinimalIsProvided()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('[]');

        $this->httpClient->sendRequest(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            // Query params
            parse_str($uri->getQuery(), $decodedQueryParams);
            $this->assertSame(['c' =>  '["content-ids"]', 'dynamic' => 'true'], $decodedQueryParams);
            $this->assertSame('https', $uri->getScheme());

            // Body
            $bodyDecoded = json_decode((string)$request->getBody(), true);
            $this->assertCount(2, $bodyDecoded);
            $this->assertSame('', $bodyDecoded['u']);
            $this->assertSame([], $bodyDecoded['p']);

            // Headers
            $this->assertSame([self::API_KEY], $request->getHeader('x-api-key'));
            $this->assertSame(['application/json'], $request->getHeader('content-type'));

            return true;
        }))->willReturn($response);

        $client = Client::create($this->config->setApiKey(self::API_KEY));

        $client->getContents(['content-ids']);
    }

    public function testGetContentsWitMultipleContentIdsProvided()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('[]');

        $this->httpClient->sendRequest(Argument::that(function (RequestInterface $request) {
            // Query params
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $decodedQueryParams);
            $this->assertSame([
                'c' =>  '["content-ids","another-content-id"]',
                'dynamic' => 'true'
            ], $decodedQueryParams);
            $this->assertSame('https', $uri->getScheme());

            // Body
            $bodyDecoded = json_decode((string)$request->getBody(), true);
            $this->assertCount(2, $bodyDecoded);
            $this->assertSame('', $bodyDecoded['u']);
            $this->assertSame([], $bodyDecoded['p']);

            // Headers
            $this->assertSame([self::API_KEY], $request->getHeader('x-api-key'));
            $this->assertSame(['application/json'], $request->getHeader('content-type'));
            return true;
        }))->willReturn($response);

        $client = Client::create($this->config->setApiKey(self::API_KEY));

        $client->getContents(['content-ids', 'another-content-id']);
    }

    public function testGetContentsWithUserIdProvided()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('[]');

        $this->httpClient->sendRequest(Argument::that(function (RequestInterface $request) {
            // Query params
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $decodedQueryParams);
            $this->assertSame([
                'c' =>  '["content-ids","another-content-id"]',
                'dynamic' => 'true'
            ], $decodedQueryParams);
            $this->assertSame('https', $uri->getScheme());

            // Body
            $bodyDecoded = json_decode((string)$request->getBody(), true);
            $this->assertCount(2, $bodyDecoded);
            $this->assertSame(self::USER_ID_VALUE, $bodyDecoded['u']);
            $this->assertSame([], $bodyDecoded['p']);

            // Headers
            $this->assertSame([self::API_KEY], $request->getHeader('x-api-key'));
            $this->assertSame(['application/json'], $request->getHeader('content-type'));
            return true;
        }))->willReturn($response);

        $client = Client::create(
            $this->config->setApiKey(self::API_KEY)
                ->setUserId(self::USER_ID_VALUE)
        );

        $client->getContents(['content-ids', 'another-content-id']);
    }

    public function testGetContentsWithParams()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('[]');

        $this->httpClient->sendRequest(Argument::that(function (RequestInterface $request) {
            // Query params
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $decodedQueryParams);
            $this->assertSame([
                'c' =>  '["content-ids","another-content-id"]',
                'dynamic' => 'true'
            ], $decodedQueryParams);
            $this->assertSame('https', $uri->getScheme());

            // Body
            $bodyDecoded = json_decode((string)$request->getBody(), true);
            $this->assertCount(2, $bodyDecoded);
            $this->assertSame(self::USER_ID_VALUE, $bodyDecoded['u']);
            $this->assertSame(['hello' => 'world'], $bodyDecoded['p']);


            // Headers
            $this->assertSame([self::API_KEY], $request->getHeader('x-api-key'));
            $this->assertSame(['application/json'], $request->getHeader('content-type'));
            return true;
        }))->willReturn($response);

        $client = Client::create(
            $this->config->setApiKey(self::API_KEY)
                ->setUserId(self::USER_ID_VALUE)
                ->setParams(['hello' => 'world'])
        );

        $client->getContents(['content-ids', 'another-content-id']);
    }

    public function testGetContentsWithSemVer()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('[]');

        $this->httpClient->sendRequest(Argument::that(function (RequestInterface $request) {
            // Query params
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $decodedQueryParams);
            $this->assertSame([
                'c' =>  '["content-ids","another-content-id"]',
                'dynamic' => 'true'
            ], $decodedQueryParams);
            $this->assertSame('https', $uri->getScheme());

            // Body
            $bodyDecoded = json_decode((string)$request->getBody(), true);
            $this->assertCount(3, $bodyDecoded);
            $this->assertSame(self::USER_ID_VALUE, $bodyDecoded['u']);
            $this->assertSame(['hello' => 'world'], $bodyDecoded['p']);
            $this->assertSame('0.0.10', $bodyDecoded['v']);

            // Headers
            $this->assertSame([self::API_KEY], $request->getHeader('x-api-key'));
            $this->assertSame(['application/json'], $request->getHeader('content-type'));
            return true;
        }))->willReturn($response);

        $client = Client::create(
            $this->config->setApiKey(self::API_KEY)
                ->setUserId(self::USER_ID_VALUE)
                ->setParams(['hello' => 'world'])
                ->setSemVer('0.0.10')
        );

        $client->getContents(['content-ids', 'another-content-id']);
    }

    public function testGetContentsWithSerializedOption()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('[]');

        $this->httpClient->sendRequest(Argument::that(function (RequestInterface $request) {
            // Query params
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $decodedQueryParams);
            $this->assertSame([
                'c' =>  '["content-ids","another-content-id"]',
                'dynamic' => 'true',
                'responseType' => 'serialized'
            ], $decodedQueryParams);
            $this->assertSame('https', $uri->getScheme());

            // Body
            $bodyDecoded = json_decode((string)$request->getBody(), true);
            $this->assertCount(3, $bodyDecoded);
            $this->assertSame(self::USER_ID_VALUE, $bodyDecoded['u']);
            $this->assertSame(['hello' => 'world'], $bodyDecoded['p']);
            $this->assertSame('0.0.10', $bodyDecoded['v']);

            // Headers
            $this->assertSame([self::API_KEY], $request->getHeader('x-api-key'));
            $this->assertSame(['application/json'], $request->getHeader('content-type'));
            return true;
        }))->willReturn($response);

        $client = Client::create(
            $this->config->setApiKey(self::API_KEY)
                ->setUserId(self::USER_ID_VALUE)
                ->setParams(['hello' => 'world'])
                ->setSemVer('0.0.10')
        );

        $client->getContents(['content-ids', 'another-content-id'], ['serialized' => true]);
    }

    // ! ||--------------------------------------------------------------------------------||
    // ! ||                                  Caching Tests                                 ||
    // ! ||--------------------------------------------------------------------------------||
    public function testReturnResultFromCache()
    {
        $dataInCache =  [
            "simple-dimple-content-id" => [
                "data" => [
                    "simple" => "dimple"
                ],
                "hash" => "b180ab2e",
                "meta" => [
                    "uid" => 0,
                    "mod" => 0,
                    "variants" => [],
                    "seg" => []
                ]
            ]
        ];
        $cacheKey = 'CACHE-KEY';
        $cache = $this->prophesize(CacheInterface::class);
        $cache->get($cacheKey)->willReturn($dataInCache);

        $cacheKeyBuilder = $this->prophesize(CacheKeyBuilder::class);
        $cacheKeyBuilder->build(Argument::type('array'))->willReturn($cacheKey);


        $clientServices = $this->prophesize(ClientServices::class);
        $clientServices->getCacheKeyBuilder()->willReturn($cacheKeyBuilder->reveal());

        $config = $this->config
            ->setApiKey(self::API_KEY)
            ->setCache($cache->reveal());

        $client = Client::create(
            $config,
            $clientServices->reveal()
        );

        $result = $client->getContents(['simple-dimple-content-id']);
        $expected = $dataInCache;

        $this->assertEquals($expected, $result);
    }

    public function testShouldSaveResultsToCache()
    {
        $expirationTime = 10;
        $httpCallResultUnencoded =  [
            "simple-dimple-content-id" => [
                "data" => [
                    "simple" => "dimple"
                ],
                "hash" => "b180ab2e",
                "meta" => [
                    "uid" => 0,
                    "mod" => 0,
                    "variants" => [],
                    "seg" => []
                ]
            ]
        ];

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn(json_encode($httpCallResultUnencoded));
        $this->httpClient->sendRequest(Argument::any())->willReturn($response);


        $cacheKey = 'CACHE-KEY';
        $cache = $this->prophesize(CacheInterface::class);
        $cache->get($cacheKey)->willReturn(null);
        $cache->set($cacheKey, $httpCallResultUnencoded, $expirationTime)->will(
            function () use ($cache, $cacheKey, $httpCallResultUnencoded) {
                $cache->get($cacheKey)->willReturn($httpCallResultUnencoded)->shouldBeCalledTimes(2);
            }
        )->shouldBeCalledOnce();

        $cacheKeyBuilder = $this->prophesize(CacheKeyBuilder::class);
        $cacheKeyBuilder->build(Argument::type('array'))->willReturn($cacheKey);


        $clientServices = $this->prophesize(ClientServices::class);
        $clientServices->getCacheKeyBuilder()->willReturn($cacheKeyBuilder->reveal());

        $config = $this->config
            ->setApiKey(self::API_KEY)
            ->setCache($cache->reveal())
            ->setExpiration($expirationTime);

        $client = Client::create(
            $config,
            $clientServices->reveal()
        );

        $result = $client->getContents(['simple-dimple-content-id']);
        $secondResult = $client->getContents(['simple-dimple-content-id']);

        $this->assertEquals($result, $httpCallResultUnencoded);
        $this->assertEquals($result, $secondResult);
    }

    public function testShouldSaveButNotReadFromCacheWithRefreshOption()
    {
        $httpCallResultUnencoded =  [
            "simple-dimple-content-id" => [
                "data" => [
                    "simple" => "dimple"
                ],
                "hash" => "b180ab2e",
                "meta" => [
                    "uid" => 0,
                    "mod" => 0,
                    "variants" => [],
                    "seg" => []
                ]
            ]
        ];
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn(json_encode($httpCallResultUnencoded));
        $this->httpClient->sendRequest(Argument::any())->willReturn($response);


        $cacheKey = 'CACHE-KEY';
        $cache = $this->prophesize(CacheInterface::class);
        $cache->get($cacheKey)->willReturn(null)->shouldNotBeCalled();
        $cache->set($cacheKey, Argument::any(), Argument::any())->shouldBeCalledTimes(2);

        $cacheKeyBuilder = $this->prophesize(CacheKeyBuilder::class);
        $cacheKeyBuilder->build(Argument::type('array'))->willReturn($cacheKey);


        $clientServices = $this->prophesize(ClientServices::class);
        $clientServices->getCacheKeyBuilder()->willReturn($cacheKeyBuilder->reveal());

        $config = $this->config
            ->setApiKey(self::API_KEY)
            ->setCache($cache->reveal());

        $client = Client::create(
            $config,
            $clientServices->reveal()
        );

        // To make sure that the second call will not change anything – will call it twice
        $client->getContents(['simple-dimple-content-id'], ['refresh' => true]);
        $client->getContents(['simple-dimple-content-id'], ['refresh' => true]);
    }

    public function testShouldCleaarCache()
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
}

<?php

declare(strict_types=1);

namespace Joystick\Tests\Apis;

use Assert\LazyAssertionException;
use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use Joystick\Apis\MultipleContent;
use Joystick\CacheKeyBuilder;
use Joystick\ClientConfig;
use Joystick\ClientServices;
use Joystick\Exceptions\Api\Http\BadRequest;
use Joystick\Exceptions\Api\Http\ServerError;
use Joystick\Exceptions\Api\MultipleContentApi;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class MultipleContentTest extends TestCase
{
    public const API_KEY = 'api-key';
    public const USER_ID_VALUE = 'USER_ID_VALUE';
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|ClientInterface
     */
    private $httpClient;
    /**
     * @var HttpFactory
     */
    private $requestFactory;
    /**
     * @var HttpFactory
     */
    private $streamFactory;
    /**
     * @var ClientConfig
     */
    private $config;
    /**
     * @var ClientServices
     */
    private $clientServices;

    private function provisionHttpParamToConfig(ClientConfig $config)
    {
        return $config->setHttpClient($this->httpClient->reveal())
            ->setRequestFactory($this->requestFactory)
            ->setStreamFactory($this->streamFactory);
    }

    protected function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->requestFactory = new HttpFactory();
        $this->streamFactory = new HttpFactory();
        $this->config = $this->provisionHttpParamToConfig(ClientConfig::create());
        $this->clientServices = ClientServices::create($this->config);
    }

    // ! ||--------------------------------------------------------------------------------||
    // ! ||                                Validation                                      ||
    // ! ||--------------------------------------------------------------------------------||

    public function testGetContentsNoContentIds()
    {
        $this->expectException(InvalidArgumentException::class);
        $client = MultipleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);
        $client->getContents([]);
    }

    /**
     * @dataProvider invalidContentIds
     */
    public function testGetContentsInvalidContentIdsArray(array $contentIds)
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->httpClient->sendRequest(Argument::any())->willReturn($response);

        $client = MultipleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);
        $this->expectException(LazyAssertionException::class);
        $client->getContents($contentIds);
    }

    public function invalidContentIds()
    {
        return [
            [
                // Empty
                []
            ],
            [
                // Array of empty array
                [[]]
            ],
            [
                // Number
                [123]
            ],
            [
                // Boolean
                [true]
            ],

            [
                // Empty string
                ['']
            ],
            [
                // Array of empty strings
                ['', '', '']
            ],
            [[null]]
        ];
    }

    /**
     * @dataProvider nonBooleanValues
     */
    public function testGetContentsIncorrectRefreshOption($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $client = MultipleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);
        $client->getContents(['content-id'], ['refresh' => $value]);
    }

    /**
     * @dataProvider nonBooleanValues
     */
    public function testGetContentsIncorrectSerializedOption($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $client = MultipleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);
        $client->getContents(['content-id'], ['serialized' => $value]);
    }

    /**
     * @dataProvider nonBooleanValues
     */
    public function testGetContentsIncorrectFullResponseOption($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $client = MultipleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);
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

    // ! ||--------------------------------------------------------------------------------||
    // ! ||                        Different options behavior                              ||
    // ! ||--------------------------------------------------------------------------------||
    public function testGetContentsMinimalIsProvided()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('[]');

        $this->httpClient->sendRequest(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            // Query params
            parse_str($uri->getQuery(), $decodedQueryParams);
            $this->assertSame(['c' => '["content-ids"]', 'dynamic' => 'true'], $decodedQueryParams);
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

        $client = MultipleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);

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
                'c' => '["content-ids","another-content-id"]',
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

        $client = MultipleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);

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
                'c' => '["content-ids","another-content-id"]',
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

        $client = MultipleContent::create(
            $this->config->setApiKey(self::API_KEY)
                ->setUserId(self::USER_ID_VALUE),
            $this->clientServices
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
                'c' => '["content-ids","another-content-id"]',
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

        $client = MultipleContent::create(
            $this->config->setApiKey(self::API_KEY)
                ->setUserId(self::USER_ID_VALUE)
                ->setParams(['hello' => 'world']),
            $this->clientServices
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
                'c' => '["content-ids","another-content-id"]',
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

        $client = MultipleContent::create(
            $this->config->setApiKey(self::API_KEY)
                ->setUserId(self::USER_ID_VALUE)
                ->setParams(['hello' => 'world'])
                ->setSemVer('0.0.10'),
            $this->clientServices
        );

        $client->getContents(['content-ids', 'another-content-id']);
    }

    // ! ||--------------------------------------------------------------------------------||
    // ! ||                             Serialized option test                             ||
    // ! ||--------------------------------------------------------------------------------||
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
                'c' => '["content-ids","another-content-id"]',
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

        $client = MultipleContent::create(
            $this->config->setApiKey(self::API_KEY)
                ->setUserId(self::USER_ID_VALUE)
                ->setParams(['hello' => 'world'])
                ->setSemVer('0.0.10'),
            $this->clientServices
        );

        $client->getContents(['content-ids', 'another-content-id'], ['serialized' => true]);
    }

    /**
     * @dataProvider shouldRespectConfigLevelSerializedOptionDataProvider
     */
    public function testShouldRespectConfigLevelSerializedOption(ClientConfig $config, array $options, $responseType)
    {
        $this->provisionHttpParamToConfig($config);

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('[]');

        $this->httpClient->sendRequest(Argument::that(function (RequestInterface $request) use ($responseType) {
            // Query params
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $decodedQueryParams);

            $expected = array_merge([
                'c' => '["content-ids","another-content-id"]',
                'dynamic' => 'true',

            ], $responseType ? ['responseType' => $responseType] : []);
            $this->assertSame($expected, $decodedQueryParams);

            return true;
        }))->willReturn($response);

        $client = MultipleContent::create(
            $config->setApiKey(self::API_KEY),
            $this->clientServices
        );

        $client->getContents(['content-ids', 'another-content-id'], $options);
    }

    public function shouldRespectConfigLevelSerializedOptionDataProvider()
    {
        return [
            [
                ClientConfig::create(),
                [],
                null
            ],
            [
                ClientConfig::create()->setSerialized(false),
                [],
                null
            ],
            [
                ClientConfig::create()->setSerialized(false),
                ['serialized' => false],
                null
            ],
            [
                ClientConfig::create(),
                ['serialized' => false],
                null
            ],
            [
                ClientConfig::create()->setSerialized(true),
                [],
                'serialized'
            ],
            [
                ClientConfig::create()->setSerialized(true),
                ['serialized' => true],
                'serialized'
            ],
            [
                ClientConfig::create()->setSerialized(false),
                ['serialized' => true],
                'serialized'
            ],
            [
                ClientConfig::create(),
                ['serialized' => true],
                'serialized'
            ],
        ];
    }

    // ! ||--------------------------------------------------------------------------------||
    // ! ||                                  Caching Tests                                 ||
    // ! ||--------------------------------------------------------------------------------||
    public function testReturnResultFromCache()
    {
        $dataInCache = [
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

        $client = MultipleContent::create(
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
        $httpCallResultUnencoded = [
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
        $expectedMethodResult = [
            "simple-dimple-content-id" => [
                "simple" => "dimple"
            ]
        ];

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn(json_encode($httpCallResultUnencoded));
        $this->httpClient->sendRequest(Argument::any())->willReturn($response);

        $cacheKey = 'CACHE-KEY';
        $cache = $this->prophesize(CacheInterface::class);
        $cache->get($cacheKey)->willReturn(null);
        $cache->set($cacheKey, $expectedMethodResult, $expirationTime)->will(
            function () use ($cache, $cacheKey, $expectedMethodResult) {
                $cache->get($cacheKey)->willReturn($expectedMethodResult)->shouldBeCalledTimes(2);
            }
        )->shouldBeCalledOnce();

        $cacheKeyBuilder = $this->prophesize(CacheKeyBuilder::class);
        $cacheKeyBuilder->build(Argument::type('array'))->willReturn($cacheKey);


        $clientServices = $this->prophesize(ClientServices::class);
        $clientServices->getCacheKeyBuilder()->willReturn($cacheKeyBuilder->reveal());

        $config = $this->config
            ->setApiKey(self::API_KEY)
            ->setCache($cache->reveal())
            ->setCacheExpirationSeconds($expirationTime);

        $client = MultipleContent::create(
            $config,
            $clientServices->reveal()
        );

        $result = $client->getContents(['simple-dimple-content-id']);
        $secondResult = $client->getContents(['simple-dimple-content-id']);

        $this->assertEquals($result, $expectedMethodResult);
        $this->assertEquals($result, $secondResult);
    }

    public function testShouldSaveButNotReadFromCacheWithRefreshOption()
    {
        $httpCallResultUnencoded = [
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

        $client = MultipleContent::create(
            $config,
            $clientServices->reveal()
        );

        // To make sure that the second call will not change anything – will call it twice
        $client->getContents(['simple-dimple-content-id'], ['refresh' => true]);
        $client->getContents(['simple-dimple-content-id'], ['refresh' => true]);
    }


    /**
     * @dataProvider shouldRespectFullResponseOptionDataProvider
     */
    public function testShouldRespectFullResponseOption(
        array $options,
        array $httpCallResultUnencoded,
        array $expectedMethodResult
    ) {
        $expirationTime = 10;

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn(json_encode($httpCallResultUnencoded));
        $this->httpClient->sendRequest(Argument::any())->willReturn($response);

        $cacheKey = 'CACHE-KEY';
        $cache = $this->prophesize(CacheInterface::class);
        $cache->get($cacheKey)->willReturn(null);
        $cache->set($cacheKey, $expectedMethodResult, $expirationTime)->will(
            function () use ($cache, $cacheKey, $expectedMethodResult) {
                $cache->get($cacheKey)->willReturn($expectedMethodResult)->shouldBeCalledTimes(2);
            }
        )->shouldBeCalledOnce();

        $cacheKeyBuilder = $this->prophesize(CacheKeyBuilder::class);
        $cacheKeyBuilder->build(Argument::type('array'))->willReturn($cacheKey);


        $clientServices = $this->prophesize(ClientServices::class);
        $clientServices->getCacheKeyBuilder()->willReturn($cacheKeyBuilder->reveal());

        $config = $this->config
            ->setApiKey(self::API_KEY)
            ->setCache($cache->reveal())
            ->setCacheExpirationSeconds($expirationTime);

        $client = MultipleContent::create(
            $config,
            $clientServices->reveal()
        );

        $result = $client->getContents(['simple-dimple-content-id', 'second-content-id'], $options);
        $secondResult = $client->getContents(['simple-dimple-content-id', 'second-content-id'], $options);

        $this->assertEquals($result, $expectedMethodResult);
        $this->assertEquals($result, $secondResult);
    }

    public function shouldRespectFullResponseOptionDataProvider()
    {
        $httpCallResultUnencoded = [
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
            ],
            "second-content-id" => [
                "data" => [
                    "very interesting" => "content"
                ],
                "hash" => "e201ec3e",
                "meta" => [
                    "uid" => 0,
                    "mod" => 0,
                    "variants" => [],
                    "seg" => []
                ]
            ]
        ];

        return [
            [
                // No options
                [],
                $httpCallResultUnencoded,
                [
                    'simple-dimple-content-id' => ["simple" => "dimple"],
                    'second-content-id' => ["very interesting" => "content"],
                ]
            ],
            [
                // No options
                ['fullResponse' => false],
                $httpCallResultUnencoded,
                [
                    'simple-dimple-content-id' => ["simple" => "dimple"],
                    'second-content-id' => ["very interesting" => "content"],
                ]
            ],
            [
                // No options
                ['fullResponse' => true],
                $httpCallResultUnencoded,
                $httpCallResultUnencoded,
            ],
        ];
    }

    // ! ||--------------------------------------------------------------------------------||
    // ! ||                              API Exceptions                                    ||
    // ! ||--------------------------------------------------------------------------------||

    public function testApiExceptionOnStringAtContentPlace()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn(json_encode([
            "not-existing-content-id" => "Error 404,  https://api.getjoystick.com/api/v1/config/not-existing-content" .
                "-id/dynamic?responsetype=parsed {\"data\":null,\"status\":2,\"message\":null,\"details\":null}.",
            "sample-second" => [
                "data" => [
                    "normal" => "response"
                ],
                "hash" => "ad3028ae",
                "meta" => [
                    "uid" => 0,
                    "mod" => 0,
                    "variants" => [],
                    "seg" => []
                ]
            ]
        ]));

        $this->httpClient->sendRequest(Argument::any())->willReturn($response);

        $client = MultipleContent::create(
            $this->config->setApiKey(self::API_KEY),
            $this->clientServices
        );

        $this->expectException(MultipleContentApi::class);
        $client->getContents(['cid1', 'cid2']);
    }

    /**
     * @dataProvider fourXXDataProvider
     */
    public function testApiExceptionOn4XXHttpCode($statusCode)
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn($statusCode);
        $response->getBody()->willReturn(json_encode([]));

        $this->httpClient->sendRequest(Argument::any())->willReturn($response);

        $client = MultipleContent::create(
            $this->config->setApiKey(self::API_KEY),
            $this->clientServices
        );


        $this->expectException(BadRequest::class);
        $client->getContents(['not-existing-content-id', 'sample-second']);
    }

    public function fourXXDataProvider(): array
    {
        return [
            [400],
            [401],
            [403],
            [404],
            [405],
        ];
    }

    /**
     * @dataProvider fiveXXDataProvider
     */
    public function testApiExceptionOn5XXHttpCode(int $statusCode)
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn($statusCode);
        $response->getBody()->willReturn(json_encode([]));

        $this->httpClient->sendRequest(Argument::any())->willReturn($response);

        $client = MultipleContent::create(
            $this->config->setApiKey(self::API_KEY),
            $this->clientServices
        );


        $this->expectException(ServerError::class);
        $client->getContents(['not-existing-content-id', 'sample-second']);
    }

    public function fiveXXDataProvider(): array
    {
        return [
            [500],
            [501],
            [502],
            [503],
            [504],
        ];
    }


    public function testApiExceptionOn500HttpCode()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(500);
        $response->getBody()->willReturn(json_encode([]));

        $this->httpClient->sendRequest(Argument::any())->willReturn($response);

        $client = MultipleContent::create(
            $this->config->setApiKey(self::API_KEY),
            $this->clientServices
        );


        $this->expectException(ServerError::class);
        $client->getContents(['cid-1', 'cid-2']);
    }

    public function testApiExceptionOnWrongJson()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('It is not a JSON');

        $this->httpClient->sendRequest(Argument::any())->willReturn($response);

        $client = MultipleContent::create(
            $this->config->setApiKey(self::API_KEY),
            $this->clientServices
        );

        $this->expectException(\Joystick\Exceptions\Api\Exception::class);
        $client->getContents(['cid-1', 'cid-2']);
    }
}

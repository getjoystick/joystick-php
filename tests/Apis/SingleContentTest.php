<?php

declare(strict_types=1);

namespace Joystick\Tests\Apis;

use GuzzleHttp\Psr7\HttpFactory;
use Joystick\Apis\SingleContent;
use Joystick\ClientConfig;
use Joystick\ClientServices;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class SingleContentTest extends TestCase
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

    public function testPublishContentUpdateNoContentId()
    {
        $this->expectException(\Assert\InvalidArgumentException::class);
        $client = SingleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);
        $client->publishContentUpdate(
            '',
            [
                'description' => 'Hello',
                'content' => [],
                'dynamicContentMap' => []
            ]
        );
    }

    /**
     * @dataProvider publishContentUpdateInvalidParamsDataProvider
     */
    public function testPublishContentUpdateInvalidParams(array $params)
    {
        $this->expectException(\Assert\InvalidArgumentException::class);
        $client = SingleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);
        $client->publishContentUpdate(
            'sample-content-id',
            $params
        );
    }

    public function publishContentUpdateInvalidParamsDataProvider()
    {
        return [
            [
                // Invalid description type
                [
                    'description' => null,
                    'content' => [],
                    'dynamicContentMap' => [],
                ]
            ],
            [
                // Invalid dynamicContentMap
                [
                    'description' => 'Description of the content',
                    'content' => [],
                    'dynamicContentMap' => '',
                ]
            ],
            [
                // Long description
                [
                    'description' => str_repeat('1', 51),
                    'content' => ['valid content'],
                    'dynamicContentMap' => [],
                ]
            ],
            [
                // Too short description
                [
                    'description' => '',
                    'content' => ['valid content'],
                    'dynamicContentMap' => [],
                ]
            ],
            [
                // Not enough fields
                [

                ]
            ],
            [
                // Only some fields
                [
                    'description' => '',
                ]
            ],

            [
                // Only some fields
                [
                    'content' => [],
                ]
            ],
            [
                // Only some fields
                [
                    'dynamicContentMap' => [],
                ]
            ],
            [
                // Only some fields
                [
                    'dynamicContentMap' => [],
                ]
            ],
        ];
    }


    /**
     * @dataProvider publishContentUpdateUnencodableDataProvider
     */
    public function testPublishContentUpdateUnencodableData(array $params)
    {
        $this->expectException(\Joystick\Exceptions\NotJsonEncodable::class);
        $client = SingleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);
        $client->publishContentUpdate(
            'sample-content-id',
            $params
        );
    }

    public function publishContentUpdateUnencodableDataProvider()
    {
        $notSerializableJson = new \stdClass();
        $notSerializableJson->property = $notSerializableJson;
        return [
            [
                [
                    'description' => 'Description of the content',
                    'content' => $notSerializableJson,
                    'dynamicContentMap' => [],
                ]
            ],
            [
                [
                    'description' => 'Description of the content',
                    'content' => [],
                    'dynamicContentMap' => [$notSerializableJson],
                ]
            ],
            [
                [
                    'description' => 'Description of the content',
                    'content' => $notSerializableJson,
                    'dynamicContentMap' => [$notSerializableJson],
                ]
            ],
            [
                [
                    'description' => 'Description of the content',
                    'content' => $notSerializableJson,
                ]
            ],
        ];
    }


    /**
     * @dataProvider publishContentUpdateNoErrorForValidParams
     */
    public function testPublishContentUpdateNoErrorForValidParams($params)
    {
        $client = SingleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('[]');

        $this->httpClient->sendRequest(Argument::any())->willReturn($response);


        $result = $client->publishContentUpdate(
            'sample-content-id',
            $params
        );

        $this->assertSame(null, $result);
    }


    public function publishContentUpdateNoErrorForValidParams()
    {
        return [
            [
                // Bare minimum
                [
                    'description' => 'Description of the content',
                    'content' => [],
                    'dynamicContentMap' => [],
                ]
            ],
            // Different data types for content are valid
            [
                [
                    'description' => 'Description of the content',
                    'content' => ['a' => 'b'],
                    'dynamicContentMap' => [],
                ]
            ],

            [
                [
                    'description' => 'Description of the content',
                    'content' => 'string',
                    'dynamicContentMap' => [],
                ]
            ],
            [
                [
                    'description' => 'Description of the content',
                    'content' => 123,
                    'dynamicContentMap' => [],
                ]
            ],
            [
                [
                    'description' => 'Description of the content',
                    'content' => [1, 2, 3, 4],
                    'dynamicContentMap' => [],
                ]
            ],
            [
                [
                    'description' => 'Description of the content',
                    'content' => [['a' => 'b', 'c' => 'd', 'e' => ['f', 'g', 'h']]],
                    'dynamicContentMap' => [],
                ]
            ],


        ];
    }

    public function testPblishContentUpdateShouldMapParamsToBodyFields()
    {
        $client = SingleContent::create($this->config->setApiKey(self::API_KEY), $this->clientServices);

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('[]');

        $description = 'Test description';
        $content = [
            'key' => 'value',
            'another-key' => 'another-value',
        ];
        $dynamicContentMap = ['some-key' => 'some-value'];

        $this->httpClient->sendRequest(Argument::that(function (RequestInterface $request) use (
            $description,
            $content,
            $dynamicContentMap
        ) {
            // Path params
            $uri = $request->getUri();

            $this->assertSame(
                'https://capi.getjoystick.com/api/v1/config/sample-content-id',
                $uri->__toString()
            );

            // Body
            $bodyDecoded = json_decode((string)$request->getBody(), true);
            $this->assertCount(3, $bodyDecoded);
            $this->assertSame($description, $bodyDecoded['d']);
            $this->assertSame($content, $bodyDecoded['c']);
            $this->assertSame($dynamicContentMap, $bodyDecoded['m']);

            // Headers
            $this->assertSame([self::API_KEY], $request->getHeader('x-api-key'));
            $this->assertSame(['application/json'], $request->getHeader('content-type'));
            return true;
        }))->willReturn($response)->shouldBeCalled();

        $client->publishContentUpdate(
            'sample-content-id',
            [
                'description' => $description,
                'content' => $content,
                'dynamicContentMap' => $dynamicContentMap,
            ]
        );
    }
}

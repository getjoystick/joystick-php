<?php

declare(strict_types=1);

namespace Joystick\Tests;

use Joystick\ClientConfig;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ClientConfigTest extends TestCase
{
    private $httpClient;
    private $httpClientSecond;
    private $requestFactory;
    private $requestFactorySecond;
    private $streamFactory;
    private $streamFactorySecond;

    protected function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->httpClientSecond = $this->prophesize(ClientInterface::class);
        $this->requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $this->requestFactorySecond = $this->prophesize(RequestFactoryInterface::class);
        $this->streamFactory = $this->prophesize(StreamFactoryInterface::class);
        $this->streamFactorySecond = $this->prophesize(StreamFactoryInterface::class);
    }

    public function testCreateDefaults()
    {
        $config = ClientConfig::create();
        $this->assertInstanceOf(ClientInterface::class, $config->getHttpClient());
        $this->assertInstanceOf(RequestFactoryInterface::class, $config->getRequestFactory());
        $this->assertInstanceOf(StreamFactoryInterface::class, $config->getStreamFactory());
        $this->assertSame($config->getExpiration(), ClientConfig::DEFAULT_EXPIRATION_TIME_MINS);
    }

    public function testClone()
    {
        $BEFORE_CLONE = [
            'user_id' => 'user-id-before-clone',
            'api_key' => 'api-key-before-clone',
            'expiration' => 777,
            'sem_ver' => '0.0.1',
            'params' => [
                'key' => 'before-value'
            ]
        ];
        $AFTER_CLONE = [
            'user_id' => 'user-id-after-clone',
            'api_key' => 'api-key-after-clone',
            'expiration' => 888,
            'sem_ver' => '0.0.2',
            'params' => [
                'key' => 'after-value'
            ]
        ];


        $config = ClientConfig::create()
            ->setUserId($BEFORE_CLONE['user_id'])
            ->setApiKey($BEFORE_CLONE['api_key'])
            ->setExpiration($BEFORE_CLONE['expiration'])
            ->setParams($BEFORE_CLONE['params'])
            ->setSemVer($BEFORE_CLONE['sem_ver'])
            ->setHttpClient($this->httpClient->reveal())
            ->setRequestFactory($this->requestFactory->reveal())
            ->setStreamFactory($this->streamFactory->reveal());

        $configCloned = clone $config;


        $config->setUserId($AFTER_CLONE['user_id'])
            ->setApiKey($AFTER_CLONE['api_key'])
            ->setExpiration($AFTER_CLONE['expiration'])
            ->setSemVer($AFTER_CLONE['sem_ver'])
            ->setParams($AFTER_CLONE['params'])
            ->setHttpClient($this->httpClientSecond->reveal())
            ->setRequestFactory($this->requestFactorySecond->reveal())
            ->setStreamFactory($this->streamFactorySecond->reveal());

        $this->assertSame($BEFORE_CLONE['user_id'], $configCloned->getUserId());
        $this->assertSame($BEFORE_CLONE['api_key'], $configCloned->getApiKey());
        $this->assertSame($BEFORE_CLONE['expiration'], $configCloned->getExpiration());
        $this->assertSame($BEFORE_CLONE['params'], $configCloned->getParams());
        $this->assertSame($BEFORE_CLONE['sem_ver'], $configCloned->getSemVer());
        $this->assertSame($this->httpClient->reveal(), $configCloned->getHttpClient());
        $this->assertSame($this->requestFactory->reveal(), $configCloned->getRequestFactory());
        $this->assertSame($this->streamFactory->reveal(), $configCloned->getStreamFactory());
    }

    public function testSetParamValue()
    {
        $config = ClientConfig::create();
        $config->setParams([
            'setViaSetParams' => 'value',
        ])
            ->setParamValue('setViaSetParamValue', 'param-value')
            ->setParamValue('secondSetViaSetParamValue', 'second-param-value');

        $this->assertEqualsCanonicalizing(
            [
                'setViaSetParams' => 'value',
                'setViaSetParamValue' => 'param-value',
                'secondSetViaSetParamValue' => 'second-param-value'
            ],
            $config->getParams()
        );
    }

    /**
     * @dataProvider validSemverData
     */
    public function testValidSemver($semVer)
    {
        $config = ClientConfig::create();
        $config->setSemVer($semVer);
        $this->assertSame($semVer, $config->getSemVer());
    }

    public function validSemverData()
    {
        return [
            ['0.0.1'],
            ['0.1.1'],
            ['1.0.1'],
            ['1.2.2'],
            ['10.20.30'],
            ['10.20.30'],
        ];
    }
    /**
     * @dataProvider invalidSemverData
     */
    public function testInvalidSemver($semVer)
    {
        $this->expectException(\LogicException::class);
        $config = ClientConfig::create();
        $config->setSemVer($semVer);
    }

    public function invalidSemverData()
    {
        return [
            ['0.0.0-prerelease'],
            ['0.0.1-beta'],
            ['1.0.-1'],
            ['1.-2.2'],
            ['-1.20.30'],
            ['1.00.00'],
            ['01.02.03'],
        ];
    }
}

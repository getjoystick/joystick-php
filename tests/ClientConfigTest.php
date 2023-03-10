<?php

declare(strict_types=1);

namespace Joystick\Tests;

use Joystick\ClientConfig;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;

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
        $this->assertInstanceOf(CacheInterface::class, $config->getCache());
        $this->assertSame(false, $config->getSerialized());
        $this->assertSame($config->getCacheExpirationSeconds(), ClientConfig::DEFAULT_EXPIRATION_TIME_SECONDS);
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
            ],
            'serialized' => true,
        ];
        $AFTER_CLONE = [
            'user_id' => 'user-id-after-clone',
            'api_key' => 'api-key-after-clone',
            'expiration' => 888,
            'sem_ver' => '0.0.2',
            'params' => [
                'key' => 'after-value'
            ],
            'serialized' => false,
        ];


        $config = ClientConfig::create()
            ->setUserId($BEFORE_CLONE['user_id'])
            ->setApiKey($BEFORE_CLONE['api_key'])
            ->setCacheExpirationSeconds($BEFORE_CLONE['expiration'])
            ->setParams($BEFORE_CLONE['params'])
            ->setSemVer($BEFORE_CLONE['sem_ver'])
            ->setSerialized($BEFORE_CLONE['serialized'])
            ->setHttpClient($this->httpClient->reveal())
            ->setRequestFactory($this->requestFactory->reveal())
            ->setStreamFactory($this->streamFactory->reveal());

        $configCloned = clone $config;


        $config->setUserId($AFTER_CLONE['user_id'])
            ->setApiKey($AFTER_CLONE['api_key'])
            ->setCacheExpirationSeconds($AFTER_CLONE['expiration'])
            ->setSemVer($AFTER_CLONE['sem_ver'])
            ->setSerialized($AFTER_CLONE['serialized'])
            ->setParams($AFTER_CLONE['params'])
            ->setHttpClient($this->httpClientSecond->reveal())
            ->setRequestFactory($this->requestFactorySecond->reveal())
            ->setStreamFactory($this->streamFactorySecond->reveal());

        $this->assertSame($BEFORE_CLONE['user_id'], $configCloned->getUserId());
        $this->assertSame($BEFORE_CLONE['api_key'], $configCloned->getApiKey());
        $this->assertSame($BEFORE_CLONE['expiration'], $configCloned->getCacheExpirationSeconds());
        $this->assertSame($BEFORE_CLONE['serialized'], $configCloned->getSerialized());
        $this->assertSame($BEFORE_CLONE['params'], $configCloned->getParams());
        $this->assertSame($BEFORE_CLONE['sem_ver'], $configCloned->getSemVer());
        $this->assertSame($this->httpClient->reveal(), $configCloned->getHttpClient());
        $this->assertSame($this->requestFactory->reveal(), $configCloned->getRequestFactory());
        $this->assertSame($this->streamFactory->reveal(), $configCloned->getStreamFactory());
    }

    public function testApiKeyShouldBeString()
    {
        $config = ClientConfig::create();
        $this->expectException(\TypeError::class);
        $config->setApiKey(123);
    }

    public function testApiKeyShouldBeNonEmptyString()
    {
        $config = ClientConfig::create();
        $this->expectException(\InvalidArgumentException::class);
        $config->setApiKey('');
    }

    public function testUserIdShouldBeAString()
    {
        $config = ClientConfig::create();
        $this->expectException(\TypeError::class);
        $config->setUserId(123);
    }

    public function testSetParamValueBeforeSettingParams()
    {
        $config = ClientConfig::create();
        $config
            ->setParamValue('setViaSetParamValue', 'param-value')
            ->setParamValue('secondSetViaSetParamValue', 'second-param-value');

        $this->assertEqualsCanonicalizing(
            [
                'setViaSetParamValue' => 'param-value',
                'secondSetViaSetParamValue' => 'second-param-value'
            ],
            $config->getParams()
        );
    }


    public function testSetParamValueAfterSetParamsIsCalled()
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

    public function testSettingParamsWithComplexValues()
    {
        $nested_array = [[['array_value']], ['nested-object' => 'value']];
        $std_class = new \stdClass();

        $config = ClientConfig::create();
        $config->setParams([
            'stdClass' => $std_class,
            'nestedArray' => $nested_array,
        ])
            ->setParamValue('boolean', true)
            ->setParamValue('null', null);

        $this->assertEqualsCanonicalizing(
            [
                'stdClass' => $std_class,
                'nestedArray' => $nested_array,
                'boolean' => true,
                'null' => null,
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

    public static function validSemverData()
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

    public static function invalidSemverData()
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

    public function testExceptionOnWrongTypeForCacheExpirationSeconds()
    {
        $config = ClientConfig::create();
        $this->expectException(\TypeError::class);
        $config->setCacheExpirationSeconds('123');
    }

    public function testExceptionOnNegativeExpirationSeconds()
    {
        $config = ClientConfig::create();
        $this->expectException(\InvalidArgumentException::class);
        $config->setCacheExpirationSeconds(-1);
    }


    public function testNonBooleanSerializedProperty()
    {
        $config = ClientConfig::create();
        $this->expectException(\TypeError::class);
        $config->setSerialized('true');
    }
}

<?php

declare(strict_types=1);

namespace Joystick\Apis;

use Assert\Assert;
use Joystick\ClientConfig;
use Joystick\ClientServices;
use Joystick\Utils\JsonEncodableValidator;
use Psr\Http\Client\ClientExceptionInterface;

class SingleContent extends AbstractApi
{
    /**
     * @var JsonEncodableValidator
     */
    private $jsonEncodableValidator;


    /**
     * @param ClientConfig $config
     * @param ClientServices $clientServices
     * @return SingleContent
     */
    public static function create(ClientConfig $config, ClientServices $clientServices): SingleContent
    {
        $result = new self($config, $clientServices);
        $result->jsonEncodableValidator = JsonEncodableValidator::create();
        return $result;
    }

    /**
     * @param string $contentId
     * @param array{description: string, content: mixed, dynamicContentMap?: mixed[]} $params
     * @return void
     * @throws ClientExceptionInterface
     */
    public function publishContentUpdate(string $contentId, array $params)
    {
        $this->validatePublishContentUpdateParams($contentId, $params);

        $body = [
            'd' => $params['description'],
            'c' => $params['content'],
            'm' => $params['dynamicContentMap'] ?? []
        ];

        $this->makeJoystickRequest(
            'PUT',
            'https://capi.getjoystick.com/api/v1/config/' . $contentId,
            $body
        );
    }

    /**
     * @param string $contentId
     * @param array{description: string, content: mixed, dynamicContentMap?: mixed[]} $params
     * @return void
     */
    private function validatePublishContentUpdateParams(string $contentId, array $params)
    {
        // Make sure that required fields are populated
        Assert::lazy()
            ->that($params)
            ->keyIsset('description')
            ->keyIsset('content')
            ->verifyNow();


        // Fields are valid
        Assert::lazy()
            ->that($contentId, 'Content ID')->string()->notEmpty()
            ->that($params['description'], 'description')->string()->maxLength(50)->minLength(1)
            ->that($params['dynamicContentMap'] ?? [], 'dynamicContentMap')->isArray()
            ->verifyNow();

        // And can be encoded to JSON
        $this->jsonEncodableValidator->validate($params['content'], 'Field `content`');
        $this->jsonEncodableValidator->validate($params['dynamicContentMap'] ?? [], 'Field `dynamicContentMap`');
    }
}

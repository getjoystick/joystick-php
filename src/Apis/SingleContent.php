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

    protected function __construct(ClientConfig $config, ClientServices $clientServices)
    {
        parent::__construct($config, $clientServices);
        $this->jsonEncodableValidator = JsonEncodableValidator::create();
    }

    /**
     * @param string $contentId
     * @param array $params
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

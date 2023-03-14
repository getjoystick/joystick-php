<?php

declare(strict_types=1);

namespace Joystick\Utils;

use Joystick\Exceptions\NotJsonEncodable;

final class JsonEncodableValidator
{
    public static function create(): JsonEncodableValidator
    {
        return new static();
    }

    /**
     * @param mixed $value
     * @param string $paramName
     * @return void
     * @throws NotJsonEncodable
     */
    public function validate($value, string $paramName)
    {
        json_encode($value);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw NotJsonEncodable::create($paramName);
        }
    }
}

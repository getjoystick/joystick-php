<?php

declare(strict_types=1);

namespace Joystick\Utils;

use Joystick\Exceptions\NotJsonEncodable;

class JsonEncodableValidator
{
    public static function create()
    {
        return new static();
    }

    /**
     * @param $value
     * @param $paramName
     * @throws NotJsonEncodable
     * @return void
     */
    public function validate($value, $paramName)
    {
        json_encode($value);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw NotJsonEncodable::create($paramName);
        }
    }
}

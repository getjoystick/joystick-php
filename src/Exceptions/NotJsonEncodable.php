<?php

declare(strict_types=1);

namespace Joystick\Exceptions;

class NotJsonEncodable extends \InvalidArgumentException
{
    public static function create(string $paramName): self
    {
        return new self("$paramName can not be encoded to JSON");
    }
}

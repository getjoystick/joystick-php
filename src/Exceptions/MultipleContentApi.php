<?php

declare(strict_types=1);

namespace Joystick\Exceptions;

class MultipleContentApi extends \RuntimeException implements JoystickApi
{
    public static function create($errors): self
    {
        $errorString = "The following errors found when calling Multiple Content API:";
        foreach ($errors as $errorText) {
            $errorString .= "\n-  $errorText";
        }

        return new static($errorString);
    }
}

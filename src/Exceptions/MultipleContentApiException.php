<?php

namespace Joystick\Exceptions;

class MultipleContentApiException extends \RuntimeException implements JoystickApiException
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

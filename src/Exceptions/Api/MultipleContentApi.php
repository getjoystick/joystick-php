<?php

declare(strict_types=1);

namespace Joystick\Exceptions\Api;

final class MultipleContentApi extends Exception
{
    /**
     * @param string[] $errors
     * @return static
     */
    public static function create(array $errors): self
    {
        $errorString = "The following errors found when calling Multiple Content API:";
        foreach ($errors as $errorText) {
            $errorString .= "\n-  $errorText";
        }

        return new static($errorString);
    }
}

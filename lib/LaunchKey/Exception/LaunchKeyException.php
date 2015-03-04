<?php

namespace LaunchKey\Exception;

/**
 * Generic LaunchKey client exception class.
 */
class LaunchKeyException extends \Exception
{

    public function __construct($message = '', array $variables = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct(
            strtr($message, $variables),
            (integer)$code,
            $previous
        );
    }
}

<?php

namespace LaunchKey\Exception;

/**
 * Thrown when a called method has not been implemented.
 */
class NotImplementedException extends LaunchKeyException {

    public function __construct($class, $method)
    {
        parent::__construct(':method is not implemented. :class should be subclassed.', array(
            ':class'  => $class,
            ':method' => $method,
        ));
    }

} // End NotImplementedException

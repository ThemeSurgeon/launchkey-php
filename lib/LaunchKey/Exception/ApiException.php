<?php

namespace LaunchKey\Exception;

/**
 * Thrown when an API error occurs.
 */
class ApiException extends LaunchKeyException {

    public function __construct($guzzle_exception)
    {
        $response = $guzzle_exception->getResponse();
        $body     = $response->json();
        $message  = isset($body['message']) ? $body['message'] : 'Unknown error';
        $code     = (integer) $body['message_code'];

        if ( ! (is_string($message)))
        {
            $message = json_encode($message);
        }

        parent::__construct(
            '[:code] :message (Status :status)', array(
                ':code'    => $code,
                ':message' => $message,
                ':status'  => $response->getStatusCode(),
            ),
            $code
        );
    }

} // End ApiException

<?php

namespace LaunchKey\Exception;

class ApiException extends LaunchKeyException {

    public function __construct($guzzle_exception)
    {
        $response = $guzzle_exception->getResponse();
        $body     = $response->json();
        $message  = $body['message'];
        $code     = (integer) $body['message_code'];

        parent::__construct(
            '[:code] :message (Status :status)', array(
                ':code'    => $code,
                ':message' => $message,
                ':status'  => $response->getStatusCode(),
            ),
            $code,
            $guzzle_exception
        );
    }

} // End ApiException

<?php
namespace LaunchKey\Exception;

use Guzzle\Common\Exception\GuzzleException;

/**
 * Thrown when an API error occurs.
 */
class ApiException extends LaunchKeyException
{

    /**
     * @param \Guzzle\Common\Exception\GuzzleException $guzzleException
     */
    public function __construct(GuzzleException $guzzleException)
    {
        $response = $guzzleException->getResponse();
        $body = $response->json();
        $message = isset($body['message']) ? $body['message'] : 'Unknown error';
        $code = (integer)$body['message_code'];

        if (!(is_string($message))) {
            $message = json_encode($message);
        }

        parent::__construct(
            '[:code] :message (Status :status)',
            array(
                ':code' => $code,
                ':message' => $message,
                ':status' => $response->getStatusCode(),
            ),
            $code
        );
    }
}

<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use LaunchKey\SDK\Cache\Cache;
use LaunchKey\SDK\Domain\AuthRequest;
use LaunchKey\SDK\Domain\AuthResponse;
use LaunchKey\SDK\Domain\DeOrbitCallback;
use LaunchKey\SDK\Domain\PingResponse;
use LaunchKey\SDK\Domain\WhiteLabelUser;
use LaunchKey\SDK\Service\Exception\CommunicationError;
use LaunchKey\SDK\Service\Exception\ExpiredAuthRequestError;
use LaunchKey\SDK\Service\Exception\InvalidCredentialsError;
use LaunchKey\SDK\Service\Exception\InvalidRequestError;
use Psr\Log\LoggerInterface;

/**
 * WordPress native implementation of the ApiService that is guaranteed to work on WordPress installs.
 *
 * @package LaunchKey\SDK\Service
 */
class WordPressApiService extends AbstractApiService implements ApiService
{
	/**
	 * @var \WP_Http
	 */
	private $http;

	/**
	 * @var CryptService
	 */
	private $cryptService;

	/**
	 * @var int
	 */
	private $appKey;

	/**
	 * @var string
	 */
	private $secretKey;

	/**
	 * @var bool
	 */
	private $sslVerify;

	/**
	 * @var string
	 */
	private $apiBaseURL;

	/**
	 * @var int
	 */
	private $requestTimeout;

	/**
     * WordPressApiService constructor.
     *
     * @param \WP_Http $http
     * @param CryptService $cryptService
     * @param Cache $cache
     * @param $publicKeyTTL
     * @param $appKey
     * @param $secretKey
     * @param LoggerInterface $logger
     */
    public function __construct(
        \WP_Http $http,
        CryptService $cryptService,
        Cache $cache,
        $publicKeyTTL,
        $appKey,
        $secretKey,
        $sslVerify,
        $apiBaseURL,
        $requestTimeout,
        LoggerInterface $logger = null
    ) {
	    parent::__construct($cache, $cryptService, $secretKey, $publicKeyTTL, $logger);
	    $this->http = $http;
	    $this->cryptService = $cryptService;
	    $this->appKey = $appKey;
	    $this->secretKey = $secretKey;
	    $this->sslVerify = $sslVerify;
	    $this->apiBaseURL = $apiBaseURL;
	    $this->requestTimeout = $requestTimeout;
    }

    /**
     * Perform a ping request
     * @return PingResponse
     * @throws CommunicationError
     * @throws Exception\InvalidResponseError
     * @throws Exception\LaunchKeyEngineError
     * @throws Exception\NoPairedDevicesError
     * @throws Exception\NoSuchUserError
     * @throws Exception\RateLimitExceededError
     * @throws ExpiredAuthRequestError
     * @throws InvalidCredentialsError
     * @throws InvalidRequestError
     */
    public function ping()
    {
        $data = $this->sendRequest('/v1/ping', 'GET');
        $pingResponse = new PingResponse(
            $this->getLaunchKeyDate($data["launchkey_time"]),
            $data["key"],
            $this->getLaunchKeyDate($data["date_stamp"])
        );
        return $pingResponse;
    }

    /**
     * Perform an "auth" request
     *
     * @param string $username Username to authorize
     * @param bool $session Is the request for a user session and not a transaction
     *
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws NoPairedDevicesError If the account for the provided username has no paired devices with which to respond
     * @throws NoSuchUserError If the username provided does not exist
     * @throws RateLimitExceededError If the same username is requested to often and exceeds the rate limit
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     * @return AuthRequest
     */
    public function auth( $username, $session )
    {
        // TODO: Implement auth() method.
    }

    /**
     * Poll to see if the auth request is completed and approved/denied
     *
     * @param string $authRequest auth_request returned from an auth call
     *
     * @return AuthResponse
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     * @throws ExpiredAuthRequestError If the auth request has expired
     */
    public function poll( $authRequest )
    {
        // TODO: Implement poll() method.
    }

    /**
     * Update the LaunchKey Engine with the current status of the auth request or user session
     *
     * @param string $authRequest auth_request returned from an auth call
     * @param string $action Action to log.  i.e. Authenticate, Revoke, etc.
     * @param bool $status
     *
     * @return null
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     * @throws ExpiredAuthRequestError If the auth request has expired
     * @throws LaunchKeyEngineError If the LaunchKey cannot apply the request auth request, action, status
     */
    public function log( $authRequest, $action, $status )
    {
        // TODO: Implement log() method.
    }

    /**
     * Create a white label user with the following identifier
     *
     * @param $identifier Unique and permanent identifier for the user in the white label application.  This identifier
     * will be used in all future communications regarding this user.  As such, it cannot ever change.
     *
     * @return WhiteLabelUser
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     */
    public function createWhiteLabelUser( $identifier )
    {
        // TODO: Implement createWhiteLabelUser() method.
    }

    /**
     * Handle a LaunchKey engine callback with the query parameters from the callback POST call
     *
     * @param array $queryParameters Query parameters from the callback POST call
     *
     * @return AuthResponse|DeOrbitCallback Object generated by processing the provided $postData
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     * @throws InvalidResponseError If the encrypted data is not valid JSON
     */
    public function handleCallback( array $queryParameters )
    {
        // TODO: Implement handleCallback() method.
    }

    private function getUrl($path) {
        return $this->apiBaseURL . $path;
    }

    /**
     * @return array
     * @throws CommunicationError
     * @throws Exception\InvalidResponseError
     * @throws Exception\LaunchKeyEngineError
     * @throws Exception\NoPairedDevicesError
     * @throws Exception\NoSuchUserError
     * @throws Exception\RateLimitExceededError
     * @throws ExpiredAuthRequestError
     * @throws InvalidCredentialsError
     * @throws InvalidRequestError
     */
    private function sendRequest($path, $method, $body = null)
    {
        $this->debugLog("Sending request", array('path' => $path, 'method' => $method, 'body' => $body));
        $response = $this->http->request( $this->getUrl( $path ), array(
            'method'      => $method,
            'timeout'     => $this->requestTimeout,
            'redirection' => 0,
            'httpversion' => '1.1',
            'sslverify'   => $this->sslVerify,
            'body'        => $body
        ) );

        if ($response instanceof \WP_Error) {
            $msg = implode( ' => ', $response->get_error_messages() );
            throw new CommunicationError( $msg );
        } else {
            $this->debugLog("Response received", array($response));
            $data = $this->jsonDecodeData( $response['body'] );
            if ($response['response']['code'] !== 200) {
                $this->throwExceptionForErrorResponse( $data );
            }
        }

        return $data;
    }
}

<?php

namespace LaunchKey;

use LaunchKey\Exception\LaunchKeyException;
use LaunchKey\Exception\ApiException;
use LaunchKey\Exception\NotImplementedException;
use LaunchKey\Http\Client as HttpClient;

/**
 * LaunchKey
 *
 * API SDK that can be used to authorize users, check existing auth requests, and notify LaunchKey for logging.
 *
 * @author   LaunchKey  <developers@launchkey.com>
 * @package  LaunchKey
 */
class Client implements \ArrayAccess {

    private $config;

    private $http_client;

    private $pinged_at = 0;

    private $ping_difference = 0;

    /**
     * Instantiates a new `Client` with optionally supplied `$config`. If no
     * `$config` is supplied, {@link LaunchKey::$config} is used.
     *
     * @param  array|Config  $config  The configuration to use.
     */
    public function __construct($config = NULL)
    {
        if ($config === NULL)
        {
            $this->config = LaunchKey::$config;
        }
        elseif ($config instanceof Config)
        {
            $this->config = clone $config;
        }
        elseif (is_array($config) OR ($config instanceof \Traversable))
        {
            $this->config = new Config($config);
        }
        else
        {
            throw new LaunchKeyException('Unknown configuration supplied');
        }

        $this->http_client = new HttpClient($this);
    }

    /**
     * Starts the authorization process for the supplied `$username`.
     *
     * @example Start authorization for a user.
     *     $auth_request = LaunchKey::authorize('bill');
     *     // => "lyyk9ai..."
     *
     * @param   string   $username  Username of LaunchKey user to authenticate.
     * @param   boolean  $session   `TRUE` for session auth, `FALSE` for transactional.
     * @param   boolean  $user_push_id   If your app would like to be returned an ID for the user
     * that can be used to initiate notifications in the future without user input set TRUE.
     * @return  string   An authorization request token.
     * @throws  LaunchKey\Exception\ApiException
     */
    public function authorize($username, $session = TRUE, $user_push_id = FALSE)
    {
        $params = array(
            'username' => $username,
            'session'  => $session,
            'user_push_id'  => $user_push_id,
        );

        $response = $this->http_client->post('auths', $params);
        return $response['auth_request'];
    }

    /**
     * Checks the status of the authorization process for the supplied
     * `$auth_request` (returned by `authorize()`).
     *
     * @example Poll an auth request.
     *     $auth_request = LaunchKey::authorize('bob');
     *     $response     = FALSE;
     *
     *     while ( ! $response)
     *     {
     *         // Check periodically until a response is given
     *         sleep(1);
     *         LaunchKey::poll($auth_request);
     *     }
     *
     *     // Move on to check the response...
     *
     * @param   string         $auth_request  The auth request token to check.
     * @return  boolean|array  `FALSE` when pending, otherwise the response array.
     * @throws  LaunchKey\Exception\ApiException
     */
    public function poll($auth_request)
    {
        try
        {
            return $this->http_client->get('poll', array(
                'auth_request' => $auth_request,
            ));
        }
        catch (ApiException $e)
        {
            if ($e->getCode() == 70403)
            {
                // Pending response
                return FALSE;
            }
            else
            {
                // Rethrow the exception
                throw $e;
            }
        }
    }

    /**
     * @see  poll
     */
    public function poll_request($auth_request)
    {
        return $this->poll($auth_request);
    }

    /**
     * Checks whether the user accepted to declined authorization in an auth
     * response returned by `poll()`.
     *
     * @example Checking authorization.
     *
     *     $response = LaunchKey::poll($auth_request);
     *
     *     if (LaunchKey::is_authorized($response['auth']))
     *     {
     *         // User allowed the request
     *     }
     *     else
     *     {
     *         // User declined the request
     *     }
     *
     * @param   string       $auth_response  The auth response to validate.
     * @param   string|null  $auth_request   The auth request.
     * @return  boolean      Whether the authorization attempt was accepted or declined.
     * @throws  LaunchKey\Exception\ApiException
     */
    public function is_authorized($auth_response, $auth_request = NULL)
    {
        $auth  = $this->load_auth($auth_response);
        $valid = $this->is_valid_auth($auth, $auth_request);

        if($valid == false) {
            $valid = 0;
        }

        return $this->notify('authenticate', $valid, $auth['auth_request']);
    }

    /**
     *  Verifies the authenticity of a webhook request from LaunchKey,
     *  returning the `user_hash` if successful.
     *
     *  @example
     *      if ($user_hash = LaunchKey::deorbit($_GET))
     *      {
     *          // Deorbit is valid
     *      }
     *      else
     *      {
     *          // Deorbit is invalid or expired
     *      }
     *
     * @param   array  $params  Parameters supplied by LaunchKey in webhook request.
     * @return  boolean|string  The user hash when request is valid, `FALSE` otherwise
     */
    public function deorbit(array $params = array())
    {
        $deorbit   = (isset($params['deorbit'])) ? (string) $params['deorbit'] : '';
        $signature = (isset($params['signature'])) ? (string) $params['signature'] : '';

        if ( ! $this['api_public_key']->verify($signature, $deorbit))
        {
            return FALSE;
        }

        $payload   = json_decode($deorbit, TRUE);
        $timestamp = strtotime($payload['launchkey_time']) - $this->ping_difference;

        // Deorbits expire after 5 minutes
        return ($timestamp > time() - 5 * 60) ? $payload['user_hash'] : FALSE;
    }

    /**
     * Notifies LaunchKey to confirm that the user's session has ended.
     *
     * @param   string   $auth_request  The auth request of the session.
     * @return  boolean  `TRUE` when successful
     */
    public function deauthorize($auth_request)
    {
        return $this->notify('revoke', TRUE, $auth_request);
    }

    /**
     * @see  deauthorize
     */
    public function logout($auth_request)
    {
        return $this->deauthorize($auth_request);
    }

    /**
     * Notifies LaunchKey as to whether the user was logged in/out.
     *
     * @param  string       $action
     * @param  boolean      $status
     * @param  string|null  $auth_request
     * @internal
     */
    public function notify($action, $status, $auth_request = NULL)
    {
        $params = array(
            'action'       => ucfirst($action),
            'status'       => $status,
            'auth_request' => $auth_request,
        );

        $this->http_client->put('logs', $params);

        return $status;
    }

    /**
     * Tests if supplied `$device` and `$app_pins` match persisted records of
     * previous `$app_pins`.
     *
     * @param   $device_id  Device identifier to check.
     * @param   $app_pins   Current application pins.
     * @return  boolean     `TRUE` if pins are valid, `FALSE` otherwise.
     */
    public function has_valid_pins($device_id, $app_pins)
    {
        throw new NotImplementedException(__CLASS__, __METHOD__);
        $user = $this->get_user_hash();
        $pins = $this->get_existing_pins($user, $device);
        $update = False;

        if(substr_count($app_pins, ',') == 0 && trim($pins) == "") {
            $update = True;
        } elseif (substr_count($app_pins, ',') > 0) {
            $update = True;
        }

        if($update) {
            $this->update_pins($user, $device, $app_pins);
        }
    }

    /**
     * @see  has_valid_pins
     */
    public function pins_valid($app_pins, $device)
    {
        return $this->has_valid_pins($device, $app_pins);
    }

    /**
     * get_user_hash - get the user hash for this request
     *
     */
    public function get_user_hash()
    {
        throw new NotImplementedException(__CLASS__, __METHOD__);
    } //end get_user_hash

    /**
     * get_existing_pins - get string of all PINs comma delimited that exist for the user already from persistent store
     *
     * @param  $user
     * @param  $device
     */
    public function get_existing_pins($user, $device)
    {
        throw new NotImplementedException(__CLASS__, __METHOD__);
    } //end get_existing_pins

    /**
     * update_pins - Update the persistent store with the latest PINs
     *
     * @param  $user
     * @param  $device
     * @param  $pins
     */
    public function update_pins($user, $device, $pins)
    {
        throw new NotImplementedException(__CLASS__, __METHOD__);
    } //end update_pins

    public function offsetGet($option)
    {
        if ($option == 'api_public_key')
        {
            return $this->api_public_key();
        }

        return $this->config[$option];
    }

    public function offsetSet($option, $value)
    {
        throw new Exception\LaunchKeyException('client config is read-only');
    }

    public function offsetExists($option)
    {
        return array_key_exists($option, $this->config);
    }

    public function offsetUnset($option)
    {
        throw new Exception\LaunchKeyException('client config is read-only');
    }

    /**
     * @internal
     */
    public function api_public_key()
    {
        if ($this->config['api_public_key'] === NULL)
        {
            $this->ping();
        }

        return $this->config['api_public_key'];
    }

    /**
     * Used to retrieve the API's public key and server time.
     *
     * @return  integer  Current unix timestamp with time difference.
     * @see     ping_time
     * @internal
     */
    public function ping()
    {
        if ( ! $this->should_ping() AND $this->config['api_public_key'] !== NULL)
            return;

        $this->pinged_at = time();

        $response = $this->http_client->get('ping');

        $this->config['api_public_key'] = $response['key'];
        return $this->ping_time(strtotime($response['launchkey_time']));
    }

    public function ping_timestamp()
    {
        return strftime('%Y-%m-%d %H:%M:%S', $this->ping_time());
    }

    public function ping_time($value = NULL)
    {
        if ($value !== NULL)
        {
            $this->ping_difference = $value - time();
        }

        return time() + $this->ping_difference;

    }

    private function should_ping()
    {
        return (time() - 5 * 60 > $this->pinged_at);
    }

    /**
     * @internal
     */
    protected function is_valid_auth($auth, $auth_request = NULL)
    {
        if ($auth_request === NULL)
        {
            $auth_request = $auth['auth_request'];
        }

        $pins_valid = FALSE;

        try
        {
            $pins_valid = $this->has_valid_pins($auth['device_id'], $auth['app_pins']);
        }
        catch (NotImplementedException $e)
        {
            $pins_valid = true;
        }

        $success = filter_var($auth['response'], FILTER_VALIDATE_BOOLEAN);

        return ($pins_valid AND $success AND isset($auth['auth_request']) AND
            $auth['auth_request'] == $auth_request);
    }

    /**
     * @internal
     */
    protected function load_auth($crypted_auth)
    {
        return json_decode($this->decrypt_auth($crypted_auth), TRUE);
    }

    /**
     * @internal
     */
    protected function decrypt_auth($crypted_auth)
    {
        return $this['keypair']->private_decrypt($crypted_auth);
    }

} // End Client

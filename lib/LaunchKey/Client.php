<?php

/**
 * LaunchKey
 *
 * API SDK that can be used to authorize users, check existing auth requests, and notify LaunchKey for logging.
 *
 * @author LaunchKey <developers@launchkey.com>
 * @package LaunchKey
 */
class LaunchKey_Client
{
    private $api_host = "https://api.launchkey.com";
    private $api_public_key;
    private $app_key;
    private $app_secret;
    private $private_key;
    private $domain;
    private $ping_time;
    private $ping_difference;

    /**
     * __construct
     *
     * @param mixed $app_key
     * @param mixed $app_secret
     * @param mixed $private_key
     * @param mixed $domain
     * @param string $version
     * @access protected
     */
    public function __construct($app_key, $app_secret, $private_key, $domain, $version = "v1")
    {
        $this->app_key = $app_key;
        $this->app_secret = $app_secret;
        $this->private_key = $private_key;
        $this->domain = $domain;
        $this->api_host = $this->api_host . "/" . $version . "/";
    } //end __construct

    /**
     * ping - used to retrieve the API's public key and server time
     *
     * @access public
     * @return string
     */
    public function ping()
    {
        if (empty($this->ping_time)) {
            $response = $this->json_curl($this->api_host . "ping", "GET");
            $json_response = json_decode($response, true);
            $this->api_public_key = $json_response['key'];
            $this->ping_time = $json_response['launchkey_time'];
            $this->ping_difference = time();
        } else {
            $this->ping_time = (time() - $this->ping_difference) + $this->ping_time;
        }
    } //end ping

    /**
     * prepare_auth - encrypts app_secret with RSA key and signs
     *
     * @access public
     * @return string
     */
    public function prepare_auth()
    {
        if (empty($this->api_public_key)) {
            $this->ping();
        }
        $data = array('secret' => $this->app_secret, 'stamped' => $this->ping_time);
        $to_encrypt = json_encode($data);
        $encrypted_app_secret = $this->rsa_encrypt($this->api_public_key, $to_encrypt);
        $signature = $this->rsa_sign($this->private_key, $encrypted_app_secret);
        $auth_array = array('app_key' => $this->app_key,
            'secret_key' => $encrypted_app_secret,
            'signature' => $signature);
        return $auth_array;
    } //end prepare_auth

    /**
     * authorize - used to send an authorization request for a specific username
     *
     * @param mixed $username
     * @throws Exception
     * @access public
     * @return string
     */
    public function authorize($username, $session = TRUE)
    {
        $params = $this->prepare_auth();
        $params['username'] = $username;
        $params['session'] = $session;
        $response = $this->json_curl($this->api_host . "auths", "POST", $params);
        $response = json_decode($response, true);

        if (isset($response['status_code'])) {
            throw new Exception('Error code returned: ' . $response['status_code']);
        }
        return $response['auth_request'];
    } //end authorize

    /**
     * poll_request - poll the API to find the status of an authorization request
     *
     * @param mixed $auth_request
     * @access public
     * @return array
     */
    public function poll_request($auth_request)
    {
        $params = $this->prepare_auth();
        $params['auth_request'] = $auth_request;
        $response = $this->json_curl($this->api_host . "poll", "GET", $params);
        return json_decode($response, true);
    } //end poll_request

    /**
     * is_authorized - returns boolean value based on whether user has denied or accepted the authorization
     * request and it has passed all security checks
     *
     * @param mixed $package
     * @access public
     * @return boolean
     */
    public function is_authorized($package, $auth_request='')
    {
        $auth_response = json_decode($this->rsa_decrypt($this->private_key, $package), true);

        if (!isset($auth_response['response']) || !isset($auth_response['auth_request']) || $auth_response['auth_request'] != $auth_request ) {
            return $this->notify("Authenticate", "False");
        }

        $pins_valid = False;
        try {
           $pins_valid = $this->pins_valid($auth_response['app_pins'], $auth_response['device_id']);
        } catch (Exception $e) {
           $pins_valid = True;
        }

        if($pins_valid) {
            $response = strtolower($auth_response['response']) === 'true' ? 'True':'False';
            return $this->notify("Authenticate", $response, $auth_response['auth_request']);
        }

        return False;
    } // end is_authorized

    /**
     * notify - notifies LaunchKey as to whether the user was logged in/out
     *
     * @param string $action
     * @param boolean $status
     * @param string $auth_request
     * @param string $username
     * @access public
     * @return boolean
     */
    public function notify($action, $status, $auth_request = "", $username = "")
    {
        $params = $this->prepare_auth();
        if ($username != "") {
            $params['username'] = $username;
        }
        $params['action'] = $action;
        $params['status'] = $status;
        $params['auth_request'] = $auth_request;
        $response = json_decode($this->json_curl($this->api_host . "logs", "PUT", $params), True);

        if(isset($response['message']) && $response['message'] == "Successfully updated") {
            $status_boolean = strtolower($status) === 'true' ? True:False;
            return $status_boolean;
        }

        return False;
    } //end notify

    /**
     * deorbit - verify the deorbit request by signature and timestamp, return the user_hash needed to identify the
     * user and log them out
     *
     * @param string $orbit
     * @param string $signature
     * @return NULL
     */
    public function deorbit($orbit, $signature)
    {
        $this->ping();
        if ($this->rsa_verify_sign($this->api_public_key, $signature, $orbit)) {
            $decoded = json_decode($orbit, true);
            $date_request = $decoded['launchkey_time'];
            if (($this->ping_time - $date_request) > 300) {
                return $decoded['user_hash'];
            }
        }
        return NULL;
    } //end deorbit

    /**
     * logout - notifies API that the session end has been confirmed
     *
     * @param string $auth_request
     * @access public
     * @return boolean
     */
    public function logout($auth_request)
    {
        return $this->notify("Revoke", "True", $auth_request);
    } //end logout

    /**
     * pins_valid - return boolean for whether the tokens pass or not
     *
     * @param $app_pins
     * @param $device
     * @return boolean
     */
    public function pins_valid($app_pins, $device)
    {
        throw new Exception('Not implemented. Subclass must implement.');
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
    } //end pins_valid

    /**
     * get_user_hash - get the user hash for this request
     *
     */
    public function get_user_hash()
    {
        throw new Exception('Not implemented. Subclass must implement.');

    } //end get_user_hash

    /**
     * get_existing_pins - get string of all PINs comma delimited that exist for the user already from persistent store
     *
     * @param $user
     * @param $device
     */
    public function get_existing_pins($user, $device)
    {
        throw new Exception('Not implemented. Subclass must implement.');
    } //end get_existing_pins

    /**
     * update_pins - Update the persistent store with the latest PINs
     *
     * @param $user
     * @param $device
     * @param $pins
     */
    public function update_pins($user, $device, $pins)
    {
        throw new Exception('Not implemented. Subclass must implement.');
    } //end update_pins

    /**
     * json_curl
     *
     * @param string $url
     * @param string $method
     * @param array $params
     * @access public
     * @return string
     */
    public function json_curl($url, $method = "GET", $params = array())
    {
        if ($method == "GET" && count($params)) {
            $query_params = http_build_query($params);
            $url = $url . "/?" . $query_params;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, '10');

        if ($method != "GET") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    } //end json_curl

    /**
     * rsa_generate - return a public/private keypair
     *
     * @param int $bits
     * @access public
     * @return array
     */
    public function rsa_generate($bits = 2048)
    {
        $rsa = new Crypt_RSA();
        define('CRYPT_RSA_EXPONENT', 65537);
        $keypair = $rsa->createKey($bits);
        return $keypair;
    } //end RSA_generate

    /**
     * rsa_decrypt - decrypt base64 encoded package
     *
     * @param mixed $key
     * @param mixed $package
     * @access public
     * @return string
     */
    public function rsa_decrypt($key, $package)
    {
        $rsa = new Crypt_RSA();
        $rsa->loadKey($key);
        $decrypted = $rsa->decrypt(base64_decode($package));
        return $decrypted;
    } //end rsa_decrypt

    /**
     * rsa_encrypt - encrypt message and return base64 encoded
     *
     * @param mixed $key
     * @param mixed $message
     * @access public
     * @return string
     */
    public function rsa_encrypt($key, $message)
    {
        $rsa = new Crypt_RSA();
        $rsa->loadKey($key);
        $encrypted = base64_encode($rsa->encrypt($message));
        return $encrypted;
    } //end rsa_encrypt

    /**
     * rsa_sign - sign a message with
     *
     * @param mixed $key
     * @param mixed $package
     * @access public
     * @return mixed
     */
    public function rsa_sign($key, $package)
    {
        $rsa = new Crypt_RSA();
        $rsa->setHash("sha256");
        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $rsa->loadKey($key);
        $signature = base64_encode($rsa->sign(base64_decode($package)));
        return $signature;
    } //end rsa_sign

    /**
     * rsa_verify_sign - verify a signed message
     *
     * @param mixed $key
     * @param $signature
     * @param mixed $package
     * @access public
     * @return boolean
     */
    public function rsa_verify_sign($key, $signature, $package)
    {
        $rsa = new Crypt_RSA();
        $rsa->setHash("sha256");
        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $rsa->loadKey($key);
        $verify = $rsa->verify(base64_decode($package), $signature);
        return $verify;
    } //end rsa_verify_sign

} //End LaunchKey

<?php

/**
 * LaunchKey
 *
 * @author LaunchKey <developers@launchkey.com>
 * @package LaunchKey
 */
class LaunchKey {

    private $api_host = "https://api.launchkey.com";
    private $api_public_key;
    private $app_key;
    private $app_secret;
    private $private_key;
    private $domain;
    private $ping_time;

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
    function __construct($app_key, $app_secret, $private_key, $domain, $version="v1") {
        $this->app_key = $app_key;
        $this->app_secret = $app_secret;
        $this->private_key = $private_key;
        $this->domain = $domain;
        $this->api_host = $this->api_host . "/" . $version . "/";
    }

    /**
     * ping
     *
     * @access public
     * @return string
     */
    public function ping() {
        $response = $this->json_curl($this->api_host . "ping", "GET");
        $json_response = json_decode($response, true);
        $this->ping_time = $json_response['launchkey_time'];
        $this->api_public_key = $json_response['key'];
        return $response;
    } //End ping

    /**
     * prepare_auth
     *
     * @access public
     * @return string
     */
    public function prepare_auth() {
        if(empty($this->api_public_key)) {
            $this->ping();
        }
        $to_encrypt = '{"secret": \'' . $this->app_secret . '\', "stamped": \'' . $this->ping_time . '\'}';
        $encrypted_app_secret = $this->rsa_encrypt($this->api_public_key, $to_encrypt);
        $signature = $this->rsa_sign($this->private_key, $encrypted_app_secret);
        $auth_array = array('app_key'=> $this->app_key,
            'secret_key' => $encrypted_app_secret,
            'signature' => $signature);
        return $auth_array;
    } //End prepare_auth

    /**
     * authorize
     *
     * @param mixed $username
     * @throws Exception
     * @access public
     * @return string
     */
    public function authorize($username) {
        $params = $this->prepare_auth();
        $params['username']  = $username;
        $response = $this->json_curl($this->api_host . "auths", "POST", $params);
        $response = json_decode($response, true);

        if (isset($response['status_code'])) {
            throw new Exception('Error code returned: ' . $response['status_code']);
        }
        return $response['auth_request'];
    } //End authorize

    /**
     * poll_request
     *
     * @param mixed $auth_request
     * @access public
     * @return array
     */
    public function poll_request($auth_request) {
        $params = $this->prepare_auth();
        $params['app_key'] = $this->app_key;
        $params['auth_request'] = $auth_request;
        $response = $this->json_curl($this->api_host . "poll", "GET", $params);
        return json_decode($response, true);
    } //End poll_request

    /**
     * is_authorized
     *
     * @param mixed $package
     * @access public
     * @return boolean
     */
    public function is_authorized($package) {
        $auth_response = json_decode($this->rsa_decrypt($this->private_key, $package), true);

        if (!isset($auth_response['response']) || strtolower($auth_response['response']) == "false") {
            $this->notify("Authenticate", "False");
            return False;
        }
        if (strtolower($auth_response['response']) == "true") {
            $this->notify("Authenticate", "True", $auth_response['auth_request']);
            return True;
        }
        return False;
    } // End is_authorized

    /**
     * notify
     *
     * @param string $action
     * @param boolean $status
     * @param string $auth_request
     * @param string $username
     * @access public
     * @return boolean
     */
    public function notify($action, $status, $auth_request="", $username="") {
        $params = $this->prepare_auth();
        if($username != "" ) {
            $params['username'] = $username;
        }
        $params['action'] = $action;
        $params['status'] = $status;
        $params['auth_request'] = $auth_request;
        $this->json_curl($this->api_host . "logs", "PUT", $params);
        return True;
    } //End notify

    /**
     * deorbit
     *
     * @param string $orbit
     * @param string $signature
     * @return NULL
     */
    public function deorbit($orbit, $signature) {
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
     * logout
     *
     * @param string $auth_request
     * @access public
     * @return boolean
     */
    public function logout($auth_request) {
        $this->notify("Revoke", "True", $auth_request);
        return True;
    } //End logout

    /**
     * json_curl
     *
     * @param string $url
     * @param string $method
     * @param array $params
     * @access public
     * @return string
     */
    public function json_curl($url, $method="GET", $params = array()) {
        if($method == "GET" && count($params)) {
            $query_params = http_build_query($params);
            $url = $url . "/?" .  $query_params;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, '6');

        if($method != "GET") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        $result = curl_exec($ch);
        return $result;
    } //End json_curl 

    /**
     * rsa_generate - return a public/private keypair
     *
     * @param int $bits
     * @access public
     * @return array
     */
    public function rsa_generate($bits=2048) {
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
    public function rsa_decrypt($key, $package) {
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
    public function rsa_encrypt($key, $message) {
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
    public function rsa_sign($key, $package) {
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
    public function rsa_verify_sign($key, $signature, $package) {
        $rsa = new Crypt_RSA();
        $rsa->setHash("sha256");
        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $rsa->loadKey($key);
        $verify = $rsa->verify(base64_decode($package), $signature);
        return $verify;
    } //end rsa_verify_sign

} //End LaunchKey

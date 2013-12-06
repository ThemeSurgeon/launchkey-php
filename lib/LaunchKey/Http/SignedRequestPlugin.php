<?php

namespace LaunchKey\Http;

use LaunchKey\LaunchKey;

/**
 * @internal
 */
class SignedRequestPlugin extends Plugin {

    public function call($event)
    {
        $request = $event['request'];

        if ( ! $this->is_ping_request($request))
        {
            $this->sign_request($request);
        }
    }

    public function is_ping_request($request)
    {
        return $request->getPath() == '/'.LaunchKey::API_VERSION.'/ping';
    }

    public function sign_request($request)
    {
        $auth_params = $this->auth_params();

        if ($request->getMethod() == 'GET')
        {
            $this->apply_query_params($auth_params, $request->getQuery());
        }
        else
        {
            $this->apply_post_params($auth_params, $request);
        }
    }

    public function auth_params()
    {
        $secret_key = $this->secret_key();

        return array(
            'app_key'    => (string) $this->client['app_key'],
            'secret_key' => $secret_key,
            'signature'  => $this->signature($secret_key),
        );
    }

    public function apply_query_params($params, $query)
    {
        foreach ($params as $key => $value)
        {
            $query->set($key, $value);
        }
    }

    public function apply_post_params($params, $request)
    {
        foreach ($params as $key => $value)
        {
            $request->setPostField($key, $value);
        }
    }

    private function secret_key()
    {
        $this->client->ping();

        $raw_secret = json_encode(array(
            'secret'  => $this->client['secret_key'],
            'stamped' => $this->client->ping_timestamp(),
        ));

        return $this->client['api_public_key']->public_encrypt($raw_secret);
    }

    private function signature($secret_key)
    {
        return $this->client['keypair']->sign($secret_key);
    }

} // End SignedRequestPlugin

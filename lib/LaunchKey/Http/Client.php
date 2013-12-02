<?php

namespace LaunchKey\Http;

use LaunchKey\LaunchKey;
use Guzzle\Http\Client as Guzzle;

class Client {

    private $client;

    private $adapter;

    public function __construct($client, $adapter = NULL)
    {
        $this->client  = $client;

        if ($adapter === NULL)
        {
            $adapter = $this->default_adapter();
        }

        $this->adapter = $adapter;
    }

    public function get($path = NULL, $params = array(), $headers = array())
    {
        $request = $this->adapter->get($path, $headers);
        $query   = $request->getQuery();

        foreach ($params as $param => $value)
        {
            $query->set($param, $value);
        }

        return $request->send()->json();
    }

    public function post($path = NULL, $body = NULL, $headers = array())
    {
        $request = $this->adapter->post($path, $headers);
        $request->addPostFields($body);

        return $request->send()->json();
    }

    public function put($path = NULL, $body = NULL, $headers = array())
    {
        $request = $this->adapter->put($path, $headers);
        $request->addPostFields($body);

        return $request->send()->json();
    }

    public function user_agent()
    {
        $os_info = php_uname('m').'-'.strtolower(php_uname('s')).php_uname('r');

        return sprintf(
            'launchkey-php/%s (Composer; PHP %s %s)',
            LaunchKey::VERSION, phpversion(), $os_info
        );
    }

    public function endpoint()
    {
        return 'https://'.$this->client['host'].'/'.LaunchKey::API_VERSION.'/';
    }

    public function default_adapter()
    {
        $adapter = new Guzzle($this->endpoint());
        $adapter->addSubscriber(
            new SignedRequestPlugin($this->client)
        );
    }

} // End Client

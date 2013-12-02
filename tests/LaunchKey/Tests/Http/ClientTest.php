<?php

namespace LaunchKey\Http {

    use LaunchKey\Tests\Http\ClientTest;

    function php_uname($mode = 'a')
    {
        if ( ! empty(ClientTest::$mock_php_uname))
        {
            return ClientTest::$mock_php_uname[$mode];
        }
        else
        {
            return \php_uname($mode);
        }
    }

    function phpversion()
    {
        if (ClientTest::$mock_phpversion)
        {
            return ClientTest::$mock_phpversion;
        }
        else
        {
            return \phpversion();
        }
    }

}

namespace LaunchKey\Tests\Http {

    use LaunchKey\Config;
    use LaunchKey\Http\Client;
    use Mockery;

    /**
     * Tests Client
     *
     * @package  LaunchKey
     * @category Tests
     */
    class ClientTest extends \PHPUnit_Framework_TestCase {

        public static $mock_php_uname = array();

        public static $mock_phpversion = FALSE;

        public function test_instantiates_with_default_adapter()
        {
        }

        public function fake_requests()
        {
            return array(
                array(
                    'poll',
                    array(
                        'bananas' => TRUE,
                    ),
                    array(
                        'X-API-Key' => 'foo',
                    ),
                    array(
                        'success' => TRUE,
                    ),
                ),
                array(
                    'auths',
                    array(
                        'username' => 'bob',
                    ),
                    array(
                    ),
                    array(
                        'auth_response' => 'sdlkfjasdflhasdgadsg',
                    ),
                ),
                array(
                    'logs',
                    array(
                        'action'       => 'Revoke',
                        'status'       => TRUE,
                        'auth_request' => 'sdflksdjfsdf',
                        'username'     => 'foo',
                    ),
                    array(
                        'Content-Type' => 'application/json'
                    ),
                    array(
                        'message' => 'RAWR!',
                    ),
                ),
            );
        }

        /**
         * @dataProvider fake_requests
         */
        public function test_get($path, $params, $headers, $expected)
        {
            $mock_response = Mockery::mock('Guzzle\\Http\\Message\\Response');
            $mock_response->shouldReceive('json')
                ->andReturn($expected);

            $mock_query = Mockery::mock('Guzzle\\Http\\QueryString');
            foreach ($params as $key => $value)
            {
                $mock_query->shouldReceive('set')
                    ->with($key, $value);
            }

            $mock_request = Mockery::mock('Guzzle\\Http\\Message\\RequestInterface');
            $mock_request->shouldReceive('getQuery')
                ->andReturn($mock_query);
            $mock_request->shouldReceive('send')
                ->andReturn($mock_response);

            $mock_adapter = Mockery::mock('Guzzle\\Http\\Client');
            $mock_adapter->shouldReceive('get')
                ->with($path, $headers)
                ->andReturn($mock_request);

            $client = new Client(NULL, $mock_adapter);

            $this->assertEquals($expected, $client->get($path, $params, $headers));
        }

        /**
         * @dataProvider fake_requests
         */
        public function test_post($path, $params, $headers, $expected)
        {
            $mock_response = Mockery::mock('Guzzle\\Http\\Message\\Response');
            $mock_response->shouldReceive('json')
                ->andReturn($expected);

            $mock_request = Mockery::mock('Guzzle\\Http\\Message\\RequestInterface');
            $mock_request->shouldReceive('addPostFields')
                ->with($params);
            $mock_request->shouldReceive('send')
                ->andReturn($mock_response);

            $mock_adapter = Mockery::mock('Guzzle\\Http\\Client');
            $mock_adapter->shouldReceive('post')
                ->with($path, $headers)
                ->andReturn($mock_request);

            $client = new Client(NULL, $mock_adapter);

            $this->assertEquals($expected, $client->post($path, $params, $headers));
        }

        /**
         * @dataProvider fake_requests
         */
        public function test_put($path, $params, $headers, $expected)
        {
            $mock_response = Mockery::mock('Guzzle\\Http\\Message\\Response');
            $mock_response->shouldReceive('json')
                ->andReturn($expected);

            $mock_request = Mockery::mock('Guzzle\\Http\\Message\\RequestInterface');
            $mock_request->shouldReceive('addPostFields')
                ->with($params);
            $mock_request->shouldReceive('send')
                ->andReturn($mock_response);

            $mock_adapter = Mockery::mock('Guzzle\\Http\\Client');
            $mock_adapter->shouldReceive('put')
                ->with($path, $headers)
                ->andReturn($mock_request);

            $client = new Client(NULL, $mock_adapter);

            $this->assertEquals($expected, $client->put($path, $params, $headers));
        }

        public function machine_info()
        {
            return array(
                array(
                    array(
                        'm' => 'x86_64',
                        's' => 'Darwin',
                        'r' => '13.0.0',
                    ),
                    '5.5.4',
                    'launchkey-php/0.1.0 (Composer; PHP 5.5.4 x86_64-darwin13.0.0)',
                ),
                array(
                    array(
                        'm' => 'i386',
                        's' => 'FreeBSD',
                        'r' => '6.1-RELEASE-p15',
                    ),
                    '5.3.14',
                    'launchkey-php/0.1.0 (Composer; PHP 5.3.14 i386-freebsd6.1-RELEASE-p15)',
                ),
                array(
                    array(
                        'm' => 'amd64',
                        's' => 'Debian',
                        'r' => '3.2.0-4',
                    ),
                    '5.4.9',
                    'launchkey-php/0.1.0 (Composer; PHP 5.4.9 amd64-debian3.2.0-4)',
                ),
            );
        }

        /**
         * @dataProvider machine_info
         * @runInSeparateProcess
         */
        public function test_user_agent($php_uname, $phpversion, $expected)
        {
            self::$mock_php_uname  = $php_uname;
            self::$mock_phpversion = $phpversion;

            $client = new Client(NULL, NULL);
            $this->assertEquals($expected, $client->user_agent());
        }

        public function endpoint_configurations()
        {
            return array(
                array(
                    array(
                        'host' => 'localhost',
                    ),
                    'https://localhost/v1/',
                ),
                array(
                    array(
                        'host' => 'internal.hosted.launchkey.my-organization.com',
                    ),
                    'https://internal.hosted.launchkey.my-organization.com/v1/',
                ),
                array(
                    new Config,
                    'https://api.launchkey.com/v1/',
                )
            );
        }

        /**
         * @dataProvider endpoint_configurations
         */
        public function test_endpoint($config, $expected)
        {
            $client = new Client($config);
            $this->assertEquals($expected, $client->endpoint());
        }

    } // End ClientTest

}

<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Service\WordPressApiService;

abstract class WordPressApiServiceTestAbstract extends \PHPUnit_Framework_TestCase
{
	/**
     * @Mock
	 * @var \WP_Http
	 */
	protected $client;

    /**
     * @Mock
     * @var \LaunchKey\SDK\Service\CryptService
     */
    protected $cryptService;

    /**
     * @Mock
     * @var \LaunchKey\SDK\Cache\Cache
     */
    protected $cache;

    /**
     * @var int
     */
    protected $publicKeyTTL = 999;

    /**
     * @var WordPressApiService
     */
    protected $apiService;

    /**
     * @var int
     */
    protected $appKey = 1234567890;

    /**
     * @var string
     */
    protected $secretKey = 'super_secret_key';

    /**
     * @var bool
     */
    protected $sslverify = true;

    /**
     * @var int
     */
    protected $requestTimeout = 99;

    /**
     * @Mock
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @Mock
     * @var \WP_Error
     */
    protected $wpError;

    /**
     * @var string
     */
    private $apiBaseUrl = 'https://api.base.url';

    protected function setUp() {
        \Phake::initAnnotations($this);
        \Phake::when($this->wpError)->get_error_messages()->thenReturn(array('error', 'messages'));
        $this->apiService = new WordPressApiService(
            $this->client,
            $this->cryptService,
            $this->cache,
            $this->publicKeyTTL,
            $this->appKey,
            $this->secretKey,
            $this->sslverify,
            $this->apiBaseUrl,
            $this->requestTimeout
        );
        $this->loggingApiService = new WordPressApiService(
            $this->client,
            $this->cryptService,
            $this->cache,
            $this->publicKeyTTL,
            $this->appKey,
            $this->secretKey,
            $this->sslverify,
            $this->apiBaseUrl,
            $this->requestTimeout,
            $this->logger
        );
	}

	protected function tearDown() {
        $this->client = null;
        $this->cryptService = null;
        $this->cache = null;
        $this->publicKeyTTL = null;
        $this->appKey = null;
        $this->secretKey = null;
        $this->sslverify = null;
        $this->apiBaseUrl = null;
        $this->requestTimeout = null;
        $this->logger = null;
		$this->apiService = null;
	}

    abstract function testCallsRequest();

    /**
     * @depends testCallsRequest
     * @param array $options
     */
    public function testRequestRedirectionIsOff(array $options)
    {
        $this->assertEquals(0, $options['redirection']);
    }

    /**
     * @depends testCallsRequest
     * @param array $options
     */
    public function testRequestHttpVersionIsOneDotOne(array $options)
    {
        $this->assertEquals('1.1', $options['httpversion']);
    }

    /**
     * @depends testCallsRequest
     * @param array $options
     */
    public function testRequestTimeoutIsCorrect(array $options)
    {
        $this->assertEquals($this->requestTimeout, $options['timeout']);
    }

    /**
     * @depends testCallsRequest
     * @param array $options
     */
    public function testRequestSslVerify(array $options)
    {
        $this->assertEquals($this->sslverify, $options['sslverify']);
    }
}

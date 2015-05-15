<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Service\WordPressApiService;
use Phake;

abstract class WordPressApiServiceTestAbstract extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    public $response;

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
     * @var int
     */
    protected $appKey = 1234567890;

    /**
     * @var string
     */
    protected $secretKey = 'Secret Key';

    /**
     * @var bool
     */
    protected $sslverify = true;

    /**
     * @var int
     */
    protected $requestTimeout = 99;

    /**
     * @var string
     */
    protected $rsaEncrypted = "RSA Encrypted";

    /**
     * @var string
     */
    protected $signed = "Signed";

    /**
     * @var string
     */
    protected $publicKey = "Public Key";

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

    /**
     * @var WordPressApiService
     */
    protected $apiService;

    /**
     * @var WordPressApiService
     */
    protected $loggingApiService;

    protected function setUp() {
        Phake::initAnnotations($this);
        Phake::when($this->wpError)->get_error_messages()->thenReturn(array('error', 'messages'));
        Phake::when($this->cryptService)->encryptRSA(Phake::anyParameters())->thenReturn($this->rsaEncrypted);
        Phake::when($this->cryptService)->sign(Phake::anyParameters())->thenReturn($this->signed);
        Phake::when($this->cache)->get(WordPressApiService::CACHE_KEY_PUBLIC_KEY)->thenReturn($this->publicKey);
        $that = $this;
        phake::when($this->client)->request(Phake::anyParameters())->thenReturnCallback(function () use ($that) {
            return $that->response;
        });

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
        $this->rsaEncrypted = null;
        $this->signed = null;
        $this->publicKey = null;
        $this->logger = null;
        $this->apiService = null;
        $this->loggingApiService = null;
        $this->wpError = null;
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

    /**
     * Verify that the last item the was RSA encrypted was a valid secret key.  Date times are provided
     * in order to verify the correct "LaunchKey Time" was used for the stamped attribute.
     * @param \DateTime $before
     * @param \DateTime $after
     */
    protected function assertLastItemRsaEncryptedWasValidSecretKey(\DateTime $before, \DateTime $after) {
        $tz = new \DateTimeZone("UTC");
        $before = $before->setTimezone($tz);
        $after = $after->setTimezone($tz);
        $json = $publicKey = null;
        Phake::verify($this->cryptService)->encryptRSA(Phake::capture($json), Phake::capture($publicKey), false);
        $this->assertEquals($this->publicKey, $publicKey, "Unexpected value used for public key in encryptRSA");
        $this->assertJson($json, "Data passed to encryptRSA for secret_key was not valid JSON");
        $data = json_decode($json, true);
        $this->assertArrayHasKey("secret", $data, "Encrypted secret has no secret attribute");
        $this->assertEquals($this->secretKey, $data["secret"], "Unexpected value for secret_key secret");
        $this->assertArrayHasKey("stamped", $data, "Encrypted secret has no stamped attribute");
        $this->assertStringMatchesFormat(
            '%d-%d-%d %d:%d:%d', $data['stamped'], "secret_key stamped attribute has incorrect format"
        );
        $actual = \DateTime::createFromFormat("Y-m-d H:i:s", $data['stamped'], $tz);
        $this->assertGreaterThanOrEqual($before, $actual, "secret_key stamped is before the call occurred");
        $this->assertLessThanOrEqual($after, $actual, "secret_key stamped is before the call occurred");
    }
}

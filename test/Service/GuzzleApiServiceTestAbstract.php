<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use Guzzle\Http\Client;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Mock\MockPlugin;
use LaunchKey\SDK\Guzzle\RequestFactory;
use LaunchKey\SDK\Service\GuzzleApiService;
use LaunchKey\SDK\Test\FixtureTestAbstract;
use Phake;
use PHPUnit_Framework_Constraint_IsEqual;
use PHPUnit_Framework_ExpectationFailedException;

abstract class GuzzleApiServiceTestAbstract extends FixtureTestAbstract
{
    /**
     * @var \Guzzle\Http\ClientInterface
     */
    protected $guzzleClient;

    /**
     * @var MockPlugin
     */
    protected $guzzleMockPlugin;

    /**
     * @Mock
     * @var \LaunchKey\SDK\Service\CryptService
     */
    protected $cryptService;

    /**
     * @Mock
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @Mock
     * @var \LaunchKey\SDK\Cache\Cache
     */
    protected $cache;

    /**
     * @var GuzzleApiService
     */
    protected $apiService;

    /**
     * @var GuzzleApiService
     */
    protected $loggingApiService;

    protected $appKey = "APP KEY";

    protected $secretKey = "SECRET KEY";

    protected $rsaEncrypted = "RSA Encrypted";

    protected $signed = "Signed";

    protected $publicKey = "Public Key";

    protected function setUp()
    {
        Phake::initAnnotations($this);
        Phake::when($this->cryptService)->encryptRSA(Phake::anyParameters())->thenReturn($this->rsaEncrypted);
        Phake::when($this->cryptService)->sign(Phake::anyParameters())->thenReturn($this->signed);
        Phake::when($this->cache)->get(GuzzleApiService::CACHE_KEY_PUBLIC_KEY)->thenReturn($this->publicKey);
        $this->guzzleClient = new Client();
        $this->guzzleClient->setRequestFactory(RequestFactory::getInstance());
        $this->guzzleMockPlugin = new MockPlugin();
        $this->guzzleClient->addSubscriber($this->guzzleMockPlugin);
        $this->apiService = new GuzzleApiService(
            $this->appKey,
            $this->secretKey,
            $this->guzzleClient,
            $this->cryptService,
            $this->cache,
            999999
        );

        $this->loggingApiService = new GuzzleApiService(
            $this->appKey,
            $this->secretKey,
            $this->guzzleClient,
            $this->cryptService,
            $this->cache,
            999999,
            $this->logger
        );
    }

    protected function tearDown()
    {
        $this->guzzleClient = null;
        $this->guzzleMockPlugin = null;
        $this->cryptService = null;
        $this->logger = null;
        $this->apiService = null;
        $this->loggingApiService = null;
    }

    protected function setFixtureResponse($fixture)
    {
        $this->guzzleMockPlugin->clearQueue();
        $this->addFixtureResponse($fixture);
    }

    protected function addFixtureResponse($fixture)
    {
        $this->guzzleMockPlugin->addResponse(Response::fromMessage($this->getFixture($fixture)));
    }

    /**
     * @return EntityEnclosingRequestInterface
     */
    protected function getGuzzleRequest()
    {
        $requests = $this->guzzleMockPlugin->getReceivedRequests();
        return empty($requests) ? null : $requests[0];
    }

    protected function assertGuzzleRequestFormFieldValueEquals($field, $expected)
    {
        $request = $this->getGuzzleRequest();
        if (!$request) {
            throw new \PHPUnit_Framework_ExpectationFailedException("No Guzzle request to evaluate");
        }
        $fields = $request->getPostFields()->toArray();
        if (empty($fields)) {
            throw new \PHPUnit_Framework_ExpectationFailedException("No form data in request");
        }

        $constraint = new \PHPUnit_Framework_Constraint_ArrayHasKey($field);
        $constraint->evaluate($fields, "Form data has no field " . $field);

        $constraint = new \PHPUnit_Framework_Constraint_IsEqual($expected);
        $actual = $fields[$field];
        $constraint->evaluate(
            $actual,
            sprintf("Value for form field \"%s\" of \"%s\" is not equal to \"%s\"", $field, $expected, $actual)
        );
    }

    protected function assertGuzzleRequestHeaderStartsWith($header, $expected) {
        $request = $this->getGuzzleRequest();
        if (!$request) {
            throw new \PHPUnit_Framework_ExpectationFailedException("No Guzzle request to evaluate");
        }
        $headerValue = $request->getHeader($header);
        if (!$headerValue) {
            throw new \PHPUnit_Framework_ExpectationFailedException("Request has no header " . $header);
        }

        $values = $headerValue->toArray();
        $found = false;
        foreach ($values as $value) {
            if (substr($value, 0, strlen($expected)) === $expected) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new \PHPUnit_Framework_ExpectationFailedException(sprintf(
                "None of the header \"%s\" values [\"%s\"] start with \"%s\"",
                $header,
                implode("\", \"", $values),
                $expected
            ));
        }
    }

    protected function assertLastItemEncryptedWasValidSecretKey() {
        $tz = new \DateTimeZone("UTC");
        $before = new \DateTime("now", $tz);
        $after = new \DateTime("now", $tz);
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

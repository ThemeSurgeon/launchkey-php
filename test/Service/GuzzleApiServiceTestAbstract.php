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
     * @var GuzzleAbstractApiService
     */
    protected $apiService;

    /**
     * @var GuzzleAbstractApiService
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
    protected function assertGuzzleRequest()
    {
        $requests = $this->guzzleMockPlugin->getReceivedRequests();
        if (empty($requests)) {
            throw new \PHPUnit_Framework_ExpectationFailedException("No Guzzle request to evaluate");
        }
        return $requests[0];
    }

    protected function assertGuzzleRequestPathEquals($expected)
    {
        $actual = $this->assertGuzzleRequest()->getPath();
        $constraint = new \PHPUnit_Framework_Constraint_IsEqual($expected);
        $constraint->evaluate(
            $actual,
            sprintf("Path \"%s\" did not equal \"%s\"", $actual, $expected)
        );
    }

    protected function assertGuzzleRequestMethodEquals($expected)
    {
        $actual = $this->assertGuzzleRequest()->getMethod();
        $constraint = new \PHPUnit_Framework_Constraint_IsEqual($expected, 0.0, 10, false, true);
        $constraint->evaluate(
            $actual,
            sprintf("Method \"%s\" is not the same as \"%s\"", $actual, $expected)
        );
    }

    protected function assertGuzzleRequestFormFieldValueEquals($field, $expected)
    {
        $fields = $this->assertGuzzleRequest()->getPostFields()->toArray();
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
        $headerValue = $this->assertGuzzleRequest()->getHeader($header);
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

    protected function assertGuzzleRequestQueryStringParameterEquals($parameter, $expected) {
        $actual = $this->assertGuzzleRequest()->getQuery()->get($parameter);
        if (!$actual) {
            throw new \PHPUnit_Framework_ExpectationFailedException("Request has no query parameter " . $parameter);
        }

        $constraint = new \PHPUnit_Framework_Constraint_IsEqual($expected);
        $constraint->evaluate(
            $actual,
            sprintf("Value for query parameter \"%s\" of \"%s\" is not equal to \"%s\"", $parameter, $expected, $actual)
        );
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

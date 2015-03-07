<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Service\CryptService;
use LaunchKey\SDK\Service\PhpSecLibCryptService;

class PhpSecLibCryptServiceTest extends \PHPUnit_Framework_TestCase
{

    const UNENCRYPTED = "This is the expected unencrypted value";

    const BASE64_AES_ENCRYPTED = "Uc7ZMWqCc6TfQU/KTdl1KHEkTIWQWSC+uuSyMU5Kg088E32HLePvHkwwxTdqzhgH";

    const AES_KEY = "myciphermyciphermyciphermycipher";

    const AES_IV = "iviviviviviviviv";

    const BASE64_RSA_ENCRYPTED = <<<'EOT'
Jny/38IhsWDpeFigUC0f+H4sYwlwY/8iGvrvfUNGh7rZCiiSf8oIC7Kx6WUCl/jY9S+OXmYmGKls
YUn2yBYYp+5cYyO6CyKNJkhNFkWjWcbb9Q0u9pxOz8Q/2YhRvHCNZWaXtLxtmQQljoiF4m0sHGSf
CUf45pCCQAU6QInN1w9S51SMRP1weTyC8WROeg8vObeMXc+DzZ4c6WCTILmjgVjB4rnQb/43EUxe
RXvaj9crUPrgaXiu+yvRnhEM40Fw4B26p8t6k6Sb27SIuAOWhmusZkf+JZoWF2yU6JeMfgXbhbjk
9Q6a1Yhav4vBvYouoXRfRwEsiwyZflXfXzgHqA==

EOT;

    const BASE64_RSA_SIGNATURE = "BKOVrXZJVOobOQHpmgPnUpggaYtlZBuKsNv300MTg1fykvD7K6/HKlv27aJUOrtyPzVur+Jad5nz6JHhSrUy5dCVOyeGRnQ4nhrlvkhOcBn4/ctz2l6ZGK6bzOOR7gmUl/3ZnAtHqaTWNlFIlhOe+JEtaMEEvc2fB5rh87ibDGUI9ZtYENoEDkaN7UUq121qZWVCg7Nj3z0+yLhEjirNYgs8tI5CzNIySX85qRLI83EJrelMNWskqKvy/lhr5GasQMZUTEPbtjXz7AunqZRVAkRw/LAoQu2JZXnJiJYhtRw/bZmU94Ah6GAW3bNmvEAZns2fs+A3KdfY52DpwEWwuw==";


    const PRIVATE_KEY = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIEogIBAAKCAQEAq2izh7NEarDdrdZLrplizezZG/JzW14XQ74IXkjEqkvxhZ1s
6joGtvoxX+P0QRyWrhgtiUnN3DSRAa0QLsZ0ZKk5a2AMGqu0/6eoJGwXSHcLreLE
fqdd8+zlvDrbWISekTLoecLttwKSIcP2bcq1nAKz+ZNKMPvB/lm/dHXOqlnybo0J
7efUkbd81fHrMOZNLKRYXx3Zx8zsQFf2ee/ypnnw5lKwX+9IBAT/679eGUlh8HfT
SG6JQaNezyRG1cOd+pO6hKxff6Q2GVXqHsrIac4AlR80AEaBeiuFYxjHpruS6BRc
yW8UvqX0l9rKMDAWNAtMWt2egYAe6XOEXIWOiQIDAQABAoIBADUmDOzZ0DAI0WPS
m7rywqk5dIRu5AgDn9EYfn3FsH1heO1GR/xEq8pWv7KM+zKpS6uFwbDdGqDaB9Bu
OiNW08ZWloBN0tL+ROw0rzVD8uA8UXnEY8sl2EMHRKDd2x+SV5yMHXuLzqu9d1RS
7/lRLojGacnMOuf/WEKmz2+sC73UDfYm7Kq39LStE0Hi9iAq8eF+9U8b3l7Pikx/
t70wOfCQJCrlfAFn0MdoxXoybr4HCy7tA2pqWPG2yhGnROaJSA430UNJQ9sU9p5M
qyU8VWz8I2lFZkpflgf34D9sxt2BaRQvR0T0GBILHf0BfwDjlF+fdgZjQb0uTdez
mcIhiNECgYEAxju+IzfDHis3GSu/6GALoDnxLpOi3y8QjBBa8nEd4XpRGAyaHgbt
/Q03Sd9jfST0jP7hKyJPWiPR5l4M9BpCEuQlhxdpSdy0acvXhuwdAWawaOHkMcUV
iBZfzOB0VY2L55RVpaAqO1rq0EOydsD3n9uX/eEjWiaEEZNhdzrcgkUCgYEA3Vva
cW4wguSB7VWJDJCd+o69AS29tBQBqYtCXRokmzWU6hitNa36wJMI2/fTW2lxegAi
8RJ8HRAj8D3GpwbdIm5tgH+2EBoGqraxwXfyt4NKiVvRFEyg0zLq31U9VDm11BlG
KU6XdxzD5aC+/txML+ib85WQsVInKVdP5pXowXUCgYB2scT6f2QER2n5V1nUQNYV
PTxtYBcQvbSRuSVLr3Ft1fiChuEtA4cyktw9DlYa06reVarrUeLjnTkMT9o/uw0/
FH5n8huoD0+zXUuSzQPdF+ifFEq3hkOLNaJtISRnKZbQtd/GiS1gVuLsiuxr8MUU
Yb8TU+AAFbnUcEPWyVbJZQKBgBPtjQDhNqTSBZBkPu5OpqpD52gPwiBQHMYyr0rK
a7k9XaalihJnE0f69LU43mJAX+Ln2D1zuJC1P0cFiLjIuWe8IUeMN8vDTA5aXC5a
qhMzUqaDCZOWQnRBBTwN5HOMrn3luJdHaANlJ42opwkys/ksK74GHPyZtMTYA21y
2X1xAoGAW3Yu0n/VcvDcQZmE++iPDKLD/Mpc18G1sRLNwrdhVEgRVk8sfYiQxmOb
NNHiXe4njK7waEKHPo86poV22FAum0zBMFSf9igfCk5kuL/pk4EVa58NftF69S8V
Ud+Zy3E0RJXToW0t3Eo5UexVieglvpgxG7x1SCdvxYtTl6CZ520=
-----END RSA PRIVATE KEY-----
EOT;

    const PUBLIC_KEY = <<<'EOT'
-----BEGIN RSA PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAq2izh7NEarDdrdZLrpli
zezZG/JzW14XQ74IXkjEqkvxhZ1s6joGtvoxX+P0QRyWrhgtiUnN3DSRAa0QLsZ0
ZKk5a2AMGqu0/6eoJGwXSHcLreLEfqdd8+zlvDrbWISekTLoecLttwKSIcP2bcq1
nAKz+ZNKMPvB/lm/dHXOqlnybo0J7efUkbd81fHrMOZNLKRYXx3Zx8zsQFf2ee/y
pnnw5lKwX+9IBAT/679eGUlh8HfTSG6JQaNezyRG1cOd+pO6hKxff6Q2GVXqHsrI
ac4AlR80AEaBeiuFYxjHpruS6BRcyW8UvqX0l9rKMDAWNAtMWt2egYAe6XOEXIWO
iQIDAQAB
-----END RSA PUBLIC KEY-----
EOT;

    /**
     * @var CryptService
     */
    private $service;

    protected function setUp()
    {
        $this->service = new PhpSecLibCryptService(static::PRIVATE_KEY);
    }

    protected function tearDown()
    {
        $this->service = null;
    }

    public function testEncryptRSABase64EncodedDecrypts()
    {
        $actual = $this->service->decryptRSA(
            $this->service->encryptRSA(static::UNENCRYPTED, static::PUBLIC_KEY, true),
            true
        );
        $this->assertEquals(static::UNENCRYPTED, $actual);
    }

    public function testEncryptRSAPlainTextDecrypts()
    {
        $actual = $this->service->decryptRSA(
            $this->service->encryptRSA(static::UNENCRYPTED, static::PUBLIC_KEY, false),
            false
        );
        $this->assertEquals(static::UNENCRYPTED, $actual);
    }

    public function testDecryptRSABase64Encoded()
    {
        $actual = $this->service->decryptRSA(static::BASE64_RSA_ENCRYPTED, true);
        $this->assertEquals(static::UNENCRYPTED, $actual);
    }

    public function testDecryptRSAPlainText()
    {
        $actual = $this->service->decryptRSA(base64_decode(static::BASE64_RSA_ENCRYPTED), false);
        $this->assertEquals(static::UNENCRYPTED, $actual);
    }

    public function testDecryptAESBase64Encoded()
    {
        $actual = $this->service->decryptAES(static::BASE64_AES_ENCRYPTED, static::AES_KEY, static::AES_IV, true);
        $this->assertEquals(static::UNENCRYPTED, $actual);
    }

    public function testDecryptAESPlainText()
    {
        $actual = $this->service->decryptAES(
            base64_decode(static::BASE64_AES_ENCRYPTED),
            static::AES_KEY,
            static::AES_IV,
            false
        );
        $this->assertEquals(static::UNENCRYPTED, $actual);
    }

    public function testSignBase64EncodedSignatureIsVerifiable()
    {
        $this->assertTrue($this->service->verifySignature(
            $this->service->sign("Data", true),
            "Data",
            static::PUBLIC_KEY,
            true
        ));
    }

    public function testSignPlainTextSignatureIsVerifiable()
    {
        $this->assertTrue($this->service->verifySignature(
            $this->service->sign("Data", false),
            "Data",
            static::PUBLIC_KEY,
            false
        ));
    }

    public function testVerifySignatureReturnsTrueWhenBase64EncodedSignatureIsValid()
    {
        $actual = $this->service->verifySignature(
            static::BASE64_RSA_SIGNATURE,
            base64_decode(static::BASE64_RSA_ENCRYPTED),
            static::PUBLIC_KEY,
            true);
        $this->assertTrue($actual);
    }

    public function testVerifySignatureReturnsFalseWhenBase64EncodedSignatureIsNotValid()
    {
        $actual = $this->service->verifySignature(
            static::BASE64_RSA_ENCRYPTED,
            base64_decode(static::BASE64_RSA_ENCRYPTED),
            static::PUBLIC_KEY,
            true);
        $this->assertFalse($actual);
    }

    public function testVerifySignatureReturnsTrueWhenPlainTextSignatureIsValid()
    {
        $actual = $this->service->verifySignature(
            base64_decode(static::BASE64_RSA_SIGNATURE),
            base64_decode(static::BASE64_RSA_ENCRYPTED),
            static::PUBLIC_KEY,
            false);
        $this->assertTrue($actual);
    }

    public function testVerifySignatureReturnsFalseWhenPlainTextSignatureIsNotValid()
    {
        $actual = $this->service->verifySignature(
            base64_decode(static::BASE64_RSA_ENCRYPTED),
            base64_decode(static::BASE64_RSA_ENCRYPTED),
            static::PUBLIC_KEY,
            false);
        $this->assertFalse($actual);
    }
}

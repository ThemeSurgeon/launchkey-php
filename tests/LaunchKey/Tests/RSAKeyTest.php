<?php

namespace LaunchKey\Tests;

use LaunchKey\RSAKey;
use Mockery;

/**
 * Tests RSAKey in the most brittle fashion possible.
 *
 * @package  LaunchKey
 * @category Tests
 */
class RSAKeyTest extends \PHPUnit_Framework_TestCase
{

    public function test_private_decrypt()
    {
        $data = sha1(mt_rand());
        $encoded = base64_encode($data);
        $decrypted = sha1(mt_rand());

        $mock = Mockery::mock('Crypt_RSA');
        $mock->shouldReceive('decrypt')
            ->with($data)
            ->andReturn($decrypted);

        $keypair = new RSAKey(NULL);
        $keypair->private_key($mock);

        $this->assertEquals($decrypted, $keypair->private_decrypt($encoded));
    }

    public function test_public_encrypt()
    {
        $data = sha1(mt_rand());
        $encrypted = sha1(mt_rand());
        $encoded = base64_encode($encrypted);

        $mock = Mockery::mock('Crypt_RSA');
        $mock->shouldReceive('encrypt')
            ->with($data)
            ->andReturn($encrypted);

        $keypair = new RSAKey(NULL);
        $keypair->public_key($mock);

        $this->assertEquals($encoded, $keypair->public_encrypt($data));
    }

    public function test_sign()
    {
        $data = sha1(mt_rand());
        $encoded_data = base64_encode($data);
        $signature = sha1(mt_rand());
        $encoded_signature = base64_encode($signature);

        $mock = Mockery::mock('Crypt_RSA');
        $mock->shouldReceive('sign')
            ->with($data)
            ->andReturn($signature);

        $keypair = new RSAKey(NULL);
        $keypair->signer($mock);

        $this->assertEquals($encoded_signature, $keypair->sign($encoded_data));
    }

    public function test_verify()
    {
        $data = sha1(mt_rand());
        $encoded_data = base64_encode($data);
        $signature = sha1(mt_rand());
        $encoded_signature = base64_encode($signature);
        $expected = (boolean)mt_rand(0, 1);

        $mock = Mockery::mock('Crypt_RSA');
        $mock->shouldReceive('verify')
            ->with($data, $signature)
            ->andReturn($expected);

        $keypair = new RSAKey(NULL);
        $keypair->verifier($mock);

        $this->assertEquals($expected, $keypair->verify($encoded_signature, $encoded_data));
    }

} // End RSAKeyTest

<?php

namespace LaunchKey;

/**
 * Wrapper around phpseclib's `Crypt_RSA` to provide easier access to the
 * cryptography methods used by LaunchKey.
 */
class RSAKey {

    const PRIVATE_PATTERN = '/(-{5}BEGIN RSA PRIVATE KEY-{5}.+-{5}END RSA PRIVATE KEY-{5})/s';
    const PUBLIC_PATTERN  = '/(-{5}BEGIN PUBLIC KEY-{5}.+-{5}END PUBLIC KEY-{5})/s';

    public static $crypt_class = 'Crypt_RSA';

    public static function generate($bits = 2048)
    {
        $crypt = new self::$crypt_class;

        if ( ! defined('CRYPT_RSA_EXPONENT'))
        {
            define('CRYPT_RSA_EXPONENT', 65537);
        }

        $generated = $crypt->createKey($bits);

        return new self($generated['privatekey']."\n".$generated['publickey']);
    }

    private $public_key;

    private $private_key;

    private $signer;

    private $verifier;

    public function __construct($key, array $options = array())
    {
        $key = (string) $key;

        $this->load_public_key($key);
        $this->load_private_key($key, $options);
    }

    public function public_key($value = NULL)
    {
        if ($value === NULL)
        {
            return $this->public_key;
        }

        $this->public_key = $value;
        return $this;
    }

    public function private_key($value = NULL)
    {
        if ($value === NULL)
        {
            return $this->private_key;
        }

        $this->private_key = $value;
        return $this;
    }

    public function signer($value = NULL)
    {
        if ($value === NULL)
        {
            return $this->signer;
        }

        $this->signer = $value;
        return $this;
    }

    public function verifier($value = NULl)
    {
        if ($value === NULL)
        {
            return $this->verifier;
        }

        $this->verifier = $value;
        return $this;
    }

    public function public_encrypt($data)
    {
        return base64_encode($this->public_key->encrypt($data));
    }

    public function private_decrypt($data)
    {
        return $this->private_key->decrypt(base64_decode($data));
    }

    public function sign($data)
    {
        return base64_encode($this->signer->sign(base64_decode($data)));
    }

    public function verify($signature, $data)
    {
        return $this->verifier->verify(base64_decode($data), base64_decode($signature));
    }

    private function load_public_key($key)
    {
        $public  = $key;
        $matches = array();

        if (preg_match(self::PUBLIC_PATTERN, $key, $matches))
        {
            $public = $matches[1];
        }

        $this->public_key = new self::$crypt_class;
        $this->public_key->loadKey($public);

        $this->verifier = new self::$crypt_class;
        $this->verifier->setHash('sha256');
        $this->verifier->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $this->verifier->loadKey($public);
    }

    private function load_private_key($key, array $options = array())
    {
        $private = $key;
        $matches = array();

        if (preg_match(self::PRIVATE_PATTERN, $key, $matches))
        {
            $private = $matches[1];
        }

        $this->private_key = new self::$crypt_class;
        $this->private_key->loadKey($private);

        $this->signer = new self::$crypt_class;
        $this->signer->setHash('sha256');
        $this->signer->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $this->signer->loadKey($private);

        if (isset($options['passphrase']) AND ! empty($options['passphrase']))
        {
            $this->private_key->setPassword($options['passphrase']);
        }
    }

} // End RSAKey

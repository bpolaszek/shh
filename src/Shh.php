<?php

namespace BenTools\Shh;

final class Shh
{
    private const DEFAULT_OPENSSL_GENERATION_CONFIGURATION = [
        'digest_alg'       => 'sha512',
        'private_key_bits' => 4096,
        'private_key_type' => \OPENSSL_KEYTYPE_RSA,
    ];

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string|null
     */
    private $privateKey;

    /**
     * @var string|null
     */
    private $passphrase;

    /**
     * @var resource
     */
    private $resource;

    /**
     * Shh constructor.
     */
    public function __construct(string $publicKey, ?string $privateKey = null, ?string $passphrase = null)
    {
        $this->publicKey = self::normalize($publicKey);
        $this->privateKey = null === $privateKey ? null : self::normalize($privateKey);
        $this->passphrase = $passphrase;
    }

    /**
     * @return resource
     */
    private function getPublicKeyAsResource()
    {
        if (null === $this->resource) {
            $this->resource = \openssl_pkey_get_public($this->publicKey)
                or ShhException::throwFromLastOpenSSLError('Unable to open resource.');
        }

        return $this->resource;
    }

    private function freeResource(): void
    {
        if (null === $this->resource) {
            return;
        }

        \openssl_free_key($this->resource);
        $this->resource = null;
    }

    /**
     * @param string $payload
     * @return string
     */
    public function encrypt(string $payload): string
    {
        $resource = $this->getPublicKeyAsResource();
        $success = \openssl_public_encrypt($payload, $encryptedData, $resource, \OPENSSL_PKCS1_OAEP_PADDING);
        $this->freeResource();

        if (!$success) {
            throw new ShhException("Encryption failed. Ensure you are using a PUBLIC key.");
        }

        return \base64_encode($encryptedData);
    }

    /**
     * @param string $base64EncodedPayload
     * @return string
     */
    public function decrypt(string $base64EncodedPayload): string
    {
        if (null === $this->privateKey) {
            throw new ShhException('Unable to decrypt payload: no private key provided.');
        }

        $payload = \base64_decode($base64EncodedPayload);

        if (false === $payload) {
            throw new ShhException('Encrypted payload was not provided as Base64.');
        }

        $resource = \openssl_pkey_get_private($this->privateKey, $this->passphrase)
            or ShhException::throwFromLastOpenSSLError('Private key seems corrupted.');

        $success = \openssl_private_decrypt($payload, $decryptedData, $resource, \OPENSSL_PKCS1_OAEP_PADDING);
        \openssl_free_key($resource);

        if (!$success) {
            throw new ShhException("Decryption failed. Ensure you are using (1) A PRIVATE key, and (2) the correct one.");
        }

        return $decryptedData;
    }

    /**
     * Generate a new private/public key pair.
     *
     * @param string|null $passphrase
     * @param array       $config
     * @return array - [publicKey, privateKey]
     */
    public static function generateKeyPair(?string $passphrase = null, array $config = []): array
    {
        $config += self::DEFAULT_OPENSSL_GENERATION_CONFIGURATION;
        $resource = \openssl_pkey_new($config)
            or ShhException::throwFromLastOpenSSLError('Unable to open resource.');

        $success = \openssl_pkey_export($resource, $privateKey, $passphrase);

        if (false === $success) {
            ShhException::throwFromLastOpenSSLError('Private key generation failed.');
        }

        $publicKey = \openssl_pkey_get_details($resource)['key'];

        return [$publicKey, $privateKey];
    }

    /**
     * Change passphrase and return a new private key.
     *
     * @param string      $privateKey
     * @param string|null $oldPassphrase
     * @param string|null $newPassphrase
     * @return string
     */
    public static function changePassphrase(string $privateKey, ?string $oldPassphrase, ?string $newPassphrase): string
    {
        $resource = \openssl_pkey_get_private(self::normalize($privateKey), $oldPassphrase);
        $success = @\openssl_pkey_export($resource, $newPrivateKey, $newPassphrase);

        if (false === $success) {
            throw new ShhException('Wrong passphrase or inexistent private key.');
        }

        return $newPrivateKey;
    }

    /**
     * @param string $key
     * @return string
     */
    private static function normalize(string $key): string
    {
        return (0 === \strpos($key, '/')) ? 'file://'.$key : $key;
    }
}

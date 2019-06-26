<?php

namespace BenTools\Shh;

use Pikirasa\RSA;

final class Shh
{
    /**
     * @var RSA
     */
    private $rsa;

    /**
     * Shh constructor.
     */
    public function __construct(RSA $rsa)
    {
        $this->rsa = $rsa;
    }

    /**
     * @param string $payload
     * @return string
     * @throws \Pikirasa\Exception
     */
    public function encrypt(string $payload): string
    {
        return \base64_encode($this->rsa->encrypt($payload));
    }

    /**
     * @param string $payload
     * @return string
     * @hrows \Pikirasa\Exception
     */
    public function decrypt(string $payload): string
    {
        return $this->rsa->decrypt(\base64_decode($payload));
    }
}

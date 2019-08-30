<?php

namespace BenTools\Shh;

final class ShhException extends \RuntimeException
{
    /**
     * @param string $defaultMessage
     */
    public static function throwFromLastOpenSSLError(string $defaultMessage = 'Unexpected OpenSSL Exception')
    {
        throw new self(\openssl_error_string() ?: $defaultMessage);
    }
}

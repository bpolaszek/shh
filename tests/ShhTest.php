<?php

namespace BenTools\Shh\Tests;

use BenTools\Shh\Shh;
use BenTools\Shh\ShhException;
use PHPUnit\Framework\TestCase;
use function BenTools\CartesianProduct\cartesian_product;

class ShhTest extends TestCase
{
    /**
     * @test
     * @dataProvider matrix
     */
    public function it_actually_works(?string $passphrase, array $keypair, bool $decryptionEnabled, string $format, array $keys)
    {
        [$publicKey, $privateKey] = $keys;

        if (false === $decryptionEnabled) {
            $privateKey = null;
        }

        $shh = new Shh($publicKey, $privateKey, $passphrase);
        $encrypted = $shh->encrypt('foo');
        $this->assertInternalType('string', $encrypted);

        if ($decryptionEnabled) {
            $this->assertEquals('foo', $shh->decrypt($encrypted));
        } else {
            $this->expectException(ShhException::class);
            $shh->decrypt($encrypted);
        }
    }

    /**
     * @test
     */
    public function it_can_encrypt_several_secrets()
    {
        $shh = new Shh(...Shh::generateKeyPair());
        $secrets = [
            'foo',
            'bar',
        ];

        $this->assertEquals($secrets, \array_map(function(string $secret) use ($shh) {
            return $shh->decrypt($shh->encrypt($secret));
        }, $secrets));
    }

    /**
     * @test
     * @dataProvider matrix
     */
    public function wrong_passphrase_throws_exception_on_decryption(?string $passphrase, array $keypair, bool $decryptionEnabled, string $format, array $keys)
    {
        [$publicKey, $privateKey] = $keys;


        if (null === $passphrase || false === $decryptionEnabled) {
            $this->markTestSkipped();
            return;
        }

        $passphrase = 'foobar';

        $shh = new Shh($publicKey, $privateKey, $passphrase);
        $encrypted = $shh->encrypt('foo');
        $this->expectException(ShhException::class);
        $shh->decrypt($encrypted);
    }

    public function matrix(): iterable
    {
        $matrix = [
            'passphrase' => [
                null,
                's3cr3tc0de',
            ],
            'keypair' => [
                static function (array $combination) {
                    return Shh::generateKeyPair($combination['passphrase']);
                }
            ],
            'decryption_enabled' => [
                true,
                false,
            ],
            'format' => [
                'string',
                'file',
            ],
            'keys' => [
                static function (array $combination) {
                    if ('string' === $combination['format']) {
                        return $combination['keypair'];
                    }

                    // file
                    [$publicKey, $privateKey] = $combination['keypair'];
                    $publicKeyFile = \tempnam(\sys_get_temp_dir(), 'shh_');
                    $privateKeyFile = \tempnam(\sys_get_temp_dir(), 'shh_');

                    \Safe\file_put_contents($publicKeyFile, $publicKey);
                    \Safe\file_put_contents($privateKeyFile, $privateKey);

                    return [$publicKeyFile, $privateKeyFile];
                }
            ]
        ];

        yield from cartesian_product($matrix);
    }

    /**
     * @test
     */
    public function it_generates_a_valid_keypair()
    {
        // Without passphrase
        [$publicKey, $privateKey] = Shh::generateKeyPair();

        $shh = new Shh($publicKey, $privateKey);
        $encrypted = $shh->encrypt('foo');
        $decrypted = $shh->decrypt($encrypted);
        $this->assertEquals('foo', $decrypted);

        // With passphrase
        [$publicKey, $privateKey] = Shh::generateKeyPair('foobar');

        $shh = new Shh($publicKey, $privateKey, 'foobar');
        $encrypted = $shh->encrypt('foo');
        $decrypted = $shh->decrypt($encrypted);
        $this->assertEquals('foo', $decrypted);
    }

    /**
     * @test
     */
    public function i_can_change_my_passphrase()
    {
        [$publicKey, $privateKey] = Shh::generateKeyPair();
        $shh = new Shh($publicKey, $privateKey);
        $encrypted = $shh->encrypt('foo');
        $decrypted = $shh->decrypt($encrypted);
        $this->assertEquals('foo', $decrypted);

        $privateKey = Shh::changePassphrase($privateKey, null, 'foobar');

        $shh = new Shh($publicKey, $privateKey, 'foobar');
        $decrypted = $shh->decrypt($encrypted);
        $this->assertEquals('foo', $decrypted);

        $shh = new Shh($publicKey, $privateKey);
        $this->expectException(ShhException::class);
        $shh->decrypt($encrypted);
    }
}

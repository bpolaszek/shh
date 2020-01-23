<?php

namespace BenTools\Shh\Tests\SecretStorage;

use BenTools\Shh\SecretStorage\JsonFileSecretStorage;
use BenTools\Shh\Shh;
use PHPUnit\Framework\TestCase;

class JsonFileSecretStorageTest extends TestCase
{
    private static $shh;
    private static $storage;

    public static function setUpBeforeClass(): void
    {
        if (null === self::$storage) {
            $file = \tempnam(\sys_get_temp_dir(), 'shh_tests');
            [$publicKey, $privateKey] = Shh::generateKeyPair();
            self::$shh = new Shh($publicKey, $privateKey);
            self::$storage = new JsonFileSecretStorage(self::$shh, $file);
        }
    }

    /**
     * @test
     */
    public function it_stores_an_unencrypted_secret()
    {
        self::$storage->store('unencrypted_secret', 'foo');
        $this->assertTrue(self::$storage->has('unencrypted_secret'));
        $this->assertNotEquals('foo', self::$storage->get('unencrypted_secret', false));
        $this->assertEquals('foo', self::$storage->get('unencrypted_secret'));
    }

    /**
     * @test
     */
    public function it_stores_an_encrypted_secret()
    {
        $this->assertTrue(self::$storage->has('unencrypted_secret'));
        self::$storage->store('encrypted_secret', self::$shh->encrypt('bar'), false);
        $this->assertNotEquals('bar', self::$storage->get('encrypted_secret', false));
        $this->assertEquals('bar', self::$storage->get('encrypted_secret'));
    }

    /**
     * @test
     */
    public function it_lists_secrets_keys()
    {
        $this->assertEquals(['unencrypted_secret', 'encrypted_secret'], self::$storage->getKeys());
    }

}

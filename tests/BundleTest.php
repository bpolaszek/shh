<?php

namespace BenTools\Shh\Tests;

use BenTools\Shh\Command\CheckCommand;
use BenTools\Shh\Command\DecryptCommand;
use BenTools\Shh\Command\EncryptCommand;
use BenTools\Shh\Command\RegisterSecretCommand;
use BenTools\Shh\Shh;
use BenTools\Shh\ShhEnvVarProcessor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BundleTest extends KernelTestCase
{
    protected function setUp()
    {
        static::bootKernel();
    }

    /**
     * @test
     */
    public function bundle_is_configured()
    {
        $this->assertTrue(self::$kernel->getContainer()->has(CheckCommand::class));
        $this->assertTrue(self::$kernel->getContainer()->has(DecryptCommand::class));
        $this->assertTrue(self::$kernel->getContainer()->has(EncryptCommand::class));
        $this->assertTrue(self::$kernel->getContainer()->has(RegisterSecretCommand::class));
        $this->assertTrue(self::$kernel->getContainer()->has(Shh::class));
        $this->assertTrue(self::$kernel->getContainer()->has(ShhEnvVarProcessor::class));
        $this->assertEquals(__DIR__.'/.keys/private.pem', self::$kernel->getContainer()->getParameter('shh.private_key_file'));
        $this->assertEquals(__DIR__.'/.keys/public.pem', self::$kernel->getContainer()->getParameter('shh.public_key_file'));
        $this->assertEquals('USNuclearCodeIs0000', self::$kernel->getContainer()->getParameter('shh.passphrase'));
        $this->assertEquals('this_is_very_secret', self::$kernel->getContainer()->getParameter('some_encrypted_secret'));
    }
}

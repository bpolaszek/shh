<?php

namespace BenTools\Shh\Tests\Command;

use BenTools\Shh\Command\RegisterSecretCommand;
use BenTools\Shh\Shh;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class RegisterSecretCommandTest extends TestCase
{

    /**
     * @test
     */
    public function it_registers_a_secret()
    {
        $secretsFile = \tempnam(\sys_get_temp_dir(), 'shh_');
        [$publicKey, $privateKey] = Shh::generateKeyPair();
        $shh = new Shh($publicKey, $privateKey);
        $application = new Application();
        $application->add(new RegisterSecretCommand($shh, new Filesystem(), $secretsFile));
        $command = $application->find('shh:register:secret');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs([
            'key' => 'foo',
            'value' => 'bar'
        ]);
        $commandTester->execute([]);
        $this->assertEquals(0, $commandTester->getStatusCode());

        $content = \Safe\file_get_contents($secretsFile);
        $secrets = \Safe\json_decode($content, true);

        $this->assertArrayHasKey('foo', $secrets);
        $this->assertEquals('bar', $shh->decrypt($secrets['foo']));
    }

    /**
     * @test
     */
    public function it_registers_an_already_encrypted_secret()
    {
        $secretsFile = \tempnam(\sys_get_temp_dir(), 'shh_');
        [$publicKey, $privateKey] = Shh::generateKeyPair();
        $shh = new Shh($publicKey, $privateKey);
        $application = new Application();
        $application->add(new RegisterSecretCommand($shh, new Filesystem(), $secretsFile));
        $command = $application->find('shh:register:secret');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'key' => 'foo',
            'value' => $shh->encrypt('bar'),
            '--no-encrypt' => true
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode());

        $content = \Safe\file_get_contents($secretsFile);
        $secrets = \Safe\json_decode($content, true);

        $this->assertArrayHasKey('foo', $secrets);
        $this->assertEquals('bar', $shh->decrypt($secrets['foo']));
    }
}

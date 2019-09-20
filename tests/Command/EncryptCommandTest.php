<?php

namespace BenTools\Shh\Tests\Command;

use BenTools\Shh\Command\EncryptCommand;
use BenTools\Shh\Shh;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class EncryptCommandTest extends TestCase
{

    /**
     * @test
     */
    public function it_encrypts_a_secret()
    {
        [$publicKey, $privateKey] = Shh::generateKeyPair();
        $shh = new Shh($publicKey);
        $application = new Application();
        $application->add(new EncryptCommand($shh));
        $command = $application->find('shh:encrypt');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['payload' => 'foo']);
        $this->assertEquals(0, $commandTester->getStatusCode());
        $encrypted = trim($commandTester->getDisplay(false));

        $shh = new Shh($publicKey, $privateKey);
        $this->assertEquals('foo', $shh->decrypt($encrypted));
    }
}

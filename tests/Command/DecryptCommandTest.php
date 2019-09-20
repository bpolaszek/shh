<?php

namespace BenTools\Shh\Tests\Command;

use BenTools\Shh\Command\DecryptCommand;
use BenTools\Shh\Shh;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DecryptCommandTest extends TestCase
{

    /**
     * @test
     */
    public function it_decrypts_a_secret()
    {
        [$publicKey, $privateKey] = Shh::generateKeyPair();
        $shh = new Shh($publicKey, $privateKey);
        $encrypted = $shh->encrypt('foo');

        $application = new Application();
        $application->add(new DecryptCommand($shh));
        $command = $application->find('shh:decrypt');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['payload' => $encrypted]);
        $this->assertEquals(0, $commandTester->getStatusCode());
        $decrypted = trim($commandTester->getDisplay(false));
        $this->assertEquals('foo', $decrypted);
    }
}

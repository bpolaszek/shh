<?php

namespace BenTools\Shh\Tests\Command;

use BenTools\Shh\Command\GenerateKeyPairCommand;
use BenTools\Shh\Command\RegisterSecretCommand;
use BenTools\Shh\Shh;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class GenerateKeyPairCommandTest extends TestCase
{

    /**
     * @test
     */
    public function it_generates_keys()
    {
        $keysDir = \sys_get_temp_dir() . '/' . \hash('crc32', \microtime(true));
        \Safe\mkdir($keysDir, 0777, true);
        $publicKey = $keysDir . '/' . 'public.pem';
        $privateKey = $keysDir . '/' . 'private.pem';

        $fs = new Filesystem();
        $this->assertFalse($fs->exists($publicKey));
        $this->assertFalse($fs->exists($privateKey));

        $application = new Application();
        $application->add(new GenerateKeyPairCommand($keysDir, $fs));
        $command = $application->find('shh:generate:keys');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs([
            'passphrase' => 's3cr3tc0de',
            'passphrase-again' => 's3cr3tc0de',
        ]);
        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertTrue($fs->exists($publicKey));
        $this->assertTrue($fs->exists($privateKey));

        $shh = new Shh($publicKey, $privateKey, 's3cr3tc0de');
        $this->assertEquals('foo', $shh->decrypt($shh->encrypt('foo')));
    }
}

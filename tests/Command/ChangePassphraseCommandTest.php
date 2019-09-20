<?php

namespace BenTools\Shh\Tests\Command;

use BenTools\Shh\Command\ChangePassphraseCommand;
use BenTools\Shh\Command\GenerateKeyPairCommand;
use BenTools\Shh\Shh;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ChangePassphraseCommandTest extends TestCase
{

    /**
     * @test
     */
    public function it_changes_passphrase()
    {
        [$publicKey, $privateKey] = Shh::generateKeyPair('foobar');
        $keysDir = \sys_get_temp_dir() . '/' . \hash('crc32', \microtime(true));
        \Safe\mkdir($keysDir, 0777, true);
        $publicKeyFile = $keysDir . '/' . 'public.pem';
        $privateKeyFile = $keysDir . '/' . 'private.pem';

        \Safe\file_put_contents($publicKeyFile, $publicKey);
        \Safe\file_put_contents($privateKeyFile, $privateKey);

        $shh = new Shh($publicKeyFile, $privateKeyFile, 'foobar');
        $this->assertEquals('foo', $shh->decrypt($shh->encrypt('foo')));

        $application = new Application();
        $application->add(new ChangePassphraseCommand($shh, new Filesystem(), $keysDir, $privateKeyFile));
        $command = $application->find('shh:change:passphrase');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs([
            'old-passphrase' => 'foobar',
            'new-passphrase' => 's3cr3tc0de',
            'new-passphrase-again' => 's3cr3tc0de',
            'overwrite' => 'yes',
        ]);
        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $shh = new Shh($publicKeyFile, $privateKeyFile, 's3cr3tc0de');
        $this->assertEquals('foo', $shh->decrypt($shh->encrypt('foo')));
    }
}

<?php

namespace BenTools\Shh\Tests\Command;

use BenTools\Shh\Command\CheckCommand;
use BenTools\Shh\Shh;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use function BenTools\CartesianProduct\cartesian_product;

class CheckCommandTest extends TestCase
{

    /**
     * @test
     * @dataProvider matrix
     */
    public function it_works(?string $passphrase, array $keys, bool $validPublicKey, bool $validPrivateKey)
    {
        [$publicKeyFile, $privateKeyFile] = $keys;

        if (false === $validPublicKey) {
            $publicKeyFile = '';
        }

        if (false === $validPrivateKey) {
            $privateKeyFile = '';
        }

        $application = new Application();
        $application->add(new CheckCommand($publicKeyFile, $privateKeyFile, $passphrase));
        $command = $application->find('shh:check');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $expectedStatusCode = \in_array(false, [$validPublicKey, $validPrivateKey], true) ? 1 : 0;

        $this->assertEquals($expectedStatusCode, $commandTester->getStatusCode());
    }

    public function matrix()
    {
        $matrix = [
            'passphrase' => [
                null,
                's3cr3tc0de',
            ],
            'keys' => [
                static function (array $combination) {
                    [$publicKey, $privateKey] = Shh::generateKeyPair($combination['passphrase']);

                    $publicKeyFile = \tempnam(\sys_get_temp_dir(), 'shh_');
                    $privateKeyFile = \tempnam(\sys_get_temp_dir(), 'shh_');

                    \Safe\file_put_contents($publicKeyFile, $publicKey);
                    \Safe\file_put_contents($privateKeyFile, $privateKey);

                    return [$publicKeyFile, $privateKeyFile];
                }
            ],
            'valid_public_key' => [
                true,
                false,
            ],
            'valid_private_key' => [
                true,
                false,
            ],
        ];

        yield from cartesian_product($matrix);
    }
}

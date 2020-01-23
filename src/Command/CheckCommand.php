<?php

namespace BenTools\Shh\Command;

use BenTools\Shh\Shh;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CheckCommand extends Command
{
    protected static $defaultName = 'shh:check';

    /**
     * @var string
     */
    private $publicKeyFile;

    /**
     * @var string
     */
    private $privateKeyFile;

    /**
     * @var string|null
     */
    private $passphrase;

    /**
     * CheckCommand constructor.
     */
    public function __construct(string $publicKeyFile, ?string $privateKeyFile, ?string $passphrase = null)
    {
        $this->publicKeyFile = $publicKeyFile;
        $this->privateKeyFile = $privateKeyFile;
        $this->passphrase = $passphrase;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Checks the app is ready for decrypting and / or encrypting payloads.')
            ->addOption('decrypt-only', null, InputOption::VALUE_NONE, 'Only checks decryption.')
            ->addOption('encrypt-only', null, InputOption::VALUE_NONE, 'Only checks encryption.')
            ->addOption('payload', null, InputOption::VALUE_OPTIONAL, '(Optionnal) Check this payload for decryption.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $shh = new Shh($this->publicKeyFile, $this->privateKeyFile, $this->passphrase);

        $errors = [];

        // Public key is required for both encrypting and decrypting.
        if (!\is_readable($this->publicKeyFile)) {
            $errors[] = \sprintf('The public key file is not readable: %s', $this->publicKeyFile);
            $io->error(\end($errors));
        }

        // Private key is not required for encrypting.
        if (false === $input->getOption('encrypt-only')) {
            if (!\is_readable($this->privateKeyFile)) {
                $errors[] = sprintf('The private key file is not readable: %s', $this->privateKeyFile);
                $io->error(\end($errors));
            }
        }

        if (false === $input->getOption('decrypt-only')) {
            try {
                $encrypted = $shh->encrypt('foo');
            } catch (\Throwable $e) {
                $errors[] = 'Encrypting a payload failed.';
                $io->error(\end($errors));
                if ($io->isDebug()) {
                    $io->error($e->getMessage());
                }
            }

            if (isset($encrypted) && false === $input->getOption('encrypt-only')) {
                try {
                    $decrypted = $shh->decrypt($encrypted);

                    if ('foo' !== $decrypted) {
                        throw new \RuntimeException('Unexpected decrypted value.');
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Decrypting a payload failed. Is the passphrase correct?';
                    $io->error(\end($errors));
                    if ($io->isDebug()) {
                        $io->error($e->getMessage());
                    }
                }
            }
        }

        if (null !== $input->getOption('payload')) {
            try {
                $shh->decrypt($input->getOption('payload'));
            } catch (\Throwable $e) {
                $errors[] = 'The given payload could not be decrypted.';
                $io->error(\end($errors));
                if ($io->isDebug()) {
                    $io->error($e->getMessage());
                }
            }
        }

        $success = [] === $errors;

        if ($success) {
            $io->success('Your configuration seems correct. 😎');

            if (true=== $input->getOption('decrypt-only') && null === $input->getOption('payload')) {
                $io->comment('Hint: to be sure payloads can be properly decoded, try this command with the <info>--payload</info> option.');
                $io->comment('You can run the <info>shh:encrypt</info> on the server having the private key to get an encrypted payload to test.');
            }

            return 0;
        }

        return 1;
    }
}

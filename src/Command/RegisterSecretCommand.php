<?php

namespace BenTools\Shh\Command;

use BenTools\Shh\Shh;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class RegisterSecretCommand extends Command
{
    protected static $defaultName = 'shh:register:secret';

    /**
     * @var Shh
     */
    private $shh;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $secretsFile;

    public function __construct(Shh $shh, Filesystem $fs, string $secretsFile)
    {
        parent::__construct();
        $this->shh = $shh;
        $this->fs = $fs;
        $this->secretsFile = $secretsFile;
    }

    protected function configure()
    {
        $this->setDescription('Register a new secret.')
            ->addArgument('key', InputArgument::REQUIRED, 'The secret\'s key name.')
            ->addArgument('value', InputArgument::REQUIRED, 'The secret\'s value.')
            ->addOption('no-encrypt', null, InputOption::VALUE_NONE, 'If the value should not be encrypted.')
            ->setAliases([
                'shh:register-secret', // Avoid BC breaks
            ])
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $input->getArgument('key')) {
            $input->setArgument(
                'key',
                $io->ask(
                    'Enter the secret\'s key:',
                    null,
                    function ($key) {
                        return $this->validateKey($key);
                    }
                )
            );
        }

        if (null === $input->getArgument('value')) {
            $input->setArgument(
                'value',
                $io->askHidden(
                    'Enter the secret\'s value:',
                    function ($key) {
                        return $this->validateValue($key);
                    }
                )
            );
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $key = $this->validateKey($input->getArgument('key'));
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        try {
            $value = $this->validateValue($input->getArgument('value'));
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $encrypted = (true === $input->getOption('no-encrypt')) ? $value : $this->shh->encrypt($value);

        if (!$this->fs->exists($this->secretsFile)) {
            $secrets = [];
        } else {
            $content = \file_get_contents($this->secretsFile);
            $secrets = '' === $content ? [] : \json_decode($content, true);
            if (\JSON_ERROR_NONE !== \json_last_error()) {
                $io->error('json_decode error: ' . \json_last_error_msg());

                return 1;
            }
        }

        if (\array_key_exists($key, $secrets) && false === $io->confirm(sprintf('Key "%s" already exists. Overwrite?', $key))) {
            $io->success(sprintf('Your %s file was left intact.', \basename($this->secretsFile)));

            return;
        }

        $secrets[$key] = $encrypted;

        $encoded = \json_encode($secrets, \JSON_PRETTY_PRINT);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            $io->error('json_encode error: ' . \json_last_error_msg());

            return 1;
        }

        $this->fs->dumpFile($this->secretsFile, $encoded);

        $io->success(sprintf('Your %s file has been successfully updated!', \basename($this->secretsFile)));

        $io->comment('Tip: you can use your new secret as a parameter:');

        $io->writeln(
            <<<EOF
# config/services.yaml
parameters:
    {$key}: '%env(shh:key:{$key}:json:file:SECRETS_FILE)%'
    
EOF
        );
    }

    /**
     * @param string $key
     * @return string
     */
    private function validateKey(?string $key): string
    {
        if (!isset($key[0])) {
            throw new \InvalidArgumentException('Key cannot be empty.');
        }

        if (false !== \filter_var($key, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/[^a-z_\-0-9]/i']])) {
            throw new \InvalidArgumentException('Key can only contain alphanumeric and underscore characters.');
        }

        return $key;
    }

    /**
     * @param string $value
     * @return string
     */
    private function validateValue(string $value): string
    {
        if (!isset($value[0])) {
            throw new \InvalidArgumentException('Value cannot be empty.');
        }

        return $value;
    }
}

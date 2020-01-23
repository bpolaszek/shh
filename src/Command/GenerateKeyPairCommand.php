<?php

namespace BenTools\Shh\Command;

use BenTools\Shh\Shh;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class GenerateKeyPairCommand extends Command
{
    protected static $defaultName = 'shh:generate:keys';

    /**
     * @var string
     */
    private $keysDirectory;

    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct(string $keysDirectory, Filesystem $fs)
    {
        parent::__construct();
        $this->keysDirectory = $keysDirectory;
        $this->fs = $fs;
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate public/private keys.')
            ->addOption('passphrase', '', InputOption::VALUE_OPTIONAL, '(Optional) your passhrase.')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $input->getOption('passphrase')) {
            $input->setOption('passphrase', $io->askHidden(
                'Enter your new passphrase:',
                function ($passphrase) use ($io) {

                    if (0 === \strlen($passphrase)) {
                        return null;
                    }

                    if ($passphrase !== $io->askHidden('Confirm:')) {
                        throw new \InvalidArgumentException("Both passphrases don't match.");
                    }

                    return $passphrase;
                }
            ));
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $passphrase = $input->getOption('passphrase');
        $dir = $this->keysDirectory;

        if ($this->fs->exists($dir.'/private.pem') || $this->fs->exists($dir.'/public.pem')) {
            $io->error(\sprintf("Keys are already defined in %s.", $dir));

            return 1;
        }

        list($publicKey, $privateKey) = Shh::generateKeyPair($passphrase);

        $this->fs->dumpFile($dir.'/private.pem', $privateKey);
        $this->fs->dumpFile($dir.'/public.pem', $publicKey);

        $io->success(\sprintf('%s was successfully created', $dir.'/private.pem'));
        $io->success(\sprintf('%s was successfully created', $dir.'/public.pem'));

        if (null !== $passphrase) {
            $io->comment('Don\'t forget to report your passphrase into the SHH_PASSPHRASE environment variable.');
        }

        return 0;
    }
}

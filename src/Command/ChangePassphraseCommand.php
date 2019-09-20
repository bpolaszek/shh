<?php

namespace BenTools\Shh\Command;

use BenTools\Shh\Shh;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

final class ChangePassphraseCommand extends Command
{
    protected static $defaultName = 'shh:change:passphrase';

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
    private $keysDir;

    /**
     * @var string|null
     */
    private $privateKey;

    public function __construct(Shh $shh, Filesystem $fs, string $keysDir, ?string $privateKey)
    {
        parent::__construct();
        $this->shh = $shh;
        $this->fs = $fs;
        $this->keysDir = $keysDir;
        $this->privateKey = $privateKey;
    }

    protected function configure()
    {
        $this
            ->setDescription('Change passphrase, generate a new private key.')
            ->addOption('old-passphrase', '', InputOption::VALUE_OPTIONAL, 'Your current passhrase.')
            ->addOption('new-passphrase', '', InputOption::VALUE_OPTIONAL, '(Optional) your new passhrase.')
            ->addOption('overwrite', '', InputOption::VALUE_NONE, 'Overwrite the existing key.')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $input->getOption('old-passphrase')) {
            $input->setOption('old-passphrase', $io->askHidden(
                'Enter your old passphrase:',
                function ($passphrase) use ($io) {

                    if (0 === \strlen($passphrase)) {
                        return null;
                    }

                    return $passphrase;
                }
            ));
        }

        if (null === $input->getOption('new-passphrase')) {
            $input->setOption('new-passphrase', $io->askHidden(
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

        $input->setOption('overwrite', $io->confirm('Overwrite current private key?'));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $this->privateKey) {
            $io->error('Private key was not found.');

            return 1;
        }

        $passphrase = $input->getOption('new-passphrase');
        $dir = $this->keysDir;

        $oldPrivateKey = $this->privateKey;
        $newPrivateKey = Shh::changePassphrase($oldPrivateKey, $input->getOption('old-passphrase'), $passphrase);

        $io->comment('Here is your new private key:');

        $io->writeln($newPrivateKey);

        if (true === $input->getOption('overwrite')) {
            $this->fs->dumpFile($dir . '/private.pem', $newPrivateKey);
            $io->success(\sprintf('%s was successfully updated.', $dir . '/private.pem'));
        }

        $io->caution('Don\'t forget to report your new passphrase into the SHH_PASSPHRASE environment variable, and to deploy the new private key to everywhere it\'s needed!');
    }
}

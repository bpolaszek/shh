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
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Shh
     */
    private $shh;

    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct(ContainerInterface $container, Shh $shh, Filesystem $fs)
    {
        parent::__construct();
        $this->container = $container;
        $this->shh = $shh;
        $this->fs = $fs;
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate public/private keys.')
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

        $passphrase = $input->getOption('new-passphrase');
        $dir = $this->getDirectory();

        $oldPrivateKey = $this->container->getParameter('shh.private_key_file');
        $newPrivateKey = Shh::changePassphrase($oldPrivateKey, $input->getOption('old-passphrase'), $passphrase);

        $io->comment('Here is your new private key:');

        $io->writeln($newPrivateKey);

        if (true === $input->getOption('overwrite')) {
            $this->fs->dumpFile($dir . '/private.pem', $newPrivateKey);
            $io->success(\sprintf('%s was successfully updated.', $dir . '/private.pem'));
        }

        $io->caution('Don\'t forget to report your new passphrase into the SHH_PASSPHRASE environment variable, and to deploy the new private key to everywhere it\'s needed!');
    }

    /**
     * @return string
     */
    private function getDirectory()
    {
        if (Kernel::MAJOR_VERSION < 4) {
            return $this->container->getParameter('kernel.project_dir') . '/app/config/shh';
        }

        return $this->container->getParameter('kernel.project_dir') . '/config/shh';
    }
}

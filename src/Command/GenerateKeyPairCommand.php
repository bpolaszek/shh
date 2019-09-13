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

final class GenerateKeyPairCommand extends Command
{
    protected static $defaultName = 'shh:generate:keys';

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $passphrase = $input->getOption('passphrase');
        $dir = $this->getDirectory();

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
    }

    /**
     * @return string
     */
    private function getDirectory()
    {
        if (Kernel::MAJOR_VERSION < 4) {
            return $this->container->getParameter('kernel.project_dir').'/app/config/shh';
        }

        return $this->container->getParameter('kernel.project_dir').'/config/shh';
    }
}

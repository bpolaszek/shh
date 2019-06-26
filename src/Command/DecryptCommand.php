<?php

namespace BenTools\Shh\Command;

use BenTools\Shh\Shh;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DecryptCommand extends Command
{
    protected static $defaultName = 'shh:decrypt';

    /**
     * @var Shh
     */
    private $shh;

    public function __construct(Shh $shh)
    {
        parent::__construct();
        $this->shh = $shh;
    }

    protected function configure()
    {
        $this
            ->setDescription('Decrypts a value.')
            ->addArgument('payload', InputArgument::OPTIONAL, 'The value to decrypt.')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $input->getArgument('payload')) {
            $input->setArgument('payload', $io->askHidden(
                'Enter the value to decrypt:',
                function ($payload) {

                    if (!isset($payload[0])) {
                        throw new \InvalidArgumentException("Invalid value.");
                    }

                    return $payload;
                }
            ));
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Pikirasa\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if ($io->isQuiet()) {
            throw new \RuntimeException('This command is not intended to be run in quiet mode.');
        }

        $decrypted = $this->shh->decrypt($input->getArgument('payload'));

        if ($io->isVerbose()) {
            $io->comment('Here \'s your decrypted payload:');
        }

        $io->writeln($decrypted);
    }
}

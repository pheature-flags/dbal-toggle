<?php

declare(strict_types=1);

namespace Pheature\Dbal\Toggle\Cli;

use Pheature\Dbal\Toggle\DbalSchema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class InitSchema extends Command
{
    private DbalSchema $dbalSchema;

    public function __construct(DbalSchema $dbalSchema)
    {
        parent::__construct();
        $this->dbalSchema = $dbalSchema;
    }

    protected function configure(): void
    {
        $this->setName('pheature:dbal:init-toggle')
            ->setDescription('Create Pheature toggles database schema.')
            ->addOption(
                'init-if-not-exists',
                null,
                InputOption::VALUE_NONE,
                'Initialize DB toggle schema if not exists'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dbalSchema->__invoke($input->getOption('init-if-not-exists') ?? false);

        $output->writeln('<info>Pheature Toggle database schema successfully created.</info>');

        return 0;
    }
}

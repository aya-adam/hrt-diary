<?php

namespace App\Command;

use App\Service\DefaultDataSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-default-data',
    description: 'Seed the database with necessary default data',
)]
class SeedDefaultDataCommand extends Command
{
    public function __construct(
        private readonly DefaultDataSeeder $seeder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->seeder->seed();

        $io = new SymfonyStyle($input, $output);
        $io->success('Default data seeded.');

        return Command::SUCCESS;
    }
}

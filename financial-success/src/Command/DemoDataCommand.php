<?php

namespace App\Command;

use App\Service\FinancialService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:demo-data',
    description: 'Populating our database with demo data for testing and development purposes.',
)]
class DemoDataCommand
{
    public function __construct(
        private FinancialService $financialService
    )  {}

    public function __invoke(SymfonyStyle $io): int
    {
        $io->title(message: 'Creating demo data...');

        try {
            $this->createAccounts(io: $io);
            $this->createTransactions(io: $io);

            $io->success(message: 'Demo data created successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(message: 'Error creating demo data: ' . $e->getMessage());

            return Command::FAILURE;
            }
    }

    private function createAccounts(SymfonyStyle $io): void
    {
        $io->session(message: 'Creating demo accounts...');

        $accounts = [
            ['Jonathan Doe', '1234567890', 50000.00, '123-45-6789', 'jonathan.doe@example.com'],
            ['Jane Smithy', '8675309876', 75000.00, '987-65-4321', 'jane.smithy@example.com'],
            ['Alice Johnson', '5551234567', 100000.00, '111-22-3333', 'alice.johnson@example.com'],
            ['Robin Williams', '4449876543', 25000.00, '444-55-7777', 'robin.williams@example.com']
        ];

        foreach ($accounts as [$name, $number, $balance, $ssn, $email]) {
            $account = $this->financialService->createAccount(customerName: $name, accountNumber: $number, balance: $balance, ssn: $ssn, email: $email);
            $io->text(message: "Created account for {$name} (ID: {$account->getId()})");
        }
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}

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
        $io->section(message: 'Creating demo accounts...');

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

    private function createTransactions(SymfonyStyle $io): void
    {
        $io->session(message: 'Creating demo transactions...');

        $transactions = [
            ['1234567890', 1500.00, 'deposit', 'Initial deposit', '4111111111111111', '123', '12/25', 'Amazon', new \DateTime('2024-01-15')],
            ['1234567890', 200.00, 'withdrawal', 'ATM withdrawal', '4111111111111111', '123', '12/25', 'ATM Withdrawal', new \DateTime('2024-02-10')],
            ['8675309876', 5000.00, 'deposit', 'Paycheck deposit', '5555555555554444', '456', '11/24', 'Employer Inc.', new \DateTime('2024-01-30')],
            ['8675309876', 300.00, 'withdrawal', 'Grocery shopping', '5555555555554444', '456', '11/24', 'Whole Foods Market', new \DateTime('2024-02-05')],
            ['5551234567', 10000.00, 'deposit', 'Freelance payment', '378282246310005', '789', '10/23', 'Client LLC', new \DateTime('2024-01-20')],
            ['5551234567', 1500.00, 'withdrawal', 'Online shopping spree', '378282246310005', '789', '10/23', 'Best Buy Online Store', new \DateTime('2024-02-12')],
            ['4449876543', 2500.00, 'deposit', "Robin's paycheck", '6011111111111117', '321', '09/26', "Robin's Employer", new \DateTime('2024-01-25')],
            ['4449876543', 500.00, 'withdrawal', "Robin's dinner out", '6011111111111117', '321', '09/26', "Fancy Restaurant", new \DateTime('2024-02-14')]
        ];

        foreach ($transactions as [$accountNumber, $amount, $type, $description, $cardNumber, $cvv, $expiryDate, $merchantName, $transactionDate]) {
            $transaction = $this->financialService->createTransaction(accountNumber: $accountNumber, amount: $amount, transactionType: $type, description: $description, cardNumber: $cardNumber, cvv: $cvv, expiryDate: $expiryDate, merchantName: $merchantName, transactionDate: $transactionDate);
            $io->text(message: "Created {$type} transaction of \${$amount} for account {$accountNumber} (ID: {$transaction->getId()})");
        }
    }
}
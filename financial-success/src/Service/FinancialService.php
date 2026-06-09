<?php

namespace App\Service;

use App\Document\Account;
use App\Document\Transaction;
use Doctrine\ODM\MongoDB\DocumentManager; //Handles encryption privately

class FinancialService // Handles financial operations for the application
{
    public function __construct(
        private DocumentManager $documentManager
    ) {}

    public function createAccount(string $customerName, string $accountNumber, float $balance, string $ssn, string $email): Account
    {
        $account = new Account();
        $account->setCustomerName($customerName);
        $account->setAccountNumber($accountNumber);
        $account->setBalance($balance);
        $account->setSsn($ssn);
        $account->setEmail($email);

        // Saving the account to the MongoDB database
        $this->documentManager->persist($account);
        $this->documentManager->flush();

        return $account;
    }


public function createTransaction(string $accountNumber, float $amount, string $transactionType, string $description, string $cardNumber, string $cvv, string $expiryDate, string $merchantName) : Transaction
{
    $transaction = new Transaction();
    $transaction->setAccountNumber($accountNumber);
    $transaction->setAmount($amount);
    $transaction->setTransactionType($transactionType);
    $transaction->setDescription($description);
    $transaction->setCardNumber($cardNumber);
    $transaction->setCvv($cvv);
    $transaction->setExpiryDate($expiryDate);
    $transaction->setMerchantName($merchantName);

    // Saving the transaction to the MongoDB database
    $this->documentManager->persist($transaction);
    $this->documentManager->flush();

    return $transaction;
}

// getter functions or "find" methods to retrieve accounts and transactions from the database

public function findAccountByNumber(string $accountNumber): ?Account
{
    return $this->documentManager->getRepository(Account::class)
        ->findOneBy(['accountNumber' => $accountNumber]); //standard 'Doctrine' repository call
}

public function findAccountsByBalanceRange(float $minBalance, float $maxBalance): array
{
    $qb = $this->documentManager->createQueryBuilder(Account::class); // Creating a 'query builder' for the 'Account' document
    $qb->field('balance')->gte($minBalance)->lte($maxBalance); // Setting the proper condition for the 'balance' field

    return $qb->getQuery()->execute()->toArray(); // 'getQuery()' method is used to get the query object, and 'execute()' method is used to execute the query and return the result as an array
}

public function findTransactionsByAccountNumber(string $accountNumber): array
{
    return $this->documentManager->getRepository(Transaction::class)
        ->findBy(['accountNumber' => $accountNumber]);
}

 public function findTransactionsByAmountRange(float $minAmount, float $maxAmount): array
 {
    $qb = $this->documentManager->createQueryBuilder(Transaction::class);
    $qb->field('amount')->gte($minAmount)->lte($maxAmount);

    return $qb->getQuery()->execute()->toArray();
}

    public function findAccountBySsn(string $ssn): ?Account
    {
        return $this->documentManager->getRepository(Account::class)
            ->findOneBy(['ssn' => $ssn]);
    }

    public function getAllAccounts(): array
    {
        return $this->documentManager->getRepository(Account::class)->findAll();
    }

    public function getAllTransactions(): array
    {
        return $this->documentManager->getRepository(Transaction::class)->findAll();
    }
}
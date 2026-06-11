<?php

namespace App\Controller;

use App\Service\FinancialService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FinancialController extends AbstractController
{
    public function __construct(
        private FinancialService $financialService
    ) {}

    #[Route('/', name: 'homepage')]

    // 'Autowire' is used to inject the project directory path. Accessing files without hardcoding the path. This is a good practice for security and maintainability.
    public function homepage(#[Autowire(param: 'kernel.project_dir')] string $projectDir): Response
    {
        $html = file_get_contents($projectDir, '/public/index.html');
        return new Response(content: $html, status: 200, headers: ['Content-Type' => 'text/html']);
    }

    // Passing back data in a JSON response with a success flag.
    private function successResponse($data, int $status = 200): JsonResponse
    {
        return $this->json(data: ['success' => true, ...$data], status: $status);
    }

    // passing back an error message in a JSON format with an error flag.
    private function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return $this->json(data: ['success' => false, 'message' => $message], status: $status);
    }

    // Account creation for users.
    #[Route(path: '/api/harmony/accounts', methods: ['POST'])]

    public function createAccount(request $request): JsonResponse
    {
        try {
            //get request data without having to parse the JSON manually
            $data = $request->getPayload();
            $account = $this->financialService->createAccount(
                customerName: $data->get(key: 'customerName'),
                accountNumber: $data->get(key: 'accountNumber'),
                balance: (float) $data->get(key: 'balance'),
                ssn: $data->get(key: 'ssn'),
                email: $data->get(key: 'email')
            );

            return $this->successResponse(data: [
                'message' => 'Account created successfully',
                'accountId' => $account->getId()
            ], status: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: 'Error creating new account' . $e->getMessage());
        }
    }

    #[Route(path: '/api/harmony/transactions', methods: ['POST'])]

    public function createTransaction(Request $request): JsonResponse
    {
        try {
            // get request data for transactions without having to parse the JSON manually
            $data = $request->getPayload();
            $transaction = $this->financialService->createTransaction(
                accountNumber: $data->get(key: 'accountNumber'),
                amount: (float) $data->get(key: 'amount'),
                transactionType: $data->get(key: 'transactionType'),
                description: $data->get(key: 'description'),
                cardNumber: $data->get(key: 'cardNumber'),
                cvv: $data->get(key: 'cvv'),
                expiryDate: $data->get(key: 'expiryDate'),
                merchantName: $data->get(key: 'merchantName')
            );
            return $this->successResponse(data: [
                'message' => 'Transaction completed successfully',
                'transactionId' => $transaction->getId()
            ], status: 201);
        } catch (\Exception $e) {
            return $this->errorResonse(message: 'Error creating new transaction: ' . $e->getMessage());
        }
        
    }

    #[Route(path: '/api/harmony/accounts/balance-range', methods: ['GET'])]

    public function getAccountsByBalanceRange(Request $request): JsonResponse
    {
        $minBalance = (float) $request->query->get(key: 'min', default: 0);
        $maxBalance = (float) $request->query->get(key: 'max', default: 10000000);

        $accounts = $this->financialService->findAccountsByBalanceRange(minBalance: $minBalance, maxBalance: $maxBalance);

        $accountData = array_map(callback: fn($account): array => [
            'id' => $account->getId(),
            'customerName' => $account->getCustomerName(),
            'accountNumber' => $account->getAccountNumber(),
            'balance' => $account->getBalance(),
            'email' => $account->getEmail(),
            'createdAt' => $account->getCreatedAt()->format('Y-m-d H:i:s')
        ], array: $accounts);
    }
    #[Route(path: '/api/harmony/accounts/{accoutNumber}', methods: ['GET'])]

    public function getAccountByNumber(string $accountNumber): JsonResponse
    {
        $account = $this->financialService->findAccountByNumber(accountNumber: $accountNumber);

        if (!$account) {
            return $this->errorResponse(message: 'Account not found', status: 404);
        }

        return $this->successResponse(data: [
            'account' => [
                'id' => $account->getId(),
                'customerName' => $account->getCustomerName(),
                'accountNumber' => $account->getAccountNumber(),
                'ssn' => $account->getSsn(),
                'balance' => $account->getBalance(),
                'email' => $account->getEmail(),
                'createdAt' => $account->getCreatedAt()->format(format: 'Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route(path: 'api/harmony/transactions/account/{accountNumber}', methods: ['GET'])]

    public function getTransactionsByAccountNumber(string $accountNumber): JsonResponse
    {
        $transactions = $this->financialService->findTransactionsByAccountNumber(accountNumber: $accountNumber);

        $transactionData = array_map(callback: fn($transaction): array => [
            'id' => $transaction->getId(),
            'accountNumber' => $transaction->getAccountNumber(),
            'amount' => $transaction->getAmount(),
            'transactionType' => $transaction->getTransactionType(),
            'description' => $transaction->getDescription(),
            'merchantName' => $transaction->getMerchantName(),
            'status' => $transaction->getStatus(),
            'transactionDate' => $transaction->getTransactionDate()->format(format: 'Y-m-d H:i:s')
        ], array: $transactions);

        return $this->successResponse(data: [
            'transactions' => $transactionData,
            'count' => count(value: $transactionData)
        ]);
    }

     #[Route('/api/harmony/transactions/amount-range', methods: ['GET'])]
    public function getTransactionsByAmountRange(Request $request): JsonResponse
    {
        $minAmount = (float) $request->query->get('min', 0);
        $maxAmount = (float) $request->query->get('max', 1000000);

        $transactions = $this->financialService->findTransactionsByAmountRange($minAmount, $maxAmount);
        
        $transactionData = array_map(fn($transaction) => [
            'id' => $transaction->getId(),
            'accountNumber' => $transaction->getAccountNumber(),
            'amount' => $transaction->getAmount(),
            'transactionType' => $transaction->getTransactionType(),
            'description' => $transaction->getDescription(),
            'merchantName' => $transaction->getMerchantName(),
            'status' => $transaction->getStatus(),
            'transactionDate' => $transaction->getTransactionDate()->format('Y-m-d H:i:s')
        ], $transactions);

        return $this->successResponse([
            'transactions' => $transactionData,
            'count' => count($transactionData)
        ]);
    }

     #[Route('/api/harmony/accounts/ssn/{ssn}', methods: ['GET'])]
    public function getAccountBySsn(string $ssn): JsonResponse
    {
        $account = $this->financialService->findAccountBySsn($ssn);
        
        if (!$account) {
            return $this->errorResponse('Account not found', 404);
        }

        return $this->successResponse([
            'account' => [
                'id' => $account->getId(),
                'customerName' => $account->getCustomerName(),
                'accountNumber' => $account->getAccountNumber(),
                'balance' => $account->getBalance(),
                'email' => $account->getEmail(),
                'createdAt' => $account->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/api/harmony/accounts', methods: ['GET'])]
    public function getAllAccounts(): JsonResponse
    {
        $accounts = $this->financialService->getAllAccounts();
        
        $accountData = array_map(fn($account) => [
            'id' => $account->getId(),
            'customerName' => $account->getCustomerName(),
            'accountNumber' => $account->getAccountNumber(),
            'balance' => $account->getBalance(),
            'email' => $account->getEmail(),
            'createdAt' => $account->getCreatedAt()->format('Y-m-d H:i:s')
        ], $accounts);

        return $this->successResponse([
            'accounts' => $accountData,
            'count' => count($accountData)
        ]);
    }

    #[Route('/api/harmony/transactions', methods: ['GET'])]
    public function getAllTransactions(): JsonResponse
    {
        $transactions = $this->financialService->getAllTransactions();
        
        $transactionData = array_map(fn($transaction) => [
            'id' => $transaction->getId(),
            'accountNumber' => $transaction->getAccountNumber(),
            'amount' => $transaction->getAmount(),
            'transactionType' => $transaction->getTransactionType(),
            'description' => $transaction->getDescription(),
            'merchantName' => $transaction->getMerchantName(),
            'status' => $transaction->getStatus(),
            'transactionDate' => $transaction->getTransactionDate()->format('Y-m-d H:i:s')
        ], $transactions);

        return $this->successResponse([
            'transactions' => $transactionData,
            'count' => count($transactionData)
        ]);
    }

    #[Route('/api/harmony/accounts/{accountNumber}/summary', methods: ['GET'])]
    public function getAccountSummary(string $accountNumber): JsonResponse
    {
        $account = $this->financialService->findAccountByNumber($accountNumber);
        
        if (!$account) {
            return $this->errorResponse('Account not found', 404);
        }

        $transactions = $this->financialService->findTransactionsByAccountNumber($accountNumber);
        $totalTransactions = count($transactions);
        $totalAmount = array_sum(array_map(fn($t) => $t->getAmount(), $transactions));

        return $this->successResponse([
            'account' => [
                'id' => $account->getId(),
                'customerName' => $account->getCustomerName(),
                'accountNumber' => $account->getAccountNumber(),
                'balance' => $account->getBalance(),
                'email' => $account->getEmail()
            ],
            'summary' => [
                'totalTransactions' => $totalTransactions,
                'totalAmount' => $totalAmount,
                'averageTransactionAmount' => $totalTransactions > 0 ? $totalAmount / $totalTransactions : 0
            ]
        ]);
    }

    #[Route('/api/harmony/accounts/{accountNumber}/history', methods: ['GET'])]
    public function getAccountBalanceHistory(string $accountNumber, Request $request): JsonResponse
    {
        $account = $this->financialService->findAccountByNumber($accountNumber);
        
        if (!$account) {
            return $this->errorResponse('Account not found', 404);
        }

        $transactions = $this->financialService->findTransactionsByAccountNumber($accountNumber);
        
        // Simple balance history simulation
        $history = [];
        $runningBalance = $account->getBalance();
        
        foreach (array_reverse($transactions) as $transaction) {
            $runningBalance -= $transaction->getAmount();
            $history[] = [
                'date' => $transaction->getTransactionDate()->format('Y-m-d H:i:s'),
                'balance' => $runningBalance,
                'transaction' => $transaction->getAmount()
            ];
        }

        return $this->successResponse([
            'accountNumber' => $accountNumber,
            'currentBalance' => $account->getBalance(),
            'history' => $history
        ]);
    }

    #[Route('api/harmony/debug/encryption-status', methods: ['GET'])]
    public function getEncryptionStatus(): JsonResponse
    {
        return $this->successResponse([
            'encryption' => 'enabled',
            'queryableEncryption' => 'active',
            'encryptedFields' => [
                'accounts' => ['accountNumber', 'balance', 'ssn'],
                'transactions' => ['accountNumber', 'amount', 'cardNumber', 'cvv', 'expiryDate']
            ]
        ]);
    }
}

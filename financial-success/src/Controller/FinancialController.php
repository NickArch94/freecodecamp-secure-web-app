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

        return $this->successResponse(data: [
            'accounts' => $accountData,
            'count' => count(value: $accountData)
        ]);
    }
}


final class FinancialController extends AbstractController
{
    #[Route('/financial', name: 'app_financial')]
    public function index(): Response
    {
        return $this->render('financial/index.html.twig', [
            'controller_name' => 'FinancialController',
        ]);
    }
}

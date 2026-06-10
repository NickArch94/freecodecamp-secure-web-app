<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

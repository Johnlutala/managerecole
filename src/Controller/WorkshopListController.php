<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WorkshopListController extends AbstractController
{
    #[Route('/workshop/list', name: 'app_workshop_list')]
    public function index(): Response
    {
        return $this->render('workshop_list/index.html.twig', [
            'controller_name' => 'WorkshopListController',
        ]);
    }
}

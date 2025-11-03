<?php

namespace App\Controller;

use App\Repository\TipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TipController extends AbstractController
{
    #[Route('/tipps', name: 'app_tip_index', methods: ['GET'])]
    public function index(TipRepository $tipRepository): Response
    {
        return $this->render('tip/index.html.twig', [
            'tipps' => $tipRepository->getTipAndReadDate($this->getUser()),
        ]);
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MaterialController extends AbstractController{
    #[Route('/lernmaterialien', name: 'app_material_teaching')]
    public function lernmaterialien(): Response
    {
        return $this->render('teaching_material/index.html.twig', [
            'controller_name' => 'TeachingMaterialController',
        ]);
    }
    #[Route('/marketing', name: 'app_material_marketing')]
    public function marketingmaterialien(): Response
    {
        return $this->render('marketing_material/index.html.twig', [
            'controller_name' => 'MarketingMaterialController',
        ]);
    }
}

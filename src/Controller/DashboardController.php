<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Repository\TodoRepository;
use App\Repository\ExamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(UserRepository $userRepository, ExamRepository $examRepository, TodoRepository $todoRepository, NotificationRepository $notificationRepository): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'todos' => $todoRepository->findForDashboard($this->getUser()),
            'notifications' => $notificationRepository->findForDashboard($this->getUser()),
            'birthdays' => $userRepository->findUpcomingBirthdaysForDashboard(),
            'exams' => $examRepository->findForDashboard($this->getUser())
        ]);
    }
}

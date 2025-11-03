<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TutoringAppointmentController extends AbstractController
{
    #[Route('/nachhilfetermin/neu', name: 'app_tutoring_appointment_new', methods: ['GET'])]
    public function neuerTermin(): Response
    {
        return $this->render('tutoring_appointment/appointment.html.twig');
    }

    #[Route('/nachhilfetermin/{id}', name: 'app_tutoring_appointment_show', methods: ['GET'])]
    public function terminAnzeigen($id): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('tutoring_appointment/tutoringappointment.html.twig', [
            'appointment_id' => $id
        ]);
    }

    #[Route('/arbeitsstunden', name: 'app_work_hours_plan', methods: ['GET'])]
    public function arbeitsstundenPlanen(): Response
    {
        return $this->render(
            'tutoring_appointment/workhours.html.twig',
            ['user_id' => $this->getUser()->getId()]
        );

    }

    #[Route('/nachhilfetermin/planung', name: 'app_tutoring_appointment_planning', methods: ['GET'])]
    public function terminPlanung(): Response
    {
        return $this->render('tutoring_appointment/planning.html.twig', [
            'user_id' => $this->getUser()->getId()
        ]);
    }
}

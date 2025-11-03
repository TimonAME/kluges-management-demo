<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Form\CalendarType;
use App\Repository\CalendarRepository;
use App\Service\CalendarExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\AppointmentRepository;

#[Route('/kalender')]
class CalendarController extends AbstractController
{
    #[Route('/test', name: 'app_calendar_index', methods: ['GET'])]
    public function index(CalendarRepository $calendarRepository): Response
    {
        return $this->render('calendar/index.html.twig', [
            'calendars' => $calendarRepository->findAll(),
        ]);
    }

    #[Route('/', name: 'app_calendar_test')]
    public function test(): Response
    {
        return $this->render('calendar/test.html.twig');
    }

    #[Route('/new', name: 'app_calendar_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $calendar = new Calendar();
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($calendar);
            $entityManager->flush();

            return $this->redirectToRoute('app_calendar_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('calendar/new.html.twig', [
            'calendar' => $calendar,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_calendar_show', methods: ['GET'])]
    public function show(Calendar $calendar): Response
    {
        return $this->render('calendar/show.html.twig', [
            'calendar' => $calendar,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_calendar_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Calendar $calendar, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_calendar_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('calendar/edit.html.twig', [
            'calendar' => $calendar,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_calendar_delete', methods: ['POST'])]
    public function delete(Request $request, Calendar $calendar, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$calendar->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($calendar);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_calendar_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/api/export', name: 'app_calendar_export', methods: ['GET'])]
    public function export(
        Request $request, 
        AppointmentRepository $appointmentRepository,
        CalendarExportService $exportService
    ): Response
    {
        try {
            $startDate = $request->query->get('start');
            $endDate = $request->query->get('end');
            $category = $request->query->get('category');

            $appointments = $appointmentRepository->getAllAppointmentsForUser($this->getUser());
            
            if ($startDate) {
                $startDateTime = new \DateTime($startDate);
                $appointments = array_filter($appointments, fn($a) => $a->getStartTime() >= $startDateTime);
            }
            if ($endDate) {
                $endDateTime = new \DateTime($endDate);
                $appointments = array_filter($appointments, fn($a) => $a->getEndTime() <= $endDateTime);
            }
            if ($category) {
                $appointments = array_filter($appointments, fn($a) => $a->getAppointmentCategory()?->getId() == $category);
            }

            $icsContent = $exportService->generateICS($appointments);
            
            $response = new Response($icsContent);
            $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="kalender.ics"');
            // Wichtig: Verhindert Caching
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Pragma', 'private');
            
            return $response;
        } catch (\Exception $e) {
            return new Response('Export fehlgeschlagen: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

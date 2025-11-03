<?php

namespace App\Controller;

use App\Entity\NotificationTag;
use App\Form\NotificationTagType;
use App\Repository\NotificationTagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NotificationTagController extends AbstractController
{
    #[Route('/notification/tag', name: 'app_notification_tag_index', methods: ['GET'])]
    public function index(NotificationTagRepository $notificationTagRepository): Response
    {
        return $this->render('notification_tag/index.html.twig', [
            'notification_tags' => $notificationTagRepository->findAll(),
        ]);
    }

    #[Route('/notification/tag/new', name: 'app_notification_tag_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $notificationTag = new NotificationTag();
        $form = $this->createForm(NotificationTagType::class, $notificationTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($notificationTag);
            $entityManager->flush();

            return $this->redirectToRoute('app_notification_tag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('notification_tag/new.html.twig', [
            'notification_tag' => $notificationTag,
            'form' => $form,
        ]);
    }

    #[Route('/notification/tag/{id}', name: 'app_notification_tag_show', methods: ['GET'])]
    public function show(NotificationTag $notificationTag): Response
    {
        return $this->render('notification_tag/show.html.twig', [
            'notification_tag' => $notificationTag,
        ]);
    }

    #[Route('/notification/tag/{id}/edit', name: 'app_notification_tag_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, NotificationTag $notificationTag, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NotificationTagType::class, $notificationTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_notification_tag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('notification_tag/edit.html.twig', [
            'notification_tag' => $notificationTag,
            'form' => $form,
        ]);
    }

    #[Route('/notification/tag/{id}', name: 'app_notification_tag_delete', methods: ['POST'])]
    public function delete(Request $request, NotificationTag $notificationTag, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$notificationTag->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($notificationTag);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_notification_tag_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/api/notification/tags/search', name: 'app_notification_tag_search', methods: ['GET'])]
    public function searchTags(Request $request, NotificationTagRepository $notificationTagRepository): Response
    {
        $query = $request->query->get('query', '');
        $limit = (int) $request->query->get('limit', 10);

        $tags = $notificationTagRepository->searchTags($query, $limit);

        return $this->json(
            [
                'tags' => $tags,
            ]
        );
    }
}

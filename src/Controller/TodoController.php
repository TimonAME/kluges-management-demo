<?php

namespace App\Controller;

use App\Repository\TodoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TodoController extends AbstractController
{

    #[Route('/todo', name: 'app_todo_index', methods: ['GET'])]
    public function index(TodoRepository $todoRepository): Response
    {
        return $this->render('todo/index.html.twig', [
            'todos' => $todoRepository->findTodayTodos($this->getUser()),
        ]);
    }

    #[Route('/api/todos/{category}', name: 'get_todos_by_category', methods: ['GET'])]
    public function getTodosByCategory(
        string $category,
        Request $request,
        TodoRepository $todoRepository
    ): JsonResponse {
        $user = $this->getUser();
        $query = $request->query->get('query', '');

        $todos = match ($category) {
            'today' => $todoRepository->findTodayTodos($user),
            'upcoming' => $todoRepository->findUpcomingTodos($user),
            'inbox' => $todoRepository->findInboxTodos($user),
            'completed' => $todoRepository->findCompletedTodos($user),
            'search' => $todoRepository->searchTodos($user, $query),
            default => throw $this->createNotFoundException('Invalid category'),
        };

        return $this->json($todos, Response::HTTP_OK, [], [
            'groups' => ['todo:read:all']
        ]);
    }

}

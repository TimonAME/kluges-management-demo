<?php
// src/EventListener/LoginListener.php
namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener]
class LoginListener
{
    private Security $security;
    private RouterInterface $router;

    public function __construct(Security $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router = $router;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (in_array($request->attributes->get('_route'), ['app_login', 'app_logout', 'first_login', 'api_change_first_password'])) {
            return;
        }

        $user = $this->security->getUser();

        if ($user instanceof User && $user->isFirstLogin()) {
            $response = new RedirectResponse($this->router->generate('first_login'));
            $event->setResponse($response);
        }
    }
}
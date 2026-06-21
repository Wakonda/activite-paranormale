<?php

namespace App\EventListener;

use App\Entity\LoginHistory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSuccessListener
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        /** @var User $user */
        $user = $event->getUser();

        $history = new LoginHistory();
        $history->setUser($user);
        $history->setAttemptedIdentifier($user->getUserIdentifier());
        $history->setSuccess(true);
        $history->setIpAddress($request->getClientIp() ?? 'unknown');
        $history->setUserAgent($request->headers->get('User-Agent'));

        $this->em->persist($history);
        $this->em->flush();
    }
}
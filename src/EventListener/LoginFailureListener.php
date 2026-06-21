<?php

namespace App\EventListener;

use App\Entity\LoginHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

#[AsEventListener(event: LoginFailureEvent::class)]
class LoginFailureListener
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();

        $identifier = $request->request->get('email')
            ?? $request->request->get('_username')
            ?? 'unknown';

        $history = new LoginHistory();
        $history->setUser(null);
        $history->setAttemptedIdentifier($identifier);
        $history->setSuccess(false);
        $history->setIpAddress($request->getClientIp() ?? 'unknown');
        $history->setUserAgent($request->headers->get('User-Agent'));
        $history->setFailureReason($event->getException()->getMessageKey());

        $this->em->persist($history);
        $this->em->flush();
    }
}
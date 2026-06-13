<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    public function sendContactMail(
        string $to,
        string $subject,
        string $content,
		array $context
    ): void {

        $email = (new TemplatedEmail())
            ->from('contact@activite-paranormale.net')
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($content)
			->context($context);

        $this->mailer->send($email);
    }
}
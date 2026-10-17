<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class MailerService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendNewsletter(string $to, array $videoGames): void
    {
        $email = (new TemplatedEmail())
            ->from('no-reply@example.com')
            ->to($to)
            ->subject('Les jeux vidéo qui sortent cette semaine')
            ->htmlTemplate('emails/newsletter.html.twig')
            ->context(['videoGames' => $videoGames]);

        $this->mailer->send($email);
    }
}

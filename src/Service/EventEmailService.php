<?php

namespace App\Service;

use App\Entity\EventRegistration;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventEmailService
{
    private $mailer;
    private $translator;
    private $adminEmail;

    public function __construct(
        MailerInterface $mailer, 
        TranslatorInterface $translator,
        string $adminEmail = 'event@montessorialgerie.mia-dz.com'
    ) {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->adminEmail = $adminEmail;
    }

    public function sendConfirmationEmail(EventRegistration $registration, string $locale): void
    {
        // Définir la locale pour la traduction
        $this->translator->setLocale($locale);
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->adminEmail, 'École International'))
            ->to(new Address($registration->getEmail(), $registration->getFirstName() . ' ' . $registration->getLastName()))
            ->subject($this->translator->trans('email.confirmation.subject', [], 'messages', $locale))
            ->htmlTemplate('emails/' . $locale . '/event_confirmation.html.twig')
            ->context([
                'registration' => $registration,
            ]);

        $this->mailer->send($email);
    }

    public function sendAdminNotificationEmail(EventRegistration $registration, string $locale): void
    {
        // Définir la locale pour la traduction
        $this->translator->setLocale($locale);
        
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@montessorialgerie.mia-dz.com', 'Système de notification'))
            ->to(new Address($this->adminEmail, 'Administrateur'))
            ->subject($this->translator->trans('email.admin.subject', ['%name%' => $registration->getFirstName() . ' ' . $registration->getLastName()], 'messages', $locale))
            ->htmlTemplate('emails/' . $locale . '/event_admin_notification.html.twig')
            ->context([
                'registration' => $registration,
            ]);

        $this->mailer->send($email);
    }
}
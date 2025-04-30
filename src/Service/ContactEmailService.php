<?php

namespace App\Service;

use App\Entity\Contact;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContactEmailService
{
    private $mailer;
    private $urlGenerator;
    private $adminEmail;
    private $senderEmail;

    public function __construct(
        MailerInterface $mailer, 
        UrlGeneratorInterface $urlGenerator,
        string $adminEmail = 'info@mia-dz.com',
        string $senderEmail = 'noreply@mia-dz.com'
    ) {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->adminEmail = $adminEmail;
        $this->senderEmail = $senderEmail;
    }

    public function sendConfirmationEmail(Contact $contact): void
    {
        $email = (new TemplatedEmail())
            ->from($this->senderEmail)
            ->to($contact->getEmail())
            ->subject('Confirmation de votre demande - Montessori Algérie')
            ->htmlTemplate('emails/contact_confirmation.html.twig')
            ->context([
                'contact' => $contact,
            ]);

        $this->mailer->send($email);
    }

    public function sendAdminNotificationEmail(Contact $contact): void
    {
        $email = (new TemplatedEmail())
            ->from($this->senderEmail)
            ->to($this->adminEmail)
            ->subject('Nouvelle demande de contact - Montessori Algérie')
            ->htmlTemplate('emails/admin_notification.html.twig')
            ->context([
                'contact' => $contact,
            ]);

        $this->mailer->send($email);
    }
}
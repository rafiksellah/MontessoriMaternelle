<?php

namespace App\Service;

use App\Entity\EventRegistration;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EventEmailService
{
    private $mailer;
    private $translator;
    private $adminEmail;
    private $params;
    
    public function __construct(
        MailerInterface $mailer,
        TranslatorInterface $translator,
        ParameterBagInterface $params,
        string $adminEmail = 'event@montessorialgerie.mia-dz.com'
    ) {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->params = $params;
        $this->adminEmail = $adminEmail;
    }
    
    /**
     * Send confirmation email with attachments
     *
     * @param EventRegistration $registration
     * @param string $locale
     * @param string $invitationFilePath
     * @param string $programFilePath
     */
    public function sendConfirmationEmailWithAttachments(
        EventRegistration $registration,
        string $locale,
        string $invitationFilePath,
        string $programFilePath
    ): void {
        try {
            // Make sure we're using valid locale (fallback to 'en' if not in our supported list)
            $locale = in_array($locale, ['fr', 'en', 'ar']) ? $locale : 'en';
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->adminEmail, 'Montessori Algérie'))
                ->to(new Address($registration->getEmail(), $registration->getFirstName() . ' ' . $registration->getLastName()))
                ->subject($this->translator->trans('email.confirmation.subject', [], 'emails', $locale))
                ->htmlTemplate('emails/' . $locale . '/registration_confirmation.html.twig')
                ->context([
                    'registration' => $registration,
                ])
                // Attach the personalized invitation
                ->attachFromPath(
                    $invitationFilePath,
                    'invitation_barbecue.pdf',
                    'application/pdf'
                );
                // Attach the program
                // ->attachFromPath(
                //     $programFilePath,
                //     'programme.pdf',
                //     'application/pdf'
                // );
            
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log the error
            error_log('Email Sending Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send notification email to admin
     *
     * @param EventRegistration $registration
     * @param string $locale
     */
    public function sendAdminNotificationEmail(EventRegistration $registration, string $locale): void
    {
        try {
            // Make sure we're using valid locale (fallback to 'en' if not in our supported list)
            $locale = in_array($locale, ['fr', 'en', 'ar']) ? $locale : 'en';
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->adminEmail, 'Système d\'inscription'))
                ->to(new Address($this->adminEmail, 'Administrateur'))
                ->subject($this->translator->trans('email.admin.subject', [
                    '%firstName%' => $registration->getFirstName(),
                    '%lastName%' => $registration->getLastName()
                ], 'emails', $locale))
                ->htmlTemplate('emails/' . $locale . '/admin_notification_register_event.html.twig')
                ->context([
                    'registration' => $registration
                ]);
            
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log the error
            error_log('Admin Email Sending Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
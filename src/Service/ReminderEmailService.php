<?php

namespace App\Service;

use App\Entity\EventRegistration;
use App\Repository\EventRegistrationRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ReminderEmailService
{
    private $mailer;
    private $translator;
    private $eventRegistrationRepository;
    private $entityManager;
    private $logger;
    private $fromEmail;
    private $eventDate;
    private $confirmationDeadline;
    private $loginUrl;

    public function __construct(
        MailerInterface $mailer,
        TranslatorInterface $translator,
        EventRegistrationRepository $eventRegistrationRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        string $fromEmail = 'event@montessorialgerie.mia-dz.com',
        \DateTimeInterface $eventDate = null,
        \DateTimeInterface $confirmationDeadline = null,
        string $loginUrl = 'https://montessorialgerie.mia-dz.com/login'
    ) {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->eventRegistrationRepository = $eventRegistrationRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->fromEmail = $fromEmail;
        
        // Default event date if not provided
        $this->eventDate = $eventDate ?? new \DateTime('2025-05-30');
        
        // Default confirmation deadline if not provided
        $this->confirmationDeadline = $confirmationDeadline ?? new \DateTime('2025-05-25');
        
        // Login URL
        $this->loginUrl = $loginUrl;
    }

    /**
     * Send reminder emails to all registered participants
     * This should be called by a command/cron job
     */
    public function sendReminderEmails(): int
    {
        $eventDate = $this->eventDate;
        $now = new \DateTime();
        
        // Calculate if we're 5 days before the event
        $daysUntilEvent = $eventDate->diff($now)->days;
        
        $this->logger->info("Days until event: {$daysUntilEvent}");
        
        // If it's not 5 days before the event, don't send reminders
        if ($daysUntilEvent !== 5) {
            $this->logger->info("Not sending reminders today. Not 5 days before event.");
            return 0;
        }
        
        // Get all registered users who haven't received a reminder yet
        $registrations = $this->eventRegistrationRepository->findByReminderNotSent();
        
        $sentCount = 0;
        foreach ($registrations as $registration) {
            $locale = $registration->getUser()->getLocale() ?? 'fr';
            
            // Send reminder in appropriate language
            try {
                $this->sendReminderEmail($registration, $locale);
                
                // Mark reminder as sent
                $registration->setReminderSent(true);
                $this->entityManager->persist($registration);
                
                $sentCount++;
            } catch (\Exception $e) {
                $this->logger->error("Failed to send reminder to {$registration->getEmail()}: {$e->getMessage()}");
            }
        }
        
        // Flush all changes to database
        if ($sentCount > 0) {
            $this->entityManager->flush();
        }
        
        $this->logger->info("Sent {$sentCount} reminder emails");
        return $sentCount;
    }

    /**
     * Send a reminder email to a specific registration
     */
    private function sendReminderEmail(EventRegistration $registration, string $locale): void
    {
        // Make sure we're using valid locale (fallback to 'fr' if not in our supported list)
        $locale = in_array($locale, ['fr', 'en', 'ar']) ? $locale : 'fr';
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, 'École International'))
            ->to(new Address($registration->getEmail(), $registration->getFirstName() . ' ' . $registration->getLastName()))
            ->subject($this->translator->trans('email.reminder.subject', [], 'emails', $locale))
            ->htmlTemplate('emails/' . $locale . '/event_reminder.html.twig')
            ->context([
                'registration' => $registration,
                'eventDate' => $this->eventDate,
                'confirmationDeadline' => $this->confirmationDeadline,
                'loginUrl' => $this->loginUrl
            ]);
        
        $this->mailer->send($email);
    }
}
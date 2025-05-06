<?php

namespace App\Controller;

use App\Entity\EventRegistration;
use Symfony\Component\Mime\Email;
use App\Form\EventRegistrationType;
use App\Service\InvitationGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EventRegistrationController extends AbstractController
{
    private $translator;
    
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    
    #[Route('/event/register', name: 'event_register_no_locale')]
    #[Route('/{_locale}/event/register', name: 'event_register',requirements: ['_locale' => 'fr|en|ar'], defaults: ['_locale' => 'fr'])]
    public function register(
        Request $request, 
        EntityManagerInterface $em, 
        MailerInterface $mailer,
        InvitationGenerator $invitationGenerator
    ): Response
    {
        // Set memory limit for the request
        ini_set('memory_limit', '256M');
        
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            $this->addFlash('error', $this->translator->trans('event.error.login_required'));
            return $this->redirectToRoute('app_login');
        }
        
        // Vérifier si l'utilisateur a déjà une inscription
        if ($user->getEventRegistration()) {
            $registration = $user->getEventRegistration();
            $this->addFlash('info', $this->translator->trans('event.info.already_registered'));
        } else {
            $registration = new EventRegistration();
            $registration->setFirstName($user->getFirstName());
            $registration->setLastName($user->getLastName());
            $registration->setEmail($user->getEmail());
            $registration->setPhone($user->getPhone());
            $registration->setUser($user);
            $registration->setRegisteredAt(new \DateTimeImmutable());
        }
        
        $form = $this->createForm(EventRegistrationType::class, $registration);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($registration);
                $em->flush();
                // Mettre à jour la relation bidirectionnelle
                $user->setEventRegistration($registration);
                $em->persist($user);
                $em->flush();
                
                // Using English only as requested
                $locale = 'en';
                
                // Generate the personalized invitation with participant names
                $invitationPdf = $invitationGenerator->generateInvitation($registration);
                
                // Save the PDF to a temporary file with proper garbage collection
                $tmpDir = $this->getParameter('kernel.project_dir') . '/var/tmp';
    
                // Créer le dossier si nécessaire
                if (!file_exists($tmpDir)) {
                    mkdir($tmpDir, 0777, true);
                }
    
                $invitationFilePath = $tmpDir . '/invitation_' . $registration->getId() . '_' . uniqid() . '.pdf';
                file_put_contents($invitationFilePath, $invitationPdf);
                
                // Free memory
                unset($invitationPdf);
                
                // Envoyer l'email de confirmation à l'utilisateur avec invitation personnalisée
                $this->sendConfirmationEmailWithCustomInvitation($registration, $mailer, $locale, $invitationFilePath);
                $this->sendConfirmationEmail($registration, $mailer, $locale);
                
                // Remove the temporary file
                if (file_exists($invitationFilePath)) {
                    unlink($invitationFilePath);
                }
                
                $this->addFlash('success', $this->translator->trans('event.flash.registration_success'));
                return $this->redirectToRoute('event_register');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('event.error.registration_failed') . ': ' . $e->getMessage());
                // Log the error
                error_log('Registration Error: ' . $e->getMessage());
            }
        }
        
        return $this->render('event_registration/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    private function sendConfirmationEmailWithCustomInvitation(
        EventRegistration $registration, 
        MailerInterface $mailer, 
        string $locale,
        string $invitationFilePath

    ): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from('event@montessorialgerie.mia-dz.com')
                ->to($registration->getEmail())
                ->subject($this->translator->trans('email.confirmation.subject', [], 'emails', $locale))
                ->htmlTemplate('emails/'.$locale.'/registration_confirmation.html.twig')
                ->context([
                    'registration' => $registration
                ])
                // Attach the personalized invitation with participant names
                ->attachFromPath(
                    $invitationFilePath,
                    'invitation_barbecue.pdf',
                    'application/pdf'
                );
               // Ajout de la deuxième pièce jointe - plan d'accès si nécessaire
            $email->attachFromPath(
                $this->getParameter('kernel.project_dir') . '/public/assets/img/programe.pdf', 
                'programe.pdf',
                'application/pdf'
        );
            
            $mailer->send($email);
        } catch (\Exception $e) {
            // Log the error
            error_log('Email Sending Error: ' . $e->getMessage());
            throw $e;
        }
    }   
    private function sendAdminNotificationEmail(EventRegistration $registration, MailerInterface $mailer, string $locale): void
    {
        $email = (new TemplatedEmail())
            ->from('noreply@montessorialgerie.com')
            ->to('contact@montessorialgerie.com')
            ->subject($this->translator->trans('email.admin_notification.subject', [
                '%firstName%' => $registration->getFirstName(),
                '%lastName%' => $registration->getLastName()
            ], 'emails', $locale))
            ->htmlTemplate('emails/'.$locale.'/admin_notification_register_event.html.twig')
            ->context([
                'registration' => $registration
            ]);
        
        $mailer->send($email);
    }
    private function sendConfirmationEmail(EventRegistration $registration, MailerInterface $mailer, string $locale): void
    {
        $email = (new TemplatedEmail())
            ->from('event@montessorialgerie.mia-dz.com')
            ->to($registration->getEmail())
            ->subject($this->translator->trans('email.confirmation.subject', [], 'emails', $locale))
            ->htmlTemplate('emails/'.$locale.'/registration_confirmation.html.twig')
            ->context([
                'registration' => $registration
            ])
            // Ajouter la pièce jointe (invitation)
            ->attachFromPath(
                $this->getParameter('kernel.project_dir') . '/public/assets/img/invitation.jpg',
                'invitation.jpg',
                'image/jpeg'
            );
        
        // Ajout de la deuxième pièce jointe - plan d'accès si nécessaire
        // $email->attachFromPath(
        //     $this->getParameter('kernel.project_dir') . '/public/assets/img/plan_acces.pdf', 
        //     'plan_acces.pdf',
        //     'application/pdf'
        // );
        
        $mailer->send($email);
    }
    
}
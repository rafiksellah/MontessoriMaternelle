<?php

namespace App\Controller;

use App\Entity\EventRegistration;
use App\Form\EventRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EventRegistrationController extends AbstractController
{
    private $translator;
    
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    
    #[Route('/{_locale}/event/register', name: 'event_register')]
    public function register(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
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
            $em->persist($registration);
            $em->flush();
            
            // Mettre à jour la relation bidirectionnelle
            $user->setEventRegistration($registration);
            $em->persist($user);
            $em->flush();
            
            // Obtenir la locale actuelle pour les emails
            $locale = $request->getLocale();
            
            // Envoyer l'email de confirmation à l'utilisateur
            $this->sendConfirmationEmail($registration, $mailer, $locale);
            
            // Envoyer l'email de notification à l'administrateur
            $this->sendAdminNotificationEmail($registration, $mailer, $locale);
            
            $this->addFlash('success', $this->translator->trans('event.flash.registration_success'));
            return $this->redirectToRoute('event_register');
        }
        
        return $this->render('event_registration/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
    
    private function sendConfirmationEmail(EventRegistration $registration, MailerInterface $mailer, string $locale): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@montessorialgerie.com')
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
}
<?php

namespace App\Controller;

use App\Entity\EventRegistration;
use App\Form\EventRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class EventRegistrationController extends AbstractController
{
    #[Route('/event/register', name: 'event_register')]
    public function register(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire à cet événement.');
            return $this->redirectToRoute('app_login');
        }
        
        // Vérifier si l'utilisateur a déjà une inscription
        if ($user->getEventRegistration()) {
            $registration = $user->getEventRegistration();
            $this->addFlash('info', 'Vous êtes déjà inscrit à cet événement. Vous pouvez modifier votre inscription.');
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
            
            // Envoyer l'email de confirmation à l'utilisateur
            $this->sendConfirmationEmail($registration, $mailer);
            
            // Envoyer l'email de notification à l'administrateur
            $this->sendAdminNotificationEmail($registration, $mailer);
            
            $this->addFlash('success', 'Votre inscription a bien été enregistrée. Un email de confirmation a été envoyé.');
            return $this->redirectToRoute('event_register');
        }
        
        return $this->render('event_registration/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
    
    private function sendConfirmationEmail(EventRegistration $registration, MailerInterface $mailer): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@montessorialgerie.com')
            ->to($registration->getEmail())
            ->subject('Confirmation de votre participation au barbecue')
            ->htmlTemplate('emails/registration_confirmation.html.twig')
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
    
    private function sendAdminNotificationEmail(EventRegistration $registration, MailerInterface $mailer): void
    {
        $email = (new TemplatedEmail())
            ->from('noreply@montessorialgerie.com')
            ->to('contact@montessorialgerie.com')
            ->subject('Nouvelle inscription au barbecue - ' . $registration->getFirstName() . ' ' . $registration->getLastName())
            ->htmlTemplate('emails/admin_notification_register_event.html.twig')
            ->context([
                'registration' => $registration
            ]);
        
        $mailer->send($email);
    }
}
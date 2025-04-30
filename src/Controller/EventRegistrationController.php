<?php

namespace App\Controller;

use App\Entity\EventRegistration;
use App\Form\EventRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class EventRegistrationController extends AbstractController
{
    #[Route('/event/register', name: 'event_register')]
    public function register(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $registration = new EventRegistration();
        $form = $this->createForm(EventRegistrationType::class, $registration);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($registration);
            $em->flush();
            
            // Envoyer l'email de confirmation
            $this->sendConfirmationEmail($registration, $mailer);
            
            $this->addFlash('success', 'Votre inscription a bien été enregistrée. Un email de confirmation a été envoyé.');
            return $this->redirectToRoute('event_register');
        }
        
        return $this->render('event_registration/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
    
    private function sendConfirmationEmail(EventRegistration $registration, MailerInterface $mailer): void
    {
        $email = (new Email())
            ->from('contact@montessorialgerie.com')
            ->to($registration->getEmail())
            ->subject('Confirmation de votre participation au barbecue')
            ->html($this->renderView('emails/registration_confirmation.html.twig', [
                'registration' => $registration
            ]))
            // Ajouter la pièce jointe (invitation)
            ->attachFromPath(
                $this->getParameter('kernel.project_dir') . '/public/assets/img/invitation.jpg',
                'invitation.jpg',
                'image/jpeg'
            );
            
        $mailer->send($email);
    }
}
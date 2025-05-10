<?php

namespace App\Controller;

use App\Entity\EventRegistration;
use Symfony\Component\Mime\Email;
use App\Service\EventEmailService;
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

    private $eventEmailService;

    public function __construct(
        TranslatorInterface $translator,
        EventEmailService $eventEmailService
    ) {
        $this->translator = $translator;
        $this->eventEmailService = $eventEmailService;
    }


    #[Route('/event/register', name: 'event_register_no_locale')]
    #[Route('/{_locale}/event/register', name: 'event_register', requirements: ['_locale' => 'fr|en|ar'], defaults: ['_locale' => 'fr'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        InvitationGenerator $invitationGenerator
    ): Response {
        // Set memory limit for the request
        ini_set('memory_limit', '256M');

        $user = $this->getUser();
        $locale = $request->getLocale(); // Get the current locale from the request

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

                // Send confirmation email using the service
                $this->eventEmailService->sendConfirmationEmailWithAttachments(
                    $registration,
                    $locale,
                    $invitationFilePath,
                    $this->getParameter('kernel.project_dir') . '/public/assets/img/programme.pdf'
                );

                // Send admin notification
                $this->eventEmailService->sendAdminNotificationEmail($registration, $locale);

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
}

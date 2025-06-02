<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/admin/contacts')]
class AdminContactController extends AbstractController
{
    public function __construct(
        private ContactRepository $contactRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    #[Route('/', name: 'app_admin_contacts', methods: ['GET'])]
    public function index(): Response
    {
        $contacts = $this->contactRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin_contact/index.html.twig', [
            'contacts' => $contacts,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_contact_show', methods: ['GET'])]
    public function show(Contact $contact): Response
    {
        return $this->render('admin_contact/show.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/respond', name: 'app_admin_contact_respond', methods: ['POST'])]
    public function respond(Contact $contact, Request $request): Response
    {
        $response = $request->request->get('response');
        $customMessage = $request->request->get('message', '');
        $appointmentDate = $request->request->get('appointment_date');
        $rejectionReason = $request->request->get('rejection_reason');

        try {
            $email = (new Email())
                ->from('admin@votre-entreprise.com')
                ->to($contact->getEmail());

            $emailBody = '';
            $subject = '';

            switch ($response) {
                case 'appointment_scheduled':
                    $subject = 'Prise de rendez-vous - ' . $contact->getChildName();
                    $appointmentDateTime = new \DateTime($appointmentDate);

                    $contact->setAppointmentDate($appointmentDateTime);
                    $contact->setCustomMessage($customMessage);

                    $emailBody = $this->twig->render('emails/appointment_scheduled.html.twig', [
                        'contact' => $contact,
                        'appointmentDate' => $appointmentDateTime,
                        'customMessage' => $customMessage
                    ]);
                    break;

                case 'confirmed':
                    $subject = 'Confirmation de rendez-vous - ' . $contact->getChildName();
                    $contact->setCustomMessage($customMessage);

                    $emailBody = $this->twig->render('emails/appointment_confirmed.html.twig', [
                        'contact' => $contact,
                        'customMessage' => $customMessage
                    ]);
                    break;

                case 'rejected':
                    $subject = 'Candidature - ' . $contact->getChildName();
                    $contact->setRejectionReason($rejectionReason);
                    $contact->setCustomMessage($customMessage);

                    $emailBody = $this->twig->render('emails/rejection.html.twig', [
                        'contact' => $contact,
                        'rejectionReason' => $rejectionReason,
                        'customMessage' => $customMessage
                    ]);
                    break;
            }

            $email->subject($subject)->html($emailBody);
            $this->mailer->send($email);

            // Mettre à jour le statut du contact
            $contact->setStatus($response);
            $contact->setResponseDate(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'Email envoyé avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_admin_contact_delete', methods: ['POST'])]
    public function delete(Contact $contact): Response
    {
        $this->entityManager->remove($contact);
        $this->entityManager->flush();

        $this->addFlash('success', 'Contact supprimé avec succès !');
        return $this->redirectToRoute('app_admin_contacts');
    }
}

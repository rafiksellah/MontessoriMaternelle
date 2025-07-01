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

        // Nettoyer le message personnalisé
        $customMessage = trim($customMessage);
        if (empty($customMessage)) {
            $customMessage = null;
        }

        // Debug: Log du statut actuel
        error_log("Status actuel avant traitement: " . $contact->getStatus());
        error_log("Response reçue: " . $response);

        try {
            $email = (new Email())
                ->from('contact@montessorialgerie.mia-dz.com')
                ->to($contact->getEmail());

            $emailBody = '';
            $subject = '';
            $newStatus = null;

            switch ($response) {
                case Contact::STATUS_APPOINTMENT_SCHEDULED:
                    // Transition: pending → appointment_scheduled
                    if (!$contact->canScheduleAppointment()) {
                        $this->addFlash('error', 'Impossible de programmer un RDV pour ce contact dans son état actuel.');
                        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
                    }

                    $subject = 'Prise de rendez-vous - ' . $contact->getChildName();

                    if (empty($appointmentDate)) {
                        $this->addFlash('error', 'La date de rendez-vous est obligatoire.');
                        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
                    }

                    try {
                        $appointmentDateTime = new \DateTimeImmutable($appointmentDate);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Format de date invalide.');
                        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
                    }

                    $contact->setAppointmentDate($appointmentDateTime);
                    $contact->setCustomMessage($customMessage);
                    $newStatus = Contact::STATUS_APPOINTMENT_SCHEDULED;

                    $emailBody = $this->twig->render('emails/appointment_scheduled.html.twig', [
                        'contact' => $contact,
                        'appointmentDate' => $appointmentDateTime,
                        'customMessage' => $customMessage
                    ]);
                    break;

                case Contact::STATUS_CONFIRMED:
                    // Transition: appointment_scheduled → confirmed
                    if (!$contact->canConfirm()) {
                        $this->addFlash('error', 'Impossible de confirmer un RDV qui n\'est pas programmé.');
                        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
                    }

                    $subject = 'Confirmation de rendez-vous - ' . $contact->getChildName();

                    if (!$contact->getAppointmentDate()) {
                        $this->addFlash('error', 'Aucun rendez-vous programmé pour ce contact.');
                        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
                    }

                    $contact->setCustomMessage($customMessage);
                    $newStatus = Contact::STATUS_CONFIRMED;

                    $emailBody = $this->twig->render('emails/appointment_confirmed.html.twig', [
                        'contact' => $contact,
                        'customMessage' => $customMessage
                    ]);
                    break;

                case Contact::STATUS_AFTER_VISIT:
                    // Transition: confirmed → after_visit
                    if (!$contact->canSendAfterVisit()) {
                        $this->addFlash('error', 'Impossible d\'envoyer un email après visite sans confirmation préalable.');
                        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
                    }

                    $subject = 'Suite à votre visite - ' . $contact->getChildName();
                    $contact->setCustomMessage($customMessage);
                    $newStatus = Contact::STATUS_AFTER_VISIT;

                    $emailBody = $this->twig->render('emails/after_visit.html.twig', [
                        'contact' => $contact,
                        'customMessage' => $customMessage
                    ]);
                    break;

                case Contact::STATUS_INSCRIPTION_ACCEPTED:
                    // Transition: after_visit → inscription_accepted
                    if (!$contact->canAcceptInscription()) {
                        $this->addFlash('error', 'Impossible d\'accepter une inscription sans visite préalable.');
                        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
                    }

                    $subject = 'Inscription acceptée - ' . $contact->getChildName();
                    $contact->setCustomMessage($customMessage);
                    $newStatus = Contact::STATUS_INSCRIPTION_ACCEPTED;

                    $emailBody = $this->twig->render('emails/inscription_accepted.html.twig', [
                        'contact' => $contact,
                        'customMessage' => $customMessage
                    ]);

                    // Ajouter les pièces jointes
                    $projectDir = $this->getParameter('kernel.project_dir');
                    $contratPath = $projectDir . '/public/assets/img/contratMontessori.pdf';
                    $ficheInscriptionPath = $projectDir . '/public/assets/img/ficheInscription.pdf';
                    $ficheInformationPath = $projectDir . '/public/assets/img/ficheInformation.docx';

                    // Vérifier que les fichiers existent
                    if (file_exists($contratPath)) {
                        $email->attachFromPath($contratPath, 'Contrat_Montessori.pdf');
                    } else {
                        error_log('Fichier contrat non trouvé: ' . $contratPath);
                    }

                    if (file_exists($ficheInscriptionPath)) {
                        $email->attachFromPath($ficheInscriptionPath, 'Fiche_Inscription.pdf');
                    } else {
                        error_log('Fichier fiche inscription non trouvé: ' . $ficheInscriptionPath);
                    }

                    if (file_exists($ficheInformationPath)) {
                        $email->attachFromPath($ficheInformationPath, 'Fiche_Information.docx');
                    } else {
                        error_log('Fichier fiche information non trouvé: ' . $ficheInformationPath);
                    }

                    break;

                case Contact::STATUS_REJECTED:
                    // Possible depuis plusieurs états
                    if (!$contact->canReject()) {
                        $this->addFlash('error', 'Impossible de refuser ce contact dans son état actuel.');
                        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
                    }

                    if (empty($rejectionReason)) {
                        $this->addFlash('error', 'Le motif de refus est obligatoire.');
                        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
                    }

                    // Définir le sujet selon l'état actuel
                    if ($contact->isPending()) {
                        $subject = 'Candidature - ' . $contact->getChildName();
                    } elseif ($contact->hasAppointmentScheduled() || $contact->isConfirmed()) {
                        $subject = 'Annulation du rendez-vous - ' . $contact->getChildName();
                    } else {
                        $subject = 'Suite à votre visite - ' . $contact->getChildName();
                    }

                    $contact->setRejectionReason($rejectionReason);
                    $contact->setCustomMessage($customMessage);
                    $newStatus = Contact::STATUS_REJECTED;

                    $emailBody = $this->twig->render('emails/rejection.html.twig', [
                        'contact' => $contact,
                        'rejectionReason' => $rejectionReason,
                        'customMessage' => $customMessage,
                        'isAfterVisit' => $contact->isAfterVisit()
                    ]);
                    break;

                default:
                    $this->addFlash('error', 'Type de réponse non valide.');
                    return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
            }

            // Vérifier que le corps de l'email n'est pas vide
            if (empty($emailBody)) {
                throw new \Exception('Le template email n\'a pas pu être généré.');
            }

            $email->subject($subject)->html($emailBody);

            // Debug: Log avant envoi email
            error_log("Tentative d'envoi email avec sujet: " . $subject);

            // Essayer d'envoyer l'email
            $this->mailer->send($email);

            // Debug: Email envoyé avec succès
            error_log("Email envoyé avec succès");

            // Mettre à jour le statut du contact seulement si l'email a été envoyé avec succès
            if ($newStatus) {
                $contact->setStatus($newStatus);
                $contact->setResponseDate(new \DateTimeImmutable());

                // Debug: Log du nouveau statut
                error_log("Nouveau statut défini: " . $newStatus);

                $this->entityManager->flush();
                $this->entityManager->refresh($contact);

                // Debug: Vérification après flush
                error_log("Status après flush: " . $contact->getStatus());
            }

            $this->addFlash('success', 'Email envoyé avec succès ! Statut mis à jour vers: ' . $newStatus);
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : Configuration du serveur mail incorrecte.');
            error_log('Mailer error: ' . $e->getMessage());
        } catch (\Twig\Error\Error $e) {
            $this->addFlash('error', 'Erreur dans le template email : ' . $e->getMessage());
            error_log('Twig error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
            error_log('General error: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
    }

    #[Route('/{id}/process', name: 'app_admin_contact_process', methods: ['POST'])]
    public function process(Contact $contact): Response
    {
        // Vérifier que le contact peut être traité
        if (!$contact->canProcess()) {
            $this->addFlash('error', 'Ce contact ne peut pas être marqué comme traité dans son état actuel.');
            return $this->redirectToRoute('app_admin_contact_show', ['id' => $contact->getId()]);
        }

        try {
            // Marquer comme traité
            $contact->setStatus(Contact::STATUS_PROCESSED);
            $contact->setResponseDate(new \DateTimeImmutable());

            $this->entityManager->flush();

            $this->addFlash('success', 'Contact marqué comme traité avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la mise à jour du statut : ' . $e->getMessage());
            error_log('Process error: ' . $e->getMessage());
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

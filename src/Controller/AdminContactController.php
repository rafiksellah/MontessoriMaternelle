<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/admin_contact')]
class AdminContactController extends AbstractController
{
    public function __construct(
        private ContactRepository $contactRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer
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
        $response = $request->request->get('response'); // 'accepted' ou 'rejected'
        $message = $request->request->get('message', '');

        try {
            $email = (new Email())
                ->from('admin@votre-entreprise.com')
                ->to($contact->getEmail())
                ->subject($response === 'accepted' ? 'Candidature acceptée' : 'Candidature refusée');

            if ($response === 'accepted') {
                $emailBody = "Bonjour {$contact->getParentName()},\n\n";
                $emailBody .= "Nous avons le plaisir de vous informer que votre candidature pour {$contact->getChildName()} a été acceptée.\n\n";
                $emailBody .= $message ? "Message personnalisé : {$message}\n\n" : "";
                $emailBody .= "Nous vous contacterons prochainement pour les prochaines étapes.\n\n";
                $emailBody .= "Cordialement,\nL'équipe de recrutement";
            } else {
                $emailBody = "Bonjour {$contact->getParentName()},\n\n";
                $emailBody .= "Nous vous remercions pour votre candidature concernant {$contact->getChildName()}.\n\n";
                $emailBody .= "Malheureusement, nous ne pouvons pas donner suite à votre demande pour le moment.\n\n";
                $emailBody .= $message ? "Message personnalisé : {$message}\n\n" : "";
                $emailBody .= "Nous vous remercions de l'intérêt que vous portez à notre établissement.\n\n";
                $emailBody .= "Cordialement,\nL'équipe de recrutement";
            }

            $email->text($emailBody);
            $this->mailer->send($email);

            // Marquer le contact comme traité
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

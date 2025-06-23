<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Guest;
use DateTimeImmutable;
use App\Entity\Contact;
use App\Entity\EventRegistration;
use App\Repository\UserRepository;
use App\Repository\GuestRepository;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EventRegistrationRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin')]
    public function dashboard(
        UserRepository $userRepository,
        ContactRepository $contactRepository
    ): Response {
        // Récupération des statistiques générales
        $users = $userRepository->findAll();
        $total_users = count($users);

        $contacts = $contactRepository->findAll();
        $total_contacts = count($contacts);

        // Statistiques par statut des contacts
        $pending_contacts = count($contactRepository->findBy(['status' => Contact::STATUS_PENDING]));
        $processed_contacts = count($contactRepository->findBy(['status' => Contact::STATUS_INSCRIPTION_ACCEPTED]));
        $confirmed_contacts = count($contactRepository->findBy(['status' => Contact::STATUS_CONFIRMED]));
        $rejected_contacts = count($contactRepository->findBy(['status' => Contact::STATUS_REJECTED]));

        // Statistiques mensuelles
        $now = new DateTimeImmutable();
        $oneMonthAgo = $now->modify('-1 month');
        $twoMonthsAgo = $now->modify('-2 months');

        // Contacts du mois en cours
        $monthly_contacts = count($contactRepository->findContactsSince($oneMonthAgo));

        // Contacts du mois précédent (pour calculer l'évolution)
        $previous_monthly_contacts = count($contactRepository->findContactsBetween(
            $twoMonthsAgo,
            $oneMonthAgo
        ));

        // Calcul du pourcentage d'évolution des contacts
        $monthly_contacts_change = $previous_monthly_contacts > 0
            ? round((($monthly_contacts - $previous_monthly_contacts) / $previous_monthly_contacts) * 100)
            : 0;

        // Évolution des utilisateurs
        $monthly_users = count($userRepository->findUsersSince($oneMonthAgo));
        $previous_monthly_users = count($userRepository->findUsersBetween(
            $twoMonthsAgo,
            $oneMonthAgo
        ));
        $monthly_users_change = $previous_monthly_users > 0
            ? round((($monthly_users - $previous_monthly_users) / $previous_monthly_users) * 100)
            : 0;

        // Données pour le graphique d'évolution des contacts sur les 6 derniers mois
        $chart_months = [];
        $chart_contacts = [];

        for ($i = 5; $i >= 0; $i--) {
            $month_start = $now->modify("-$i month")->modify('first day of this month');
            $month_end = $now->modify("-$i month")->modify('last day of this month');

            $month_name = $month_start->format('M');
            $chart_months[] = $month_name;

            $month_contacts = count($contactRepository->findContactsBetween(
                $month_start,
                $month_end
            ));
            $chart_contacts[] = $month_contacts;
        }

        // Taux de conversion (pourcentage de contacts traités avec succès)
        $contact_success_rate = $total_contacts > 0
            ? round(($processed_contacts / $total_contacts) * 100)
            : 0;

        // Récupération des contacts récents
        $recent_contacts = $contactRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // Récupération des utilisateurs récents
        $recent_users = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // Statistiques sur les objectifs des contacts
        $objectives_stats = $contactRepository->getObjectivesStatistics();

        return $this->render('admin/index.html.twig', [
            'total_users' => $total_users,
            'total_contacts' => $total_contacts,
            'pending_contacts' => $pending_contacts,
            'processed_contacts' => $processed_contacts,
            'confirmed_contacts' => $confirmed_contacts,
            'rejected_contacts' => $rejected_contacts,
            'monthly_contacts' => $monthly_contacts,
            'monthly_contacts_change' => $monthly_contacts_change,
            'monthly_users_change' => $monthly_users_change,
            'chart_months' => $chart_months,
            'chart_contacts' => $chart_contacts,
            'contact_success_rate' => $contact_success_rate,
            'recent_contacts' => $recent_contacts,
            'recent_users' => $recent_users,
            'objectives_stats' => $objectives_stats,
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/users/{id}', name: 'app_user_show')]
    public function showUser(User $user): Response
    {
        return $this->render('admin/user_show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/event-registrations', name: 'app_event_registration_index')]
    public function eventRegistrations(EventRegistrationRepository $eventRegistrationRepository): Response
    {
        return $this->render('admin/event_registrations.html.twig', [
            'eventRegistrations' => $eventRegistrationRepository->findAll(),
        ]);
    }

    #[Route('/event-registrations/{id}', name: 'app_event_registration_show', methods: ['GET'])]
    public function showEventRegistration(EventRegistration $registration): Response
    {
        return $this->render('admin/event_registration_details.html.twig', [
            'registration' => $registration,
        ]);
    }

    #[Route('/event-registrations/{id}/edit', name: 'app_event_registration_edit', methods: ['GET', 'POST'])]
    public function editEventRegistration(Request $request, EventRegistration $registration, EntityManagerInterface $entityManager): Response
    {
        // Formulaire d'édition ici
        // ...

        return $this->render('admin/event_registration_edit.html.twig', [
            'registration' => $registration,
            // 'form' => $form->createView(),
        ]);
    }

    #[Route('/event-registrations/{id}', name: 'app_event_registration_delete', methods: ['POST'])]
    public function deleteEventRegistration(Request $request, EventRegistration $registration, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $registration->getId(), $request->request->get('_token'))) {
            $entityManager->remove($registration);
            $entityManager->flush();
            $this->addFlash('success', 'L\'inscription a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_event_registration_index');
    }

    #[Route('/guests/new/{registration_id}', name: 'app_guest_new', methods: ['GET', 'POST'])]
    public function newGuest(Request $request, $registration_id, EventRegistrationRepository $registrationRepository, EntityManagerInterface $entityManager): Response
    {
        $registration = $registrationRepository->find($registration_id);

        if (!$registration) {
            throw $this->createNotFoundException('L\'inscription n\'existe pas');
        }

        // Formulaire d'ajout d'invité ici
        // ...

        return $this->render('admin/guest_new.html.twig', [
            'registration' => $registration,
            // 'form' => $form->createView(),
        ]);
    }

    #[Route('/guests/{id}/edit', name: 'app_guest_edit', methods: ['GET', 'POST'])]
    public function editGuest(Request $request, Guest $guest, EntityManagerInterface $entityManager): Response
    {
        // Formulaire d'édition d'invité ici
        // ...

        return $this->render('admin/guest_edit.html.twig', [
            'guest' => $guest,
            // 'form' => $form->createView(),
        ]);
    }

    #[Route('/guests/{id}', name: 'app_guest_delete', methods: ['GET'])]
    public function deleteGuest(Request $request, Guest $guest, EntityManagerInterface $entityManager): Response
    {
        $registrationId = $guest->getEventRegistration()->getId();

        $entityManager->remove($guest);
        $entityManager->flush();

        $this->addFlash('success', 'L\'invité a été supprimé avec succès.');

        return $this->redirectToRoute('app_event_registration_show', ['id' => $registrationId]);
    }
}
<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\EventRegistration;
use App\Entity\Guest;
use App\Repository\UserRepository;
use App\Repository\EventRegistrationRepository;
use App\Repository\GuestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin')]
    public function dashboard(
        UserRepository $userRepository,
        EventRegistrationRepository $eventRegistrationRepository
    ): Response {
        return $this->render('admin/index.html.twig', [
            'total_users' => count($userRepository->findAll()),
            'total_registrations' => count($eventRegistrationRepository->findAll()),
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
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
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
        if ($this->isCsrfTokenValid('delete'.$registration->getId(), $request->request->get('_token'))) {
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
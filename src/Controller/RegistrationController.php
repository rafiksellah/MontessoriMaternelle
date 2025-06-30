<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register_no_locale')]
    #[Route('/{_locale}/register', name: 'app_register', methods: ['GET', 'POST'], requirements: ['_locale' => 'en|fr|ar'], defaults: ['_locale' => 'en'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Vérifier si l'email existe déjà
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    // Email déjà utilisé, afficher un message d'erreur
                    $this->addFlash('error', $translator->trans('registration.error.email_already_used'));
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form->createView(),
                    ]);
                }

                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                // Set default role
                $user->setRoles(['ROLE_USER']);

                $entityManager->persist($user);
                $entityManager->flush();

                // Flash message
                $this->addFlash('success', $translator->trans('registration.success.account_created'));

                // Redirect to login page avec le paramètre _locale
                return $this->redirectToRoute('app_login', [
                    '_locale' => $request->getLocale()
                ]);
            } catch (\Exception $e) {
                // Log l'erreur en arrière-plan (pour les administrateurs)
                // Pour le mode production, ne pas exposer les détails techniques
                if ($this->getParameter('kernel.environment') === 'dev') {
                    $this->addFlash('error', 'Erreur: ' . $e->getMessage());
                } else {
                    $this->addFlash('error', $translator->trans('registration.error.general_error'));
                }

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}

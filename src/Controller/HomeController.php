<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactFormType;
use App\Repository\ContactRepository;
use App\Service\ContactEmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'fr|en|ar'], defaults: ['_locale' => 'fr'])]
    public function index(
        Request $request,
        ContactRepository $contactRepository,
        ContactEmailService $emailService
    ): Response {
        $contact = new Contact();
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);
        
        $form_success = false;
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération des données du formulaire
            $formData = $form->getData();
            
            // Création d'une nouvelle entité Contact
            $contact
                ->setParentName($formData['parentName'])
                ->setChildName($formData['childName'])
                ->setChildBirthDate($formData['childBirthDate'])
                ->setPhoneNumber($formData['phoneNumber'])
                ->setEmail($formData['email'])
                ->setObjective($formData['objective'])
                ->setHeardAboutUs($formData['heardAboutUs'])
                ->setExpectations($formData['expectations'] ?? null);
            
            // Sauvegarde en base de données
            $contactRepository->save($contact, true);
            
            // Envoi des emails
            $emailService->sendConfirmationEmail($contact);
            $emailService->sendAdminNotificationEmail($contact);
            
            // Message flash de succès
            $this->addFlash('success', $this->getTranslator()->trans('contact.success_message'));
            
            // Redirection sur la même page avec un drapeau pour afficher un message de succès
            $form_success = true;
            
            // Création d'un nouveau formulaire vide
            $form = $this->createForm(ContactFormType::class);
        }
        
        return $this->render('home/index.html.twig', [
            'contact_form' => $form->createView(),
            'form_success' => $form_success,
        ]);
    }
    
    #[Route('/{_locale}/recrutement', name: 'app_recrutement', requirements: ['_locale' => 'fr|en|ar'], defaults: ['_locale' => 'fr'])]
    public function recrutement(): Response
    {
        
        return $this->render('home/recrutement.html.twig', []);
    }
}
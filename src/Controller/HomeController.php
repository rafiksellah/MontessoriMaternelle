<?php

namespace App\Controller;

use App\Entity\Contact;
use Psr\Log\LoggerInterface;
use App\Form\ContactFormType;
use App\Entity\JobApplication;
use App\Form\JobApplicationType;
use Symfony\Component\Mime\Email;
use App\Service\ContactEmailService;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class HomeController extends AbstractController
{
    private $translator;


    public function __construct(
        TranslatorInterface $translator,
    ) {
        $this->translator = $translator;
    }

    #[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'fr|en|ar'], defaults: ['_locale' => 'fr'])]
    public function index(
        Request $request,
        ContactRepository $contactRepository,
        ContactEmailService $emailService
    ): Response {
        $contact = new Contact();

        // Créer le formulaire avec l'injection de dépendances correcte
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
                ->setHeardAboutUs("Non spécifié / Not specified") // Default value
                ->setExpectations($formData['expectations'])
                ->setCreatedAt(new \DateTimeImmutable()); // Set the current date/time

            // Sauvegarde en base de données
            $contactRepository->save($contact, true);

            // Envoi des emails
            $emailService->sendConfirmationEmail($contact);
            $emailService->sendAdminNotificationEmail($contact);

            // Message flash de succès
            $this->addFlash('success', $this->translator->trans('contact.success_message'));

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
    public function recrutement(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        SluggerInterface $slugger,
        LoggerInterface $logger,
        TranslatorInterface $translator
    ): Response {
        $jobApplication = new JobApplication();
        $form = $this->createForm(JobApplicationType::class, $jobApplication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Gérer l'upload des fichiers
                $cvFile = $form->get('cvFilename')->getData();
                $motivationFile = $form->get('motivationFileName')->getData();

                if ($cvFile) {
                    $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $cvFile->guessExtension();

                    try {
                        $cvFile->move(
                            $this->getParameter('uploads_directory') ?? $this->getParameter('kernel.project_dir') . '/public/uploads',
                            $newFilename
                        );
                        $jobApplication->setCvFilename($newFilename);
                    } catch (FileException $e) {
                        $logger->error('Erreur lors de l\'upload du CV: ' . $e->getMessage());
                        throw new \Exception($translator->trans('job_application.messages.cv_upload_error'));
                    }
                }

                if ($motivationFile) {
                    $originalFilename = pathinfo($motivationFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $motivationFile->guessExtension();

                    try {
                        $motivationFile->move(
                            $this->getParameter('uploads_directory') ?? $this->getParameter('kernel.project_dir') . '/public/uploads',
                            $newFilename
                        );
                        $jobApplication->setMotivationFilename($newFilename);
                    } catch (FileException $e) {
                        $logger->error('Erreur lors de l\'upload de la lettre de motivation: ' . $e->getMessage());
                        throw new \Exception($translator->trans('job_application.messages.motivation_file_upload_error'));
                    }
                }

                // Sauvegarder l'application
                $em->persist($jobApplication);
                $em->flush();

                // Envoyer un email de confirmation
                $this->sendConfirmationEmail($mailer, $jobApplication, $translator);

                // Réponse pour AJAX
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'message' => $translator->trans('job_application.messages.success')
                    ]);
                }

                $this->addFlash('success', $translator->trans('job_application.messages.success'));
                return $this->redirectToRoute('app_recrutement');
            } catch (\Exception $e) {
                $logger->error('Erreur lors de l\'envoi de la candidature: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => $translator->trans('job_application.messages.error_details', ['details' => $e->getMessage()])
                    ], 500);
                }

                $this->addFlash('error', $translator->trans('job_application.messages.error'));
            }
        }

        // Retourner les erreurs pour AJAX (quand le formulaire n'est pas valide)
        if ($request->isXmlHttpRequest() && $form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            $fieldErrors = [];

            // Récupérer toutes les erreurs
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            // Récupérer les erreurs par champ
            foreach ($form->all() as $fieldName => $field) {
                if (!$field->isValid()) {
                    $fieldErrors[$fieldName] = [];
                    foreach ($field->getErrors() as $error) {
                        $fieldErrors[$fieldName][] = $error->getMessage();
                    }
                }
            }

            // Gestion spéciale pour les collections (langues)
            if (isset($form['langues']) && !$form['langues']->isValid()) {
                foreach ($form['langues']->all() as $index => $langueForm) {
                    if (!$langueForm->isValid()) {
                        foreach ($langueForm->all() as $langueField => $langueFieldForm) {
                            if (!$langueFieldForm->isValid()) {
                                $fieldName = "langues_${index}_${langueField}";
                                $fieldErrors[$fieldName] = [];
                                foreach ($langueFieldForm->getErrors() as $error) {
                                    $fieldErrors[$fieldName][] = $error->getMessage();
                                }
                            }
                        }
                    }
                }
            }

            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans('job_application.messages.form_errors'),
                'errors' => $errors,
                'fieldErrors' => $fieldErrors
            ], 400);
        }

        return $this->render('home/recrutement.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function sendConfirmationEmail(MailerInterface $mailer, JobApplication $jobApplication, TranslatorInterface $translator): void
    {
        try {
            // Email au candidat avec traduction
            $candidateEmail = (new Email())
                ->from('recrutement@montessorialgerie.mia-dz.com')
                ->to($jobApplication->getEmail())
                ->subject($translator->trans('job_application.email.candidate.subject'))
                ->html($this->renderView('emails/job_application_confirmation.html.twig', [
                    'application' => $jobApplication,
                    'translator' => $translator
                ]));

            $mailer->send($candidateEmail);

            // Email à l'équipe RH avec traduction
            $hrEmail = (new Email())
                ->from('recrutement@montessorialgerie.mia-dz.com')
                ->to('recrutement@montessorialgerie.mia-dz.com')
                ->subject($translator->trans('job_application.email.hr.subject', ['position' => $jobApplication->getPosteSouhaite()]))
                ->html($this->renderView('emails/job_application_notification.html.twig', [
                    'application' => $jobApplication,
                    'translator' => $translator
                ]));

            $mailer->send($hrEmail);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer toute la candidature
            error_log($translator->trans('job_application.messages.email_error') . ': ' . $e->getMessage());
        }
    }
}

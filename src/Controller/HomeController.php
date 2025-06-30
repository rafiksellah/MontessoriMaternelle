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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Form\FormError;

class HomeController extends AbstractController
{
    private $translator;


    public function __construct(
        TranslatorInterface $translator,
    ) {
        $this->translator = $translator;
    }

    #[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'en|fr|ar'], defaults: ['_locale' => 'en'])]
    public function index(
        Request $request,
        ContactRepository $contactRepository,
        ContactEmailService $emailService
    ): Response {
        $contact = new Contact();

        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        $form_success = false;
        $form_error = false;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $formData = $form->getData();
                    $blockedNames = ['RobertAnowl', 'RobertAnowI']; // Ajustez selon vos données
                    $blockedDomains = ['registry.godaddy'];

                    if (
                        in_array($formData['parentName'], $blockedNames) ||
                        strpos($formData['email'], 'registry.godaddy') !== false
                    ) {
                        $form->addError(new FormError('Inscription temporairement indisponible.'));
                        $form_error = true;
                        return $this->render('home/index.html.twig', [
                            'contact_form' => $form->createView(),
                            'form_success' => false,
                            'form_error' => true,
                        ]);
                    }

                    // NOUVELLE VÉRIFICATION ANTI-SPAM
                    $suspiciousActivity = $this->checkForSuspiciousActivity(
                        $contactRepository,
                        $formData['parentName'],
                        $formData['email'],
                        $request->getClientIp()
                    );

                    if ($suspiciousActivity['blocked']) {
                        $form->addError(new FormError(
                            $this->translator->trans('contact.errors.suspicious_activity')
                        ));
                        $form_error = true;

                        // Log l'activité suspecte
                        $this->logger->warning('Suspicious registration activity detected', [
                            'name' => $formData['parentName'],
                            'email' => $formData['email'],
                            'ip' => $request->getClientIp(),
                            'reason' => $suspiciousActivity['reason']
                        ]);

                        return $this->render('home/index.html.twig', [
                            'contact_form' => $form->createView(),
                            'form_success' => $form_success,
                            'form_error' => $form_error,
                        ]);
                    }

                    // Vérifier si l'email existe déjà
                    $existingContact = $contactRepository->findOneBy(['email' => $formData['email']]);

                    if ($existingContact) {
                        $form->get('email')->addError(new FormError(
                            $this->translator->trans('contact.errors.email_already_exists')
                        ));
                        $form_error = true;
                    } else {
                        // Création et sauvegarde du contact
                        $contact
                            ->setParentName($formData['parentName'])
                            ->setChildName($formData['childName'])
                            ->setChildBirthDate($formData['childBirthDate'])
                            ->setPhoneNumber($formData['phoneNumber'])
                            ->setEmail($formData['email'])
                            ->setObjective($formData['objective'])
                            ->setHeardAboutUs("Non spécifié / Not specified")
                            ->setExpectations($formData['expectations'])
                            ->setCreatedAt(new \DateTimeImmutable())
                            ->setIpAddress($request->getClientIp()); // Ajouter l'IP

                        $contactRepository->save($contact, true);

                        $emailService->sendConfirmationEmail($contact);
                        $emailService->sendAdminNotificationEmail($contact);

                        $this->addFlash('success', $this->translator->trans('contact.success_message'));
                        $form_success = true;
                        $form = $this->createForm(ContactFormType::class);
                    }
                } catch (UniqueConstraintViolationException $e) {
                    $form->get('email')->addError(new FormError(
                        $this->translator->trans('contact.errors.email_already_exists')
                    ));
                    $form_error = true;
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans('contact.errors.general_error'));
                    $form_error = true;
                }
            } else {
                $form_error = true;
            }
        }

        return $this->render('home/index.html.twig', [
            'contact_form' => $form->createView(),
            'form_success' => $form_success,
            'form_error' => $form_error,
        ]);
    }

    #[Route('/{_locale}/recrutement', name: 'app_recrutement', requirements: ['_locale' => 'en|fr|ar'], defaults: ['_locale' => 'en'])]
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


        $jobConfig = [
            'title' => $translator->trans('job.title'),
            'subtitle' => $translator->trans('job.subtitle'),
            'team' => [
                'education' => [
                    'title' => $translator->trans('job.team.education.title'),
                    'desc' => $translator->trans('job.team.education.desc'),
                    'roles' => $translator->trans('job.team.education.roles'),
                ],
                'admin' => [
                    'title' => $translator->trans('job.team.admin.title'),
                    'desc' => $translator->trans('job.team.admin.desc'),
                    'roles' => $translator->trans('job.team.admin.roles'),
                ],
                'hse' => [
                    'title' => $translator->trans('job.team.hse.title'),
                    'desc' => $translator->trans('job.team.hse.desc'),
                    'roles' => $translator->trans('job.team.hse.roles'),
                ],
                'extras' => [
                    'title' => $translator->trans('job.team.extras.title'),
                    'desc' => $translator->trans('job.team.extras.desc'),
                    'roles' => $translator->trans('job.team.extras.roles'),
                ],
                'health' => [
                    'title' => $translator->trans('job.team.health.title'),
                    'desc' => $translator->trans('job.team.health.desc'),
                    'roles' => $translator->trans('job.team.health.roles'),
                ],
                'marketing' => [
                    'title' => $translator->trans('job.team.marketing.title'),
                    'desc' => $translator->trans('job.team.marketing.desc'),
                    'roles' => $translator->trans('job.team.marketing.roles'),
                ],
            ],
            'cta' => [
                'title' => $translator->trans('job.cta.title'),
                'button' => $translator->trans('job.cta.button'),
            ],
        ];

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
                                $fieldName = "langues_{$index}_{$langueField}";
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
            'job' => $jobConfig
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

    private function checkForSuspiciousActivity(
        ContactRepository $contactRepository,
        string $parentName,
        string $email,
        ?string $ip
    ): array {
        $now = new \DateTimeImmutable();
        $oneHourAgo = $now->modify('-1 hour');
        $oneDayAgo = $now->modify('-24 hours');

        // Vérifier les inscriptions récentes avec le même nom
        $recentSameNameCount = $contactRepository->countRecentByName($parentName, $oneDayAgo);

        // Vérifier les inscriptions récentes depuis la même IP
        $recentSameIpCount = $contactRepository->countRecentByIp($ip, $oneHourAgo);

        // Vérifier si c'est un email temporaire/suspect
        $suspiciousEmailDomains = [
            'registry.godaddy',
            '10minutemail.com',
            'tempmail.org',
            'guerrillamail.com',
            'mailinator.com'
        ];

        $emailDomain = substr(strrchr($email, "@"), 1);
        $isSuspiciousEmailDomain = false;
        foreach ($suspiciousEmailDomains as $domain) {
            if (strpos($emailDomain, $domain) !== false) {
                $isSuspiciousEmailDomain = true;
                break;
            }
        }

        // Règles de détection
        if ($recentSameNameCount >= 5) {
            return [
                'blocked' => true,
                'reason' => 'Too many registrations with same name in 24h'
            ];
        }

        if ($recentSameIpCount >= 3) {
            return [
                'blocked' => true,
                'reason' => 'Too many registrations from same IP in 1h'
            ];
        }

        if ($isSuspiciousEmailDomain) {
            return [
                'blocked' => true,
                'reason' => 'Suspicious email domain'
            ];
        }

        // Vérifier si le nom contient des patterns suspects
        if (
            preg_match('/^[A-Za-z]+[0-9]+$/', $parentName) ||
            strlen($parentName) < 3 ||
            preg_match('/^(test|spam|fake)/i', $parentName)
        ) {
            return [
                'blocked' => true,
                'reason' => 'Suspicious name pattern'
            ];
        }

        return ['blocked' => false, 'reason' => null];
    }
}

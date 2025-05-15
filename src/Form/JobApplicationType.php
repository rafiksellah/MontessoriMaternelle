<?php

namespace App\Form;

use App\Entity\JobApplication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobApplicationType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Informations personnelles
            ->add('nom', TextType::class, [
                'label' => $this->translator->trans('job_application.nom'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('job_application.placeholders.nom')
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => $this->translator->trans('job_application.prenom'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('job_application.placeholders.prenom')
                ]
            ])
            ->add('dateNaissance', BirthdayType::class, [
                'label' => $this->translator->trans('job_application.date_naissance'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTime('yesterday'))->format('Y-m-d')
                ]
            ])
            ->add('lieuResidence', TextType::class, [
                'label' => $this->translator->trans('job_application.lieu_residence'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('job_application.placeholders.lieu_residence')
                ]
            ])
            ->add('nationalite', TextType::class, [
                'label' => $this->translator->trans('job_application.nationalite'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('job_application.placeholders.nationalite')
                ]
            ])
            ->add('permisTravail', CheckboxType::class, [
                'label' => $this->translator->trans('job_application.permis_travail'),
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('telephone', TelType::class, [
                'label' => $this->translator->trans('job_application.telephone'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('job_application.placeholders.telephone')
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('job_application.email'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('job_application.placeholders.email')
                ]
            ])

            // Candidature
            ->add('posteSouhaite', ChoiceType::class, [
                'label' => $this->translator->trans('job_application.poste_souhaite'),
                'required' => true,
                'placeholder' => $this->translator->trans('job_application.jobs.select_position'),
                'choices' => $this->getJobChoices(),
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('raisonInteretPoste', TextareaType::class, [
                'label' => $this->translator->trans('job_application.raison_interet'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => $this->translator->trans('job_application.placeholders.raison_interet')
                ]
            ])
            ->add('cvFilename', FileType::class, [
                'label' => $this->translator->trans('job_application.cv_filename'),
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => $this->translator->trans('job_application.validation.invalid_file'),
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf',
                    'data-filename-target' => 'cv-filename'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ]
            ])
            ->add('motivationFileName', FileType::class, [
                'label' => $this->translator->trans('job_application.motivation_filename'),
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => $this->translator->trans('job_application.validation.invalid_file'),
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf',
                    'data-filename-target' => 'motivation-filename'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ]
            ])

            // Langues
            ->add('langues', CollectionType::class, [
                'entry_type' => LangueType::class,
                'entry_options' => ['label' => false],
                'allow_add' => false,
                'allow_delete' => false,
                'data' => [
                    ['nom' => $this->translator->trans('job_application.language_names.francais'), 'niveau' => ''],
                    ['nom' => $this->translator->trans('job_application.language_names.anglais'), 'niveau' => ''],
                    ['nom' => $this->translator->trans('job_application.language_names.arabe'), 'niveau' => ''],
                    ['nom' => $this->translator->trans('job_application.language_names.tamazight'), 'niveau' => '']
                ],
                'label' => $this->translator->trans('job_application.languages')
            ])

            // Motivation et vision
            ->add('motivationMMA', TextareaType::class, [
                'label' => $this->translator->trans('job_application.motivation_mma'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => $this->translator->trans('job_application.placeholders.motivation_mma')
                ]
            ])
            ->add('contributionMMA', TextareaType::class, [
                'label' => $this->translator->trans('job_application.contribution_mma'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => $this->translator->trans('job_application.placeholders.contribution_mma')
                ]
            ])
            ->add('disponibilite', ChoiceType::class, [
                'label' => $this->translator->trans('job_application.disponibilite'),
                'required' => true,
                'placeholder' => $this->translator->trans('job_application.disponibilite_options.select'),
                'choices' => $this->getDisponibiliteChoices(),
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('engagement', ChoiceType::class, [
                'label' => $this->translator->trans('job_application.engagement'),
                'required' => true,
                'placeholder' => $this->translator->trans('job_application.engagement_options.select'),
                'choices' => $this->getEngagementChoices(),
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => $this->translator->trans('job_application.submit'),
                'attr' => [
                    'class' => 'btn btn-primary btn-lg w-100'
                ]
            ]);
    }

    private function getJobChoices(): array
    {
        return [
            $this->translator->trans('job_application.jobs.categories.pedagogique') => [
                $this->translator->trans('job_application.jobs.positions.enseignant_anglais') => 'enseignant_anglais',
                $this->translator->trans('job_application.jobs.positions.enseignant_arabe') => 'enseignant_arabe',
                $this->translator->trans('job_application.jobs.positions.enseignant_francais') => 'enseignant_francais',
                $this->translator->trans('job_application.jobs.positions.enseignant_musique') => 'enseignant_musique',
            ],
            $this->translator->trans('job_application.jobs.categories.administrative') => [
                $this->translator->trans('job_application.jobs.positions.directeur_rh') => 'directeur_rh',
                $this->translator->trans('job_application.jobs.positions.office_manager') => 'office_manager',
            ],
            $this->translator->trans('job_application.jobs.categories.support') => [
                $this->translator->trans('job_application.jobs.positions.responsable_hse') => 'responsable_hse',
                $this->translator->trans('job_application.jobs.positions.cuisinier_chef') => 'cuisinier_chef',
                $this->translator->trans('job_application.jobs.positions.aide_cuisinier') => 'aide_cuisinier',
                $this->translator->trans('job_application.jobs.positions.personnel_entretien') => 'personnel_entretien',
                $this->translator->trans('job_application.jobs.positions.agent_accueil') => 'agent_accueil',
                $this->translator->trans('job_application.jobs.positions.factotum') => 'factotum',
            ],
            $this->translator->trans('job_application.jobs.categories.extrascolaire') => [
                $this->translator->trans('job_application.jobs.positions.directeur_activites') => 'directeur_activites',
                $this->translator->trans('job_application.jobs.positions.animateur') => 'animateur',
                $this->translator->trans('job_application.jobs.positions.expert_culture') => 'expert_culture',
            ],
            $this->translator->trans('job_application.jobs.categories.sante') => [
                $this->translator->trans('job_application.jobs.positions.medecin') => 'medecin',
                $this->translator->trans('job_application.jobs.positions.psychologue') => 'psychologue',
                $this->translator->trans('job_application.jobs.positions.orthophoniste') => 'orthophoniste',
            ],
            $this->translator->trans('job_application.jobs.categories.marketing') => [
                $this->translator->trans('job_application.jobs.positions.community_manager') => 'community_manager',
                $this->translator->trans('job_application.jobs.positions.marketing_digital') => 'marketing_digital',
                $this->translator->trans('job_application.jobs.positions.graphiste') => 'graphiste',
            ]
        ];
    }

    private function getDisponibiliteChoices(): array
    {
        return [
            $this->translator->trans('job_application.disponibilite_options.immediate') => 'immediate',
            $this->translator->trans('job_application.disponibilite_options.preavis_1_mois') => 'preavis_1_mois',
            $this->translator->trans('job_application.disponibilite_options.preavis_2_mois') => 'preavis_2_mois',
            $this->translator->trans('job_application.disponibilite_options.autre') => 'autre'
        ];
    }

    private function getEngagementChoices(): array
    {
        return [
            $this->translator->trans('job_application.engagement_options.moins_1_an') => 'moins_1_an',
            $this->translator->trans('job_application.engagement_options.1_2_ans') => '1_2_ans',
            $this->translator->trans('job_application.engagement_options.2_3_ans') => '2_3_ans',
            $this->translator->trans('job_application.engagement_options.5_ans_plus') => '5_ans_plus'
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobApplication::class,
            'attr' => ['class' => 'needs-validation', 'novalidate' => true]
        ]);
    }
}

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

class JobApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Informations personnelles
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom'
                ]
            ])
            ->add('dateNaissance', BirthdayType::class, [
                'label' => 'Date de naissance',
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
                'label' => 'Lieu de résidence',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ville, Pays'
                ]
            ])
            ->add('nationalite', TextType::class, [
                'label' => 'Nationalité',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nationalité'
                ]
            ])
            ->add('permisTravail', CheckboxType::class, [
                'label' => 'J\'ai un permis de travail',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+213 XX XX XX XX'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'votre.email@exemple.com'
                ]
            ])

            // Candidature
            ->add('posteSouhaite', ChoiceType::class, [
                'label' => 'Poste souhaité',
                'required' => true,
                'placeholder' => 'Choisissez un poste',
                'choices' => [
                    'Équipe Pédagogique' => [
                        'Enseignant d\'anglais' => 'enseignant_anglais',
                        'Enseignant d\'arabe' => 'enseignant_arabe',
                        'Enseignant Français' => 'enseignant_francais',
                        'Enseignant de musique' => 'enseignant_musique',
                    ],
                    'Équipe Management administrative' => [
                        'Directeur administratif et RH' => 'directeur_rh',
                        'Office manager' => 'office_manager',
                    ],
                    'Équipe Support' => [
                        'Responsable HSE' => 'responsable_hse',
                        'Cuisinier/Chef' => 'cuisinier_chef',
                        'Aide-cuisinier' => 'aide_cuisinier',
                        'Personnel d\'entretien et d\'hygiène' => 'personnel_entretien',
                        'Agent d\'accueil' => 'agent_accueil',
                        'Factotum' => 'factotum',
                    ],
                    'Équipe Activité Extrascolaire' => [
                        'Directeur des activités extrascolaires' => 'directeur_activites',
                        'Animateurs culture, sports, sciences et nature' => 'animateur',
                        'Experts/passionnés de culture et histoire algérienne' => 'expert_culture',
                    ],
                    'Équipe Santé' => [
                        'Médecin' => 'medecin',
                        'Psychologue' => 'psychologue',
                        'Orthophoniste' => 'orthophoniste',
                    ],
                    'Équipe Marketing' => [
                        'Gestionnaire de communauté' => 'community_manager',
                        'Spécialiste du marketing numérique' => 'marketing_digital',
                        'Graphiste' => 'graphiste',
                    ]
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('raisonInteretPoste', TextareaType::class, [
                'label' => 'Pourquoi ce poste vous attire-t-il particulièrement ?',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Expliquez en 2-3 phrases ce qui vous attire dans ce poste...'
                ]
            ])
            ->add('cvFilename', FileType::class, [
                'label' => 'Joindre votre CV (PDF recommandé)',
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf',
                    'data-filename-target' => 'cv-filename' // Add this attribute
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ]
            ])
            ->add('motivationFileName', FileType::class, [
                'label' => 'Joindre votre lettre de motivation (optionnelle)',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf',
                    'data-filename-target' => 'motivation-filename' // Add this attribute
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
                    ['nom' => 'Français', 'niveau' => ''],
                    ['nom' => 'Anglais', 'niveau' => ''],
                    ['nom' => 'Arabe', 'niveau' => ''],
                    ['nom' => 'Tamazight', 'niveau' => '']
                ],
                'label' => 'Langues parlées et niveau de maîtrise'
            ])

            // Motivation et vision
            ->add('motivationMMA', TextareaType::class, [
                'label' => 'Qu\'est-ce qui vous motive à postuler chez MMA ?',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Expliquez votre motivation à rejoindre Montessori Algérie...'
                ]
            ])
            ->add('contributionMMA', TextareaType::class, [
                'label' => 'Quelle contribution souhaitez-vous apporter à MMA ?',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Décrivez la contribution que vous souhaitez apporter...'
                ]
            ])
            ->add('disponibilite', ChoiceType::class, [
                'label' => 'Disponibilité',
                'required' => true,
                'placeholder' => 'Sélectionnez votre disponibilité',
                'choices' => [
                    'Immédiate' => 'immediate',
                    'Préavis de 1 mois' => 'preavis_1_mois',
                    'Préavis de 2 mois' => 'preavis_2_mois',
                    'Autre (à préciser)' => 'autre'
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('engagement', ChoiceType::class, [
                'label' => 'Engagement souhaité avec Montessori Algérie',
                'required' => true,
                'placeholder' => 'Sélectionnez votre engagement souhaité',
                'choices' => [
                    'Moins d\'1 an' => 'moins_1_an',
                    '1 à 2 ans' => '1_2_ans',
                    '2 à 3 ans' => '2_3_ans',
                    '5 ans et plus' => '5_ans_plus'
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer ma candidature',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg w-100'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobApplication::class,
            'attr' => ['class' => 'needs-validation', 'novalidate' => true]
        ]);
    }
}

<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\IsTrue;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('parentName', TextType::class, [
                'label' => 'Nom du parent / Parent\'s Name *',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le nom du parent',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                    ]),
                ],
            ])
            ->add('childName', TextType::class, [
                'label' => 'Nom de l\'enfant / Child\'s Name *',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le nom de l\'enfant',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                    ]),
                ],
            ])
            ->add('childBirthDate', DateType::class, [
                'label' => 'Date de naissance de l\'enfant / Child\'s date of birth *',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer la date de naissance de l\'enfant',
                    ]),
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Numéro de téléphone / Phone Number *',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre numéro de téléphone',
                    ]),
                    new Length([
                        'min' => 8,
                        'max' => 20,
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Courrier électronique / Email *',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre adresse email',
                    ]),
                    new Email([
                        'message' => 'L\'adresse email {{ value }} n\'est pas valide',
                    ]),
                ],
            ])
            ->add('objective', ChoiceType::class, [
                'label' => 'Objectif / Objective *',
                'choices' => [
                    'Demande de visite / Visit Request' => 'visit_request',
                    'Demande d\'inscription / Enrollment Request' => 'enrollment_request',
                    'Demande d\'information / Request information' => 'information_request',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un objectif',
                    ]),
                ],
            ])
            ->add('heardAboutUs', TextType::class, [
                'label' => 'Comment avez-vous entendu parler de nous ? / How did you hear about us? *',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez nous indiquer comment vous avez entendu parler de nous',
                    ]),
                ],
            ])
            ->add('expectations', TextareaType::class, [
                'label' => 'Parlez-nous de vous et de vos attentes pour l\'éducation de votre enfant / Tell us about yourself and what you\'re looking for in your child\'s education',
                'required' => false,
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte que mes données soient utilisées pour me contacter à propos de ma demande / I agree that my data will be used to contact me about my request',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions pour continuer',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => null,
        ]);
    }
}

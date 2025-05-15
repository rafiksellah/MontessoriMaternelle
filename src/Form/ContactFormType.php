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
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactFormType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('parentName', TextType::class, [
                'label' => $this->translator->trans('contact.parent_name') . ' *',
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('contact.errors.parent_name_required'),
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => $this->translator->trans('contact.errors.parent_name_min'),
                        'maxMessage' => $this->translator->trans('contact.errors.parent_name_max'),
                    ]),
                ],
            ])
            ->add('childName', TextType::class, [
                'label' => $this->translator->trans('contact.child_name') . ' *',
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('contact.errors.child_name_required'),
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => $this->translator->trans('contact.errors.child_name_min'),
                        'maxMessage' => $this->translator->trans('contact.errors.child_name_max'),
                    ]),
                ],
            ])
            ->add('childBirthDate', DateType::class, [
                'label' => $this->translator->trans('contact.child_birth_date') . ' *',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('contact.errors.child_birth_date_required'),
                    ]),
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => $this->translator->trans('contact.phone') . ' *',
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('contact.errors.phone_required'),
                    ]),
                    new Length([
                        'min' => 8,
                        'max' => 20,
                        'minMessage' => $this->translator->trans('contact.errors.phone_min'),
                        'maxMessage' => $this->translator->trans('contact.errors.phone_max'),
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('contact.email') . ' *',
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('contact.errors.email_required'),
                    ]),
                    new Email([
                        'message' => $this->translator->trans('contact.errors.email_invalid'),
                    ]),
                ],
            ])
            ->add('objective', ChoiceType::class, [
                'label' => $this->translator->trans('contact.objective') . ' *',
                'choices' => [
                    $this->translator->trans('contact.choices.visit_request') => 'visit_request',
                    $this->translator->trans('contact.choices.enrollment_request') => 'enrollment_request',
                    $this->translator->trans('contact.choices.information_request') => 'information_request',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('contact.errors.objective_required'),
                    ]),
                ],
            ])
            ->add('expectations', TextareaType::class, [
                'label' => $this->translator->trans('contact.expectations') . ' *',
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('contact.errors.expectations_required'),
                    ]),
                ],
                'attr' => [
                    'rows' => 5
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}

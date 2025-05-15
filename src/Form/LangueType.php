<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class LangueType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', HiddenType::class)
            ->add('niveau', ChoiceType::class, [
                'label' => $this->translator->trans('job_application.niveau_maitrise'),
                'required' => false,
                'placeholder' => $this->translator->trans('job_application.language_levels.select'),
                'choices' => [
                    $this->translator->trans('job_application.language_levels.debutant') => 'debutant',
                    $this->translator->trans('job_application.language_levels.intermediaire') => 'intermediaire',
                    $this->translator->trans('job_application.language_levels.avance') => 'avance',
                    $this->translator->trans('job_application.language_levels.natif') => 'natif',
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}

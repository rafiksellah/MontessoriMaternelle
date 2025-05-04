<?php

namespace App\Form;

use App\Entity\EventRegistration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventRegistrationType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => $this->translator->trans('event.form.firstName'),
                'disabled' => true,
                'attr' => ['readonly' => true, 'class' => 'form-control-plaintext']
            ])
            ->add('lastName', TextType::class, [
                'label' => $this->translator->trans('event.form.lastName'),
                'disabled' => true,
                'attr' => ['readonly' => true, 'class' => 'form-control-plaintext']
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('event.form.email'),
                'disabled' => true,
                'attr' => ['readonly' => true, 'class' => 'form-control-plaintext']
            ])
            ->add('phone', TelType::class, [
                'label' => $this->translator->trans('event.form.phone'),
                'disabled' => true,
                'attr' => ['readonly' => true, 'class' => 'form-control-plaintext']
            ])
            ->add('guests', CollectionType::class, [
                'entry_type' => GuestType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventRegistration::class,
            'translation_domain' => 'messages'
        ]);
    }
}
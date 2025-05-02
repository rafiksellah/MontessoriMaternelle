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

class EventRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Nom',
                'disabled' => true,
                'attr' => ['readonly' => true, 'class' => 'form-control-plaintext']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Prénom',
                'disabled' => true,
                'attr' => ['readonly' => true, 'class' => 'form-control-plaintext']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'disabled' => true,
                'attr' => ['readonly' => true, 'class' => 'form-control-plaintext']
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
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
        ]);
    }
}
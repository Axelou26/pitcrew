<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EducationEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('degree', TextType::class, [
                'label' => 'Diplôme / Certification',
                'required' => true,
            ])
            ->add('institution', TextType::class, [
                'label' => 'Établissement / Institution',
                'required' => true,
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('startDate', TextType::class, [
                'label' => 'Date de début (MM/AAAA)',
                'required' => true,
            ])
            ->add('endDate', TextType::class, [
                'label' => 'Date de fin (MM/AAAA) ou "present" si en cours',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de la formation',
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
} 
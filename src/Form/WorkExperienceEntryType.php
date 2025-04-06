<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkExperienceEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du poste',
                'required' => true,
            ])
            ->add('company', TextType::class, [
                'label' => 'Entreprise / Organisation',
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
                'label' => 'Description des responsabilités et accomplissements',
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
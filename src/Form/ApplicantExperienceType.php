<?php

namespace App\Form;

use App\Entity\Applicant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicantExperienceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jobTitle', TextType::class, [
                'label' => 'Titre de poste actuel',
                'required' => false,
            ])
            ->add('experience', TextareaType::class, [
                'label' => 'Expérience professionnelle',
                'required' => false,
                'attr' => ['rows' => 6],
                'help' => 'Décrivez votre parcours professionnel, vos postes occupés et vos responsabilités.',
            ])
            ->add('education', TextareaType::class, [
                'label' => 'Formation',
                'required' => false,
                'attr' => ['rows' => 4],
                'help' => 'Indiquez vos diplômes, formations et certifications.',
            ])
            ->add('workExperience', CollectionType::class, [
                'label' => 'Détails de l\'expérience professionnelle',
                'entry_type' => WorkExperienceEntryType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
                'attr' => [
                    'class' => 'work-experience-collection',
                    'data-collection-holder' => 'work-experience',
                ],
                'by_reference' => false,
            ])
            ->add('educationHistory', CollectionType::class, [
                'label' => 'Détails des formations',
                'entry_type' => EducationEntryType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
                'attr' => [
                    'class' => 'education-history-collection',
                    'data-collection-holder' => 'education-history',
                ],
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Applicant::class,
        ]);
    }
}

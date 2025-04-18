<?php

namespace App\Form;

use App\Entity\JobOffer;
use App\Form\Type\JobOfferTypeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

class JobOfferType extends AbstractType implements JobOfferTypeInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du poste',
                'attr' => ['placeholder' => 'Ex: Mécanicien F1'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Le titre du poste est obligatoire'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du poste',
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Décrivez le poste, les responsabilités, et les exigences',
                ],
            ])
            ->add('company', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'attr' => ['placeholder' => 'Ex: Mercedes F1 Team'],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo de l\'entreprise',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/svg+xml',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, SVG)',
                    ])
                ],
                'attr' => ['class' => 'form-control'],
                'help' => 'Image au format JPEG, PNG ou SVG (max 2 Mo)',
            ])
            ->add('contractType', ChoiceType::class, [
                'label' => 'Type de contrat',
                'choices' => [
                    'CDI' => 'CDI',
                    'CDD' => 'CDD',
                    'Freelance' => 'Freelance',
                    'Stage' => 'Stage',
                    'Alternance' => 'Alternance',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Localisation',
                'attr' => ['placeholder' => 'Ex: Paris, France'],
            ])
            ->add('isRemote', CheckboxType::class, [
                'label' => 'Télétravail possible',
                'required' => false,
            ])
            ->add('experienceLevel', ChoiceType::class, [
                'label' => 'Niveau d\'expérience requis',
                'choices' => [
                    'Débutant' => 'junior',
                    'Intermédiaire' => 'mid',
                    'Expérimenté' => 'senior',
                    'Expert' => 'expert'
                ],
                'required' => true,
            ])
            ->add('salary', MoneyType::class, [
                'label' => 'Salaire annuel (€)',
                'required' => false,
                'currency' => 'EUR',
                'attr' => ['placeholder' => 'Ex: 45000'],
            ])
            ->add('requiredSkills', CollectionType::class, [
                'label' => 'Compétences requises',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'attr' => ['class' => 'skills-collection'],
            ])
            ->add('expiresAt', DateType::class, [
                'label' => 'Date d\'expiration',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['min' => (new DateTime())->format('Y-m-d')],
            ])
            ->add('contactEmail', TextType::class, [
                'label' => 'Email de contact',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: recrutement@team.com'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobOffer::class,
        ]);
    }
}

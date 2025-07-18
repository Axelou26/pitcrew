<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Trait\FileValidationTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfilePostulantType extends AbstractType
{
    use FileValidationTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('jobTitle', TextType::class, [
                'label' => 'Titre du poste recherché',
                'required' => false,
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('experience', TextareaType::class, [
                'label' => 'Expérience professionnelle',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('educationCollection', CollectionType::class, [
                'label' => 'Formation',
                'entry_type' => EducationEntryType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
                'by_reference' => false,
            ])
            ->add('skills', CollectionType::class, [
                'label' => 'Compétences',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ])
            ->add('profilePictureFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    $this->createImageFileConstraint('1024k', false),
                ],
                'attr' => $this->getImageFileAttributes('form-control', false),
            ])
            ->add('cvFile', FileType::class, [
                'label' => 'CV',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    $this->createPdfFileConstraint('2048k'),
                ],
                'attr' => $this->getPdfFileAttributes(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

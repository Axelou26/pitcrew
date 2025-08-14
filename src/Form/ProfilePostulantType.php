<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Applicant;
use App\Form\Trait\FileValidationTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfilePostulantType extends AbstractType
{
    use FileValidationTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label'    => 'Prénom',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Votre prénom',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label'    => 'Nom',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Votre nom',
                ],
            ])
            ->add('email', EmailType::class, [
                'label'    => 'Email',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'votre.email@exemple.com',
                ],
            ])
            ->add('phone', TelType::class, [
                'label'    => 'Téléphone',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Votre numéro de téléphone',
                ],
            ])
            ->add('jobTitle', TextType::class, [
                'label'    => 'Titre du poste recherché',
                'required' => false,
            ])
            ->add('bio', TextareaType::class, [
                'label'    => 'Biographie',
                'required' => false,
                'attr'     => ['rows' => 5],
            ])
            ->add('workExperience', CollectionType::class, [
                'label'        => 'Expériences professionnelles',
                'entry_type'   => WorkExperienceEntryType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'prototype'    => true,
                'required'     => false,
                'attr'         => [
                    'class' => 'work-experience-collection',
                ],
                'by_reference' => false,
            ])
            ->add('educationHistory', CollectionType::class, [
                'label'        => 'Formation',
                'entry_type'   => EducationEntryType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'prototype'    => true,
                'required'     => false,
                'attr'         => [
                    'class' => 'education-history-collection',
                ],
                'by_reference' => false,
            ])
            ->add('technicalSkills', CollectionType::class, [
                'label'        => 'Compétences techniques',
                'entry_type'   => TextType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'prototype'    => true,
                'required'     => false,
                'attr'         => [
                    'class' => 'technical-skills-collection',
                ],
                'by_reference' => false,
            ])
            ->add('softSkills', CollectionType::class, [
                'label'        => 'Compétences non techniques',
                'entry_type'   => TextType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'prototype'    => true,
                'required'     => false,
                'attr'         => [
                    'class' => 'soft-skills-collection',
                ],
                'by_reference' => false,
            ])
            ->add('profilePictureFile', FileType::class, [
                'label'       => 'Photo de profil',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    $this->createImageFileConstraint('1024k', false),
                ],
                'attr' => $this->getImageFileAttributes('form-control', false),
            ])
            ->add('cvFile', FileType::class, [
                'label'       => 'CV',
                'required'    => false,
                'mapped'      => false,
                'constraints' => [
                    new File([
                        'maxSize'   => '2M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF ou Word valide.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Applicant::class,
        ]);
    }

    public function getMaxFileSize(): int
    {
        return 10 * 1024 * 1024; // 10MB
    }
}

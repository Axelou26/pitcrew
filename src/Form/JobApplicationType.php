<?php

namespace App\Form;

use App\Entity\JobApplication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\NotNull;

class JobApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coverLetter', TextareaType::class, [
                'label' => 'Lettre de motivation',
                'required' => true,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Rédigez votre lettre de motivation...',
                    'class' => 'form-control'
                ],
            ])
            ->add('resume', FileType::class, [
                'label' => 'CV (PDF)',
                'required' => true,
                'data_class' => null,
                'constraints' => [
                    new NotNull([
                        'message' => 'Le CV est obligatoire'
                    ]),
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'maxSizeMessage' => 'Le fichier est trop volumineux. La taille maximale autorisée est {{ limit }}',
                        'mimeTypesMessage' => 'Seuls les fichiers PDF sont acceptés',
                        'notFoundMessage' => 'Le fichier n\'a pas été trouvé',
                        'notReadableMessage' => 'Le fichier n\'est pas lisible',
                        'uploadErrorMessage' => 'Erreur lors du téléchargement du fichier',
                    ])
                ],
                'attr' => [
                    'accept' => 'application/pdf',
                    'class' => 'form-control',
                    'data-bs-toggle' => 'tooltip',
                    'title' => 'Format accepté : PDF, taille maximale : 5 Mo'
                ],
                'help' => 'Format accepté : PDF, taille maximale : 5 Mo',
                'invalid_message' => 'Le CV est obligatoire et doit être au format PDF',
            ])
            ->add('additionalDocuments', FileType::class, [
                'label' => 'Documents complémentaires (PDF)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new Count([
                        'max' => 5,
                        'maxMessage' => 'Vous ne pouvez pas télécharger plus de {{ limit }} documents',
                    ]),
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '5M',
                                'mimeTypes' => [
                                    'application/pdf',
                                ],
                                'mimeTypesMessage' => 'Veuillez télécharger des documents PDF valides',
                            ])
                        ]
                    ])
                ],
                'attr' => [
                    'accept' => 'application/pdf',
                    'data-max-files' => 5
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobApplication::class,
        ]);
    }
} 
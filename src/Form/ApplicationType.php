<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Application;
use App\Form\Trait\FileValidationTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class ApplicationType extends AbstractType
{
    use FileValidationTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coverLetter', TextareaType::class, [
                'label'    => 'Lettre de motivation',
                'required' => true,
                'attr'     => [
                    'rows'        => 5,
                    'placeholder' => 'Décrivez votre motivation pour ce poste...',
                ],
            ])
            ->add('cvFile', FileType::class, [
                'label'       => 'CV (PDF)',
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
            ->add('additionalDocuments', FileType::class, [
                'label'       => 'Documents additionnels',
                'required'    => false,
                'mapped'      => false,
                'multiple'    => true,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize'   => '5M',
                                'mimeTypes' => [
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'image/jpeg',
                                    'image/png',
                                ],
                                'mimeTypesMessage' => 'Veuillez télécharger des fichiers valides.',
                            ]),
                        ],
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
        ]);
    }

    public function getMaxFileSize(): int
    {
        return 10 * 1024 * 1024; // 10MB
    }
}

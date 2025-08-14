<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Trait\FileValidationTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileRecruiterType extends AbstractType
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
            ->add('companyName', TextType::class, [
                'label'    => 'Nom de l\'entreprise',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Nom de votre entreprise',
                ],
            ])
            ->add('companyDescription', TextareaType::class, [
                'label'    => 'Description de l\'entreprise',
                'required' => false,
                'attr'     => [
                    'rows'        => 4,
                    'placeholder' => 'Décrivez votre entreprise...',
                ],
            ])
            ->add('logoFile', FileType::class, [
                'label'       => 'Logo de l\'entreprise',
                'required'    => false,
                'mapped'      => false,
                'constraints' => [
                    new File([
                        'maxSize'   => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Recruiter::class,
        ]);
    }

    public function getMaxFileSize(): int
    {
        return 10 * 1024 * 1024; // 10MB
    }
}

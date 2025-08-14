<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Post;
use App\Form\Trait\FileValidationTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

class PostType extends AbstractType
{
    use FileValidationTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label'    => 'Titre',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Titre de votre post (optionnel)',
                    'class'       => 'form-control',
                ],
                'constraints' => [
                    new Length([
                        'min'        => 3,
                        'max'        => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label'    => 'Contenu',
                'required' => true,
                'attr'     => [
                    'rows'        => 4,
                    'placeholder' => 'Partagez quelque chose avec votre réseau...',
                ],
            ])
            ->add('images', FileType::class, [
                'label'       => 'Images',
                'required'    => false,
                'multiple'    => true,
                'mapped'      => false,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize'   => '5M',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif',
                                ],
                                'mimeTypesMessage' => 'Veuillez télécharger des images valides.',
                            ]),
                        ],
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }

    public function getMaxFileSize(): int
    {
        return 5 * 1024 * 1024; // 5MB
    }
}

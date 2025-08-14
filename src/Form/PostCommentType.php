<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\PostComment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PostCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Votre commentaire',
                'attr'  => [
                    'placeholder' => 'Écrivez votre commentaire...',
                    'rows'        => 3,
                    'class'       => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un commentaire',
                    ]),
                    new Length([
                        'min'        => 2,
                        'max'        => 1000,
                        'minMessage' => 'Le commentaire doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le commentaire ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PostComment::class,
        ]);
    }
}

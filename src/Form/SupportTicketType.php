<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SupportTicket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SupportTicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'attr'  => [
                    'placeholder' => 'Sujet de votre demande',
                    'class'       => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le sujet ne peut pas être vide']),
                    new Length([
                        'min'        => 5,
                        'max'        => 100,
                        'minMessage' => 'Le sujet doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le sujet ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label'   => 'Catégorie',
                'mapped'  => false,
                'choices' => [
                    'Technique'   => 'technical',
                    'Facturation' => 'billing',
                    'Abonnement'  => 'subscription',
                    'Autre'       => 'other',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une catégorie']),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Description',
                'attr'  => [
                    'placeholder' => 'Décrivez votre problème en détail',
                    'class'       => 'form-control',
                    'rows'        => 6,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La description ne peut pas être vide']),
                    new Length([
                        'min'        => 20,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupportTicket::class,
        ]);
    }
}

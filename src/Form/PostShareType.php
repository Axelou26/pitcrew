<?php

namespace App\Form;

use App\Entity\PostShare;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostShareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment', TextareaType::class, [
                'label' => 'Ajouter un commentaire (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ajoutez un commentaire à votre partage...',
                    'rows' => 3,
                    'class' => 'form-control'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PostShare::class,
        ]);
    }
}

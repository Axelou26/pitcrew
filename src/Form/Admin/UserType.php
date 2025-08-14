<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('roles', ChoiceType::class, [
                'label'   => 'Rôles',
                'choices' => [
                    'Recruteur'      => 'ROLE_RECRUTEUR',
                    'Postulant'      => 'ROLE_POSTULANT',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('firstName', TextType::class, [
                'label'    => 'Prénom',
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'label'    => 'Nom',
                'required' => false,
            ])
            ->add('company', TextType::class, [
                'label'    => 'Entreprise',
                'required' => false,
            ])
            ->add('jobTitle', TextType::class, [
                'label'    => 'Poste',
                'required' => false,
            ])
            ->add('stripeCustomerId', TextType::class, [
                'label'    => 'ID Client Stripe',
                'required' => false,
            ])
            ->add('profilePictureFile', FileType::class, [
                'label'       => 'Photo de profil',
                'required'    => false,
                'mapped'      => false,
                'constraints' => [
                    new Image([
                        'maxSize'   => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG)',
                    ]),
                ],
            ])
            ->add('bio', TextareaType::class, [
                'label'    => 'Biographie',
                'required' => false,
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

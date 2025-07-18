<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Trait\FileValidationTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileRecruiterType extends AbstractType
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
            ->add('company', TextType::class, [
                'label' => 'Entreprise',
                'required' => true,
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Description de l\'entreprise',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('profilePictureFile', FileType::class, [
                'label' => 'Logo de l\'entreprise',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    $this->createImageFileConstraint('1024k', false),
                ],
                'attr' => $this->getImageFileAttributes('form-control', false),
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

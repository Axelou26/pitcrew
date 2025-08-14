<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicantSkillsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('technicalSkills', CollectionType::class, [
                'label'        => 'Compétences techniques',
                'entry_type'   => TextType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'prototype'    => true,
                'required'     => false,
                'attr'         => [
                    'class'                  => 'skills-collection',
                    'data-collection-holder' => 'technical-skills',
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
                    'class'                  => 'skills-collection',
                    'data-collection-holder' => 'soft-skills',
                ],
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Applicant',
        ]);
    }
}

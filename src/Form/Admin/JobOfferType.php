<?php

namespace App\Form\Admin;

use App\Entity\JobOffer;
use App\Entity\Recruiter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 10]
            ])
            ->add('company', TextType::class, [
                'label' => 'Entreprise'
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu'
            ])
            ->add('contractType', ChoiceType::class, [
                'label' => 'Type de contrat',
                'choices' => [
                    'CDI' => 'CDI',
                    'CDD' => 'CDD',
                    'Intérim' => 'INTERIM',
                    'Stage' => 'STAGE',
                    'Alternance' => 'ALTERNANCE',
                    'Freelance' => 'FREELANCE',
                ]
            ])
            ->add('salary', MoneyType::class, [
                'label' => 'Salaire',
                'currency' => 'EUR',
                'required' => false,
            ])
            ->add('expiresAt', DateType::class, [
                'label' => 'Date d\'expiration',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
            ->add('isRemote', CheckboxType::class, [
                'label' => 'Télétravail',
                'required' => false,
            ])
            ->add('isPromoted', CheckboxType::class, [
                'label' => 'Mise en avant',
                'required' => false,
            ])
            ->add('contactEmail', TextType::class, [
                'label' => 'Email de contact',
                'required' => false,
            ])
            ->add('contactPhone', TextType::class, [
                'label' => 'Téléphone de contact',
                'required' => false,
            ])
            ->add('recruiter', EntityType::class, [
                'label' => 'Recruteur',
                'class' => Recruiter::class,
                'choice_label' => function (Recruiter $recruiter) {
                    return sprintf('%s %s (%s)', $recruiter->getFirstName(), $recruiter->getLastName(), $recruiter->getEmail());
                },
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobOffer::class,
        ]);
    }
} 
<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Subscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => ['rows' => 5],
            ])
            ->add('price', MoneyType::class, [
                'label'    => 'Prix',
                'currency' => 'EUR',
            ])
            ->add('durationMonths', IntegerType::class, [
                'label' => 'Durée (mois)',
            ])
            ->add('maxJobOffers', IntegerType::class, [
                'label' => 'Nombre max d\'offres d\'emploi',
            ])
            ->add('stripePriceId', TextType::class, [
                'label'    => 'ID Prix Stripe',
                'required' => false,
            ])
            ->add('stripeProductId', TextType::class, [
                'label'    => 'ID Produit Stripe',
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label'    => 'Actif',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
        ]);
    }
}

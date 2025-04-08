<?php

namespace App\Form\Admin;

use App\Entity\RecruiterSubscription;
use App\Entity\Recruiter;
use App\Entity\Subscription;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecruiterSubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subscription', EntityType::class, [
                'label' => 'Plan d\'abonnement',
                'class' => Subscription::class,
                'choice_label' => function (Subscription $subscription) {
                    return sprintf('%s (%s €)', $subscription->getName(), $subscription->getPrice() / 100);
                },
            ])
            ->add('recruiter', EntityType::class, [
                'label' => 'Recruteur',
                'class' => Recruiter::class,
                'choice_label' => function (Recruiter $recruiter) {
                    return sprintf('%s %s (%s)', $recruiter
                        ->getFirstName(), $recruiter
                        ->getLastName(), $recruiter
                        ->getEmail());
                },
                'required' => true,
                'placeholder' => 'Sélectionner un recruteur',
                'attr' => [
                    'class' => 'form-control'
                ],
                'invalid_message' => 'Veuillez sélectionner un recruteur valide.'
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('stripeSubscriptionId', TextType::class, [
                'label' => 'ID Abonnement Stripe',
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
            ->add('cancelled', CheckboxType::class, [
                'label' => 'Annulé',
                'required' => false,
            ])
            ->add('autoRenew', CheckboxType::class, [
                'label' => 'Renouvellement automatique',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecruiterSubscription::class,
        ]);
    }
}

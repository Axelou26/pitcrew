<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserTypeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('userType', ChoiceType::class, [
                'label'   => 'Quel type de compte souhaitez-vous créer ?',
                'choices' => [
                    'Je cherche un emploi dans le sport automobile' => User::ROLE_POSTULANT,
                    'Je souhaite recruter des talents'              => User::ROLE_RECRUTEUR,
                ],
                'expanded'    => true,
                'multiple'    => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez choisir un type de compte',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}

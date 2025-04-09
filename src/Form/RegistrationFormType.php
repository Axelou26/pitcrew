<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length as AssertLength;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email as AssertEmail;
use App\Service\EmailValidationService;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Champs communs à tous les utilisateurs
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Votre prénom'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le prénom est obligatoire',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Votre nom'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom est obligatoire',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'votre@email.com'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'email est obligatoire',
                    ]),
                    new AssertEmail([
                        'message' => 'L\'email n\'est pas valide',
                        'mode' => AssertEmail::VALIDATION_MODE_STRICT,
                        'normalizer' => 'trim',
                    ]),
                    new AssertLength([
                        'max' => 254,
                        'maxMessage' => 'L\'email ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new AssertLength([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte les conditions d\'utilisation',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation',
                    ]),
                ],
            ]);

        // Ajouter les champs spécifiques en fonction du type d'utilisateur
        $this->addUserSpecificFields($builder, $options['user_type'] ?? null);
    }

    /**
     * Ajoute les champs spécifiques au formulaire en fonction du type d'utilisateur.
     */
    private function addUserSpecificFields(FormBuilderInterface $builder, ?string $userType): void
    {
        if ($userType === User::ROLE_RECRUTEUR) {
            $this->addRecruiterFields($builder);
        }

        if ($userType === User::ROLE_POSTULANT) {
            $this->addApplicantFields($builder);
        }
    }

    /**
     * Ajoute les champs spécifiques aux recruteurs.
     */
    private function addRecruiterFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('company', TextType::class, [
                'label' => 'Entreprise',
                'attr' => ['placeholder' => 'Nom de votre entreprise'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le nom de votre entreprise',
                    ]),
                ],
            ])
            ->add('position', TextType::class, [
                'label' => 'Votre poste',
                'mapped' => false,
                'attr' => ['placeholder' => 'Votre poste dans l\'entreprise'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre poste',
                    ]),
                ],
            ]);
    }

    /**
     * Ajoute les champs spécifiques aux postulants.
     */
    private function addApplicantFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('jobTitle', TextType::class, [
                'label' => 'Poste recherché',
                'mapped' => false,
                'attr' => ['placeholder' => 'Ex: Ingénieur F1, Mécanicien, etc.'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le poste recherché',
                    ]),
                ],
            ])
            ->add('skills', TextType::class, [
                'label' => 'Compétences',
                'mapped' => false,
                'attr' => ['placeholder' => 'Ex: Mécanique, Aérodynamique, etc.'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer vos compétences',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'user_type' => null,  // Option pour déterminer le type d'utilisateur
        ]);
    }
}

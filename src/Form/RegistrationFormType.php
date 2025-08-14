<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Applicant;
use App\Entity\Recruiter;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email as AssertEmail;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length as AssertLength;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Champs communs à tous les utilisateurs
        $builder
            ->add('firstName', TextType::class, [
                'label'       => 'Prénom *',
                'attr'        => ['placeholder' => 'Votre prénom'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le prénom est obligatoire',
                    ]),
                    new AssertLength([
                        'min'        => 2,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'max'        => 50,
                        'maxMessage' => 'Le prénom ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label'       => 'Nom *',
                'attr'        => ['placeholder' => 'Votre nom'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom est obligatoire',
                    ]),
                    new AssertLength([
                        'min'        => 2,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'max'        => 50,
                        'maxMessage' => 'Le nom ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'Adresse email *',
                'attr'        => ['placeholder' => 'votre@email.com'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'email est obligatoire',
                    ]),
                    new AssertEmail([
                        'message'    => 'L\'email n\'est pas valide',
                        'mode'       => AssertEmail::VALIDATION_MODE_STRICT,
                        'normalizer' => 'trim',
                    ]),
                    new AssertLength([
                        'max'        => 254,
                        'maxMessage' => 'L\'email ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'label'       => 'Téléphone',
                'required'    => false,
                'attr'        => ['placeholder' => '+33 6 12 34 56 78'],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^(\+33|0)[1-9](\d{8})$/',
                        'message' => 'Veuillez entrer un numéro de téléphone français valide',
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'label'       => 'Ville',
                'required'    => false,
                'attr'        => ['placeholder' => 'Votre ville'],
                'constraints' => [
                    new AssertLength([
                        'max'        => 100,
                        'maxMessage' => 'La ville ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'          => PasswordType::class,
                'mapped'        => false,
                'first_options' => [
                    'label' => 'Mot de passe *',
                    'attr'  => [
                        'autocomplete' => 'new-password',
                        'placeholder'  => 'Minimum 8 caractères',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe *',
                    'attr'  => [
                        'autocomplete' => 'new-password',
                        'placeholder'  => 'Répétez votre mot de passe',
                    ],
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new AssertLength([
                        'min'        => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'max'        => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&-_])[A-Za-z\d@$!%*?&]{8,}$/',
                        'message' => 'Le mot de passe doit contenir au moins 8
                                caractères avec : une minuscule, une majuscule,
                                 un chiffre et un caractère spécial (@$!%*?&).
                                Exemple : Test123!',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped'      => false,
                'label'       => 'J\'accepte les conditions d\'utilisation et la politique de confidentialité *',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation',
                    ]),
                ],
            ])
            ->add('agreeNewsletter', CheckboxType::class, [
                'mapped'   => false,
                'required' => false,
                'label'    => 'Je souhaite recevoir les newsletters et offres de PitCrew',
            ]);

        // Ajouter les champs spécifiques en fonction du type d'utilisateur
        $this->addUserSpecificFields($builder, $options['user_type'] ?? null);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class, // Classe par défaut
            'user_type'  => null,  // Option pour déterminer le type d'utilisateur
        ]);

        // Modifier la classe de données en fonction du type d'utilisateur
        $resolver->addNormalizer('data_class', function (Options $options, $value) {
            if ($options['user_type'] === User::ROLE_RECRUTEUR) {
                return Recruiter::class;
            }
            if ($options['user_type'] === User::ROLE_POSTULANT) {
                return Applicant::class;
            }

            return $value;
        });
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
            ->add('companyName', TextType::class, [
                'label'       => 'Nom de l\'entreprise *',
                'attr'        => ['placeholder' => 'Nom de votre entreprise'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le nom de votre entreprise',
                    ]),
                    new AssertLength([
                        'max'        => 255,
                        'maxMessage' => 'Le nom de l\'entreprise ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('jobTitle', TextType::class, [
                'label'       => 'Votre poste *',
                'attr'        => ['placeholder' => 'Ex: Directeur RH, Recruteur, etc.'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre poste',
                    ]),
                    new AssertLength([
                        'max'        => 255,
                        'maxMessage' => 'Le poste ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('sector', ChoiceType::class, [
                'label'    => 'Secteur d\'activité',
                'required' => false,
                'choices'  => [
                    'Sport automobile' => 'sport_automobile',
                    'Formule 1'        => 'formule_1',
                    'Endurance'        => 'endurance',
                    'Rally'            => 'rally',
                    'Karting'          => 'karting',
                    'Simulation'       => 'simulation',
                    'Autre'            => 'autre',
                ],
                'placeholder' => 'Sélectionnez un secteur',
            ])
            ->add('companySize', ChoiceType::class, [
                'label'    => 'Taille de l\'entreprise',
                'required' => false,
                'choices'  => [
                    '1-10 employés'     => '1-10',
                    '11-50 employés'    => '11-50',
                    '51-200 employés'   => '51-200',
                    '201-1000 employés' => '201-1000',
                    'Plus de 1000'      => '1000+',
                ],
                'placeholder' => 'Sélectionnez la taille',
            ])
            ->add('website', UrlType::class, [
                'label'       => 'Site web de l\'entreprise',
                'required'    => false,
                'attr'        => ['placeholder' => 'https://www.votre-entreprise.com'],
                'constraints' => [
                    new AssertLength([
                        'max'        => 255,
                        'maxMessage' => 'L\'URL ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('companyDescription', TextareaType::class, [
                'label'    => 'Description de l\'entreprise',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Décrivez votre entreprise, ses valeurs, ses projets...',
                    'rows'        => 4,
                ],
                'constraints' => [
                    new AssertLength([
                        'max'        => 1000,
                        'maxMessage' => 'La description ne doit pas dépasser {{ limit }} caractères',
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
                'label'       => 'Poste recherché *',
                'attr'        => ['placeholder' => 'Ex: Ingénieur F1, Mécanicien, Pilote, etc.'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le poste recherché',
                    ]),
                    new AssertLength([
                        'max'        => 255,
                        'maxMessage' => 'Le poste recherché ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('location', TextType::class, [
                'label'       => 'Localisation souhaitée',
                'required'    => false,
                'attr'        => ['placeholder' => 'Ex: Paris, Lyon, Monaco, etc.'],
                'constraints' => [
                    new AssertLength([
                        'max'        => 255,
                        'maxMessage' => 'La localisation ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('technicalSkills', TextareaType::class, [
                'label'    => 'Compétences techniques',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Ex: Mécanique, Aérodynamique, Électronique, Simulation, etc.',
                    'rows'        => 3,
                ],
                'constraints' => [
                    new AssertLength([
                        'max'        => 500,
                        'maxMessage' => 'Les compétences techniques ne doivent pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'mapped' => false,

            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Présentation personnelle',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Parlez de votre parcours, de vos motivations, de vos objectifs...',
                    'rows'        => 4,
                ],
                'constraints' => [
                    new AssertLength([
                        'max'        => 1000,
                        'maxMessage' => 'La présentation ne doit pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('experienceLevel', ChoiceType::class, [
                'label'    => 'Niveau d\'expérience',
                'required' => false,
                'choices'  => [
                    'Débutant (0-2 ans)'      => 'debutant',
                    'Intermédiaire (3-5 ans)' => 'intermediaire',
                    'Confirmé (6-10 ans)'     => 'confirme',
                    'Expert (10+ ans)'        => 'expert',
                ],
                'placeholder' => 'Sélectionnez votre niveau',
            ])
            ->add('availability', ChoiceType::class, [
                'label'    => 'Disponibilité',
                'required' => false,
                'choices'  => [
                    'Immédiate'   => 'immediate',
                    'Sous 1 mois' => '1_mois',
                    'Sous 3 mois' => '3_mois',
                    'À discuter'  => 'a_discuter',
                ],
                'placeholder' => 'Sélectionnez votre disponibilité',
            ]);
    }
}

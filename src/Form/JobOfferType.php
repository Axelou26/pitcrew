<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\JobOffer;
use App\Form\Trait\FileValidationTrait;
use App\Form\Type\JobOfferTypeInterface;
use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class JobOfferType extends AbstractType implements JobOfferTypeInterface
{
    use FileValidationTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label'    => 'Titre du poste',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Ex: Développeur PHP Senior',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description du poste',
                'required' => true,
                'attr'     => [
                    'rows'        => 8,
                    'placeholder' => 'Décrivez les missions, responsabilités et exigences du poste...',
                ],
            ])
            ->add('company', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'attr'  => ['placeholder' => 'Ex: Mercedes F1 Team'],
            ])
            ->add('image', FileType::class, [
                'label'       => 'Image de l\'offre',
                'required'    => false,
                'data_class'  => null,
                'constraints' => [
                    new File([
                        'maxSize'          => $this->getMaxFileSize(),
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, GIF).',
                    ]),
                ],
                'attr' => $this->getImageFileAttributes(),
            ])
            ->add('logoFile', FileType::class, [
                'label'       => 'Logo de l\'entreprise',
                'required'    => false,
                'mapped'      => false,
                'constraints' => [
                    new File([
                        'maxSize'   => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/svg+xml',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, SVG).',
                    ]),
                ],
            ])
            ->add('contractType', ChoiceType::class, [
                'label'    => 'Type de contrat',
                'required' => true,
                'choices'  => [
                    'CDI'        => 'CDI',
                    'CDD'        => 'CDD',
                    'Freelance'  => 'Freelance',
                    'Stage'      => 'Stage',
                    'Alternance' => 'Alternance',
                ],
            ])
            ->add('location', TextType::class, [
                'label'    => 'Localisation',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Ex: Paris, France',
                ],
            ])
            ->add('isRemote', CheckboxType::class, [
                'label'    => 'Télétravail possible',
                'required' => false,
            ])
            ->add('experienceLevel', ChoiceType::class, [
                'label'   => 'Niveau d\'expérience requis',
                'choices' => [
                    'Débutant'      => 'junior',
                    'Intermédiaire' => 'mid',
                    'Expérimenté'   => 'senior',
                    'Expert'        => 'expert',
                ],
                'required' => true,
            ])
            ->add('salary', MoneyType::class, [
                'label'    => 'Salaire annuel',
                'required' => false,
                'currency' => 'EUR',
                'attr'     => [
                    'placeholder' => 'Ex: 45000',
                ],
            ])
            ->add('requiredSkills', CollectionType::class, [
                'label'        => 'Compétences requises',
                'entry_type'   => TextType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'required'     => false,
                'attr'         => [
                    'class' => 'skills-collection',
                ],
            ])
            ->add('expiresAt', DateType::class, [
                'label'    => 'Date d\'expiration',
                'required' => false,
                'widget'   => 'single_text',
                'attr'     => ['min' => (new DateTime())->format('Y-m-d')],
            ])
            ->add('contactEmail', TextType::class, [
                'label'    => 'Email de contact',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: recrutement@team.com'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobOffer::class,
        ]);
    }

    public function getMaxFileSize(): int
    {
        return 5 * 1024 * 1024; // 5MB
    }
}

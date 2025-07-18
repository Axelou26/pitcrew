<?php

namespace App\Form;

use App\Entity\JobApplication;
use App\Form\Trait\FileValidationTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class JobApplicationType extends AbstractType
{
    use FileValidationTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coverLetter', TextareaType::class, [
                'label' => 'Lettre de motivation',
                'required' => true,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Rédigez votre lettre de motivation...',
                    'class' => 'form-control'
                ],
            ])
            ->add('resume', FileType::class, [
                'label' => 'CV (PDF)',
                'required' => true,
                'data_class' => null,
                'constraints' => [
                    new NotNull([
                        'message' => 'Le CV est obligatoire'
                    ]),
                    $this->createPdfFileConstraint(),
                ],
                'attr' => $this->getPdfFileAttributes(),
                'help' => 'Format accepté : PDF, taille maximale : 5 Mo',
                'invalid_message' => 'Le CV est obligatoire et doit être au format PDF',
            ])
            ->add('additionalDocuments', FileType::class, [
                'label' => 'Documents complémentaires (PDF)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    $this->createFileCountConstraint(),
                    $this->createMultipleFilesConstraint(),
                ],
                'attr' => $this->getMultipleFilesAttributes(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobApplication::class,
        ]);
    }
}

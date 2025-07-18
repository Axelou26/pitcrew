<?php

namespace App\Form;

use App\Entity\Application;
use App\Form\Trait\FileValidationTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationType extends AbstractType
{
    use FileValidationTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coverLetter', TextareaType::class, [
                'label' => 'Lettre de motivation',
                'required' => true,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Expliquez pourquoi vous êtes le candidat idéal pour ce poste...'
                ]
            ])
            ->add('cvFile', FileType::class, [
                'label' => 'CV (PDF)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    $this->createPdfFileConstraint(self::MAX_SIZE_SMALL),
                ],
                'attr' => $this->getPdfFileAttributes(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
        ]);
    }
}

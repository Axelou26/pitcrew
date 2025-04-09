<?php

namespace App\Form\Type;

use App\Entity\Interview;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface InterviewTypeInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void;
    public function configureOptions(OptionsResolver $resolver): void;
    public function getBlockPrefix(): string;
} 
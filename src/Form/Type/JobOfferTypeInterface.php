<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface JobOfferTypeInterface extends FormTypeInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void;

    public function configureOptions(OptionsResolver $resolver): void;
}

<?php

namespace App\Form;

use App\Entity\Interview;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Repository\JobOfferRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class InterviewType extends AbstractType
{
    private $security;
    private $userRepository;
    private $jobOfferRepository;

    public function __construct(
        Security $security,
        UserRepository $userRepository,
        JobOfferRepository $jobOfferRepository
    ) {
        $this->security = $security;
        $this->userRepository = $userRepository;
        $this->jobOfferRepository = $jobOfferRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'entretien',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Exemple: Entretien pour le poste de Mécanicien F1']
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes ou instructions',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Informations supplémentaires pour le candidat'
                ]
            ]);

        // Si c'est un recruteur, on ajoute le champ pour sélectionner un candidat
        if (in_array('ROLE_RECRUTEUR', $this->security->getUser()->getRoles())) {
            // Si une offre d'emploi est fournie via les options, on affiche uniquement les candidats de cette offre
            if (isset($options['job_offer_id']) && $options['job_offer_id']) {
                $jobOfferId = $options['job_offer_id'];
                $builder->add('applicant', EntityType::class, [
                    'class' => User::class,
                    'choice_label' => function (User $user) {
                        return $user->getFullName() . ' (' . $user->getEmail() . ')';
                    },
                    'query_builder' => function (UserRepository $er) use ($jobOfferId) {
                        return $er->createQueryBuilder('u')
                            ->join('u.applications', 'ja')
                            ->where('ja.jobOffer = :jobOfferId')
                            ->andWhere('u.roles LIKE :role')
                            ->setParameter('jobOfferId', $jobOfferId)
                            ->setParameter('role', '%ROLE_POSTULANT%')
                            ->orderBy('u.lastName', 'ASC');
                    },
                    'label' => 'Candidat',
                    'placeholder' => 'Sélectionnez un candidat',
                    'attr' => ['class' => 'form-control']
                ]);
            } else {
                // Sinon on affiche tous les candidats
                $builder->add('applicant', EntityType::class, [
                    'class' => User::class,
                    'choice_label' => function (User $user) {
                        return $user->getFullName() . ' (' . $user->getEmail() . ')';
                    },
                    'query_builder' => function (UserRepository $er) {
                        return $er->createQueryBuilder('u')
                            ->where('u.roles LIKE :role')
                            ->setParameter('role', '%ROLE_POSTULANT%')
                            ->orderBy('u.lastName', 'ASC');
                    },
                    'label' => 'Candidat',
                    'placeholder' => 'Sélectionnez un candidat',
                    'attr' => ['class' => 'form-control']
                ]);
            }

            // Ajout du champ pour sélectionner une offre d'emploi si non pré-définie
            if (!isset($options['job_offer_id']) || !$options['job_offer_id']) {
                $builder->add('jobOffer', EntityType::class, [
                    'class' => JobOffer::class,
                    'choice_label' => 'title',
                    'query_builder' => function (JobOfferRepository $er) {
                        $user = $this->security->getUser();
                        return $er->createQueryBuilder('j')
                            ->where('j.recruiter = :recruiter')
                            ->andWhere('j.expiresAt > :now')
                            ->setParameter('recruiter', $user)
                            ->setParameter('now', new \DateTime())
                            ->orderBy('j.title', 'ASC');
                    },
                    'label' => 'Offre d\'emploi associée',
                    'required' => false,
                    'placeholder' => 'Sélectionnez une offre (optionnel)',
                    'attr' => ['class' => 'form-control']
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Interview::class,
            'job_offer_id' => null,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Post;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use DateTimeImmutable;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            ApplicantFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getPostData() as $index => $data) {
            $post = new Post();
            $post->setTitle($data['title'])
                ->setContent($data['content'])
                ->setAuthor($this->getReference(ApplicantFixtures::APPLICANT_REFERENCE_PREFIX . ($index % 2)));

            $manager->persist($post);
        }

        $manager->flush();
    }

    private function getPostData(): array
    {
        return [
            [
                'title' => 'Mon expérience en tant que développeur Full Stack',
                'content' => 'Voici mon retour d\'expérience après 5 ans en tant que développeur Full Stack...'
            ],
            [
                'title' => 'Les tendances UX/UI en 2024',
                'content' => 'Découvrez les dernières tendances en matière de design d\'interface...'
            ],
            [
                'title' => 'Comment réussir son entretien technique',
                'content' => 'Conseils et astuces pour bien se préparer à un entretien technique...'
            ],
            [
                'title' => 'Les meilleures pratiques en développement web',
                'content' => 'Guide des bonnes pratiques pour un code propre et maintenable...'
            ],
            // Ajoutez d'autres posts selon vos besoins
        ];
    }
}

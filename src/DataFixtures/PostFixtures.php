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
                'title' => 'Nouvelle saison en F1 !',
                'content' => 'Ravi de commencer une nouvelle saison avec @Alpine F1 Team ! ' .
                    'Les nouveaux développements aéro sont prometteurs 🏎️ ' .
                    '#F1 #Alpine #Innovation #Aérodynamique'
            ],
            [
                'title' => 'Victoire aux 24h du Mans',
                'content' => 'Félicitations à toute l\'équipe @Toyota Gazoo Racing pour cette incroyable victoire ! ' .
                    'Un travail d\'équipe exceptionnel 🏆 Merci à @Michelin Motorsport pour les pneus parfaits. ' .
                    '#WEC #24hLeMans #Endurance #Toyota'
            ],
            [
                'title' => 'Innovation en sport automobile',
                'content' => 'Passionnant projet sur les nouveaux matériaux composites avec @Ferrari GT Racing. ' .
                    'L\'avenir de la performance est dans l\'innovation ! ' .
                    '#Innovation #Composite #SportAuto #R&D'
            ],
            [
                'title' => 'Développement moteur électrique',
                'content' => 'Fier de travailler sur le nouveau groupe propulseur avec @Porsche Motorsport ' .
                    'pour la #FormulaE. L\'électrique est l\'avenir ! @FIA ' .
                    '#Innovation #Électrique #Motorsport'
            ],
            [
                'title' => 'Tests pneumatiques réussis',
                'content' => 'Excellente session de tests aujourd\'hui avec @Michelin Motorsport ' .
                    'sur le circuit de @Magny-Cours. Les nouveaux composés sont très prometteurs ! ' .
                    '#Pneus #Performance #R&D'
            ],
            [
                'title' => 'Formation jeunes mécaniciens',
                'content' => 'Super journée de formation à @FFSA Academy ! ' .
                    'Transmettre sa passion aux futures générations de mécaniciens 🔧 ' .
                    '#Formation #Mécanique #SportAuto'
            ],
            [
                'title' => 'Analyse de données en course',
                'content' => 'Fascinant de voir comment l\'analyse de données révolutionne ' .
                    'la stratégie de course @Alpine F1 Team. Merci à @AWS pour les outils performants ! ' .
                    '#DataScience #F1 #Innovation'
            ],
            [
                'title' => 'Nouveau défi professionnel',
                'content' => 'Très heureux de rejoindre @McLaren Automotive comme ingénieur performance ! ' .
                    'Hâte de travailler sur les futures supercars 🏎️ ' .
                    '#McLaren #Engineering #Automotive'
            ],
            [
                'title' => 'Salon de l\'emploi motorsport',
                'content' => 'Retrouvez-nous au salon @PitCrew_Jobs la semaine prochaine ! ' .
                    'De belles opportunités dans le sport automobile 🏁 ' .
                    '@Alpine F1 Team @Toyota Gazoo Racing #Emploi #Motorsport'
            ],
            [
                'title' => 'Innovation en sécurité',
                'content' => 'Présentation de nos dernières innovations en matière de sécurité avec @FIA. ' .
                    'La sécurité reste la priorité n°1 ! ' .
                    '#Sécurité #Innovation #Motorsport'
            ],
            [
                'title' => 'Succès en GT3',
                'content' => 'Podium pour @Aston Martin Racing ce weekend ! ' .
                    'Excellent travail de toute l\'équipe technique 🏆 ' .
                    '#GT3 #AstonMartin #Racing'
            ],
            [
                'title' => 'Développement durable',
                'content' => 'Fier de travailler sur les carburants durables avec @Porsche Motorsport. ' .
                    'L\'avenir du sport auto sera vert ! ' .
                    '#Développement_Durable #Innovation #Motorsport'
            ],
            [
                'title' => 'Stage réussi',
                'content' => 'Fin de mon stage chez @Ferrari F1 ! ' .
                    'Une expérience incroyable dans le monde de la F1 🏎️ ' .
                    'Merci à toute l\'équipe ! #F1 #Stage #Ferrari'
            ],
            [
                'title' => 'Conférence technique',
                'content' => 'Passionnante conférence sur l\'aérodynamique avec @Alpine F1 Team ' .
                    'et @ISAE-SUPAERO. Le futur de l\'ingénierie course ! ' .
                    '#Aérodynamique #Innovation'
            ],
            [
                'title' => 'Championnat WEC',
                'content' => 'En route pour la nouvelle saison @WEC avec @Peugeot Sport ! ' .
                    'Les nouveaux prototypes sont impressionnants 🏎️ ' .
                    '#WEC #Peugeot #Endurance'
            ]
        ];
    }
}

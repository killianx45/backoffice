<?php

namespace App\DataFixtures;

use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TagFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tagsData = [
            [
                "nom" => 'Sport',
                "color" => "#EB9B1B"
            ],
            [
                "nom" => 'Santé',
                "color" => "#64BC7A"
            ],
            [
                "nom" => 'Nutrition',
                "color" => "#A0CF74"
            ],
            [
                "nom" => 'Escalade',
                "color" => "#152FCE"
            ],
            [
                "nom" => 'Musculation',
                "color" => "#FD80B2"
            ],
            [
                "nom" => 'Natation',
                "color" => "#79EFD7"
            ],
            [
                "nom" => 'Metrics',
                "color" => "#E4AEEE"
            ]
        ];

        foreach ($tagsData as $tagData) {
            $tag = new Tag();
            $tag->setNom($tagData['nom']);
            $tag->setColor($tagData['color']);
            $tag->setSlug($tagData['nom']);
            $manager->persist($tag);
        }

        $manager->flush();
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\Editor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class EditorFixtures extends Fixture implements FixtureGroupInterface
{
    public const EDITOR_REF = 'editor_';

    public static function getGroups(): array
    {
        return ['editors'];
    }

    public function load(ObjectManager $manager): void
    {
        $editors = [
            ['Nintendo', 'Japon'],
            ['Ubisoft', 'France'],
            ['Electronic Arts', 'États-Unis'],
            ['Square Enix', 'Japon'],
            ['CD Projekt', 'Pologne'],
        ];

        for ($i = 1; $i <= 5; $i++) {
            $editor = new Editor();
            $editor->setName($editors[$i - 1][0]);
            $editor->setCountry($editors[$i - 1][1]);

            $manager->persist($editor);

            $this->addReference(self::EDITOR_REF . $i, $editor);
        }

        $manager->flush();
    }
}

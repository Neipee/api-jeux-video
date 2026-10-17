<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Editor;
use App\Entity\VideoGame;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VideoGameFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['videogames'];
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            EditorFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 1; $i <= 10; $i++) {
            $releaseDate = new \DateTime('today');
            $releaseDate->modify(rand(-1000, 30) . ' days');

            $videoGame = new VideoGame();
            $videoGame->setTitle($faker->sentence(3));
            $videoGame->setReleaseDate($releaseDate);
            $videoGame->setDescription($faker->paragraph());

            $categoryNumber = rand(1, 5);
            $categoryRef = CategoryFixtures::CATEGORY_REF . $categoryNumber;
            $videoGame->setCategory($this->getReference($categoryRef, Category::class));

            $editorNumber = rand(1, 5);
            $editorRef = EditorFixtures::EDITOR_REF . $editorNumber;
            $videoGame->setEditor($this->getReference($editorRef, Editor::class));

            $manager->persist($videoGame);
        }

        $manager->flush();
    }
}

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

        $videoGames = [
            ['The Legend of Zelda: Tears of the Kingdom', '2023-05-12', 2, 1],
            ['Super Mario Bros. Wonder', '2023-10-20', 1, 1],
            ['Assassin\'s Creed Mirage', '2023-10-05', 2, 2],
            ['Anno 117: Pax Romana', '2025-11-13', 5, 2],
            ['EA Sports FC 25', '2024-09-27', 4, 3],
            ['Battlefield 6', '2025-10-10', 1, 3],
            ['Final Fantasy VII Rebirth', '2024-02-29', 3, 4],
            ['Dragon Quest XI', '2017-07-29', 3, 4],
            ['The Witcher 3: Wild Hunt', '2015-05-19', 3, 5],
            ['Cyberpunk 2077', '2020-12-10', 3, 5],
        ];

        foreach ($videoGames as $data) {
            $videoGame = new VideoGame();
            $videoGame->setTitle($data[0]);
            $videoGame->setReleaseDate(new \DateTime($data[1]));
            $videoGame->setDescription($faker->paragraph());
            $videoGame->setCategory($this->getReference(CategoryFixtures::CATEGORY_REF . $data[2], Category::class));
            $videoGame->setEditor($this->getReference(EditorFixtures::EDITOR_REF . $data[3], Editor::class));

            $manager->persist($videoGame);
        }

        for ($i = 1; $i <= 3; $i++) {
            $releaseDate = new \DateTime('today');
            $releaseDate->modify('+' . ($i * 2) . ' days');

            $videoGame = new VideoGame();
            $videoGame->setTitle($faker->sentence(3));
            $videoGame->setReleaseDate($releaseDate);
            $videoGame->setDescription($faker->paragraph());
            $videoGame->setCategory($this->getReference(CategoryFixtures::CATEGORY_REF . rand(1, 5), Category::class));
            $videoGame->setEditor($this->getReference(EditorFixtures::EDITOR_REF . rand(1, 5), Editor::class));

            $manager->persist($videoGame);
        }

        $manager->flush();
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public const CATEGORY_REF = 'category_';

    public static function getGroups(): array
    {
        return ['categories'];
    }

    public function load(ObjectManager $manager): void
    {
        $names = ['Action', 'Aventure', 'RPG', 'Sport', 'Stratégie'];

        for ($i = 1; $i <= 5; $i++) {
            $category = new Category();
            $category->setName($names[$i - 1]);

            $manager->persist($category);

            $this->addReference(self::CATEGORY_REF . $i, $category);
        }

        $manager->flush();
    }
}

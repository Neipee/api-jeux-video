<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['users'];
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@jeux.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword(password_hash('admin', PASSWORD_ARGON2ID));
        $admin->setSubcriptionToNewsletter(true);

        $manager->persist($admin);

        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setEmail('user' . $i . '@jeux.fr');
            $user->setRoles([]);
            $user->setPassword(password_hash('user', PASSWORD_ARGON2ID));
            $user->setSubcriptionToNewsletter($i % 2 == 1);

            $manager->persist($user);
        }

        $manager->flush();
    }
}

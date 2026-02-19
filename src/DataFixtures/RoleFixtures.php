<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class RoleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $role = new Role();
        $role->setName('ROLE_ROOT');

        $manager->persist($role);
        $manager->flush();

        $role = new Role();
        $role->setName('ROLE_USER');

        $manager->persist($role);
        $manager->flush();
    }
}

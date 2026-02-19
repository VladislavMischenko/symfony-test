<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $role = $manager->getRepository(Role::class)->findOneBy(['name' => 'ROLE_ROOT']);

        $user = new User();
        $user->setLogin('Root');
        $user->setPhone('123-1231-12');
        $user->setPass('123456');
        $user->setRole($role);

        $manager->persist($user);
        $manager->flush();
    }
}

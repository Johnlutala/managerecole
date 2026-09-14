<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            
            [
                'firstname' => 'John',
                'lastname' => 'KISINA',
                'username' => 'johnkisina',
                'email' => 'jk.lutala@gmail.com',
                'password' => 'Admin123', 
                'roles' => ['ROLE_ADMIN'],
            ],
        ];

        foreach ($users as $userData) {
            $user = new User();

            $user->setFirstname($userData['firstname']);
            $user->setLastname($userData['lastname']);
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);

            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $userData['password']
                )
            );

            $manager->persist($user);
        }

        $manager->flush();
    }
}
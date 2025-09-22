<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $users = [
            [
                'firstname' => 'Daniel',
                'lastname' => 'Zema',
                'username' => 'zema.d', 
                'email' => 'zema.d@infosetgroup.com', 
                'password' => 'Bl4fLNZe43F/', 
                'role' => 'ROLE_SUPER_ADMIN'
            ],
            [
                'firstname' => 'Leonardo',
                'lastname' => 'Rubuz',
                'username' => 'rubuz.l',
                'email' => 'rubuz.l@infosetgroup.com',
                'password' => 'AtB0G5Ins9v/',
                'role' => 'ROLE_SUPER_ADMIN'
            ],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setFirstname($userData['firstname']);
            $user->setLastname($userData['lastname']);
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            // In a real application, ensure to hash passwords properly
            $user->setPassword(password_hash($userData['password'], PASSWORD_BCRYPT));
            
            // Find the role by name
            $role = $manager->getRepository(Role::class)->findOneBy(['name' => $userData['role']]);
            if ($role) {
                $user->setRole($role);
            }
            
            $manager->persist($user);
        }

        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}

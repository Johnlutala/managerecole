<?php

namespace App\DataFixtures;

use App\Entity\Permission;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PermissionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $permissions = [
            ['name' => 'VIEW_DASHBOARD', 'description' => 'Accès au tableau de bord'],
            ['name' => 'MANAGE_USERS', 'description' => 'Gérer les utilisateurs'],
            ['name' => 'MANAGE_ROLES', 'description' => 'Gérer les rôles'],
            ['name' => 'CREATE_WORKSHOP', 'description' => 'Créer un atelier'],
            ['name' => 'EDIT_WORKSHOP', 'description' => 'Modifier un atelier'],
            ['name' => 'DELETE_WORKSHOP', 'description' => 'Supprimer un atelier'],
            ['name' => 'CREATE_PARTICIPANT', 'description' => 'Créer un participant'],
            ['name' => 'EDIT_PARTICIPANT', 'description' => 'Modifier un participant'],
            ['name' => 'DELETE_PARTICIPANT', 'description' => 'Supprimer un participant'],
            ['name' => 'MANAGE_PERMISSIONS', 'description' => 'Gérer les permissions'],
            ['name' => 'VIEW_REPORTS', 'description' => 'Voir les rapports'],
            ['name' => 'EXPORT_DATA', 'description' => 'Exporter les données'],
            ['name' => 'IMPORT_DATA', 'description' => 'Importer des données'],
            ['name' => 'ACCESS_API', 'description' => 'Accès à l\'API'],
        ];

        foreach ($permissions as $permissionData) {
            $permission = new Permission();
            $permission->setName($permissionData['name']);
            $permission->setDescription($permissionData['description']);
            $manager->persist($permission);
        }

        $manager->flush();
    }
}

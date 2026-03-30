<?php 

namespace App\Service;

use App\Repository\UserRepository;

class UsernameGenerator {
    private UserRepository $repo;

    public function __construct(UserRepository $repo_) {
        $this->repo = $repo_;
    }

    public function generate(
        string $firstname,
        string $lastname
    ): string
    {
        $generatedUsername = strtolower($lastname.'.'.$firstname[0]);

        // Vérifier si le nom d'utilisateur existe déjà
        /* while ($this->repo->findOneBy(['username' => $generatedUsername])) {
            $generatedUsername .= '';
        }
        */
        return $generatedUsername;
    
    }
}
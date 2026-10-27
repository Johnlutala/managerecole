<?php

namespace App\Service;

use App\Repository\UserRepository;

final class UsernameGenerator
{
    public function __construct(
        private readonly UserRepository $repo
    ) {}

    /**
     * Génère un nom d'utilisateur au format :
     * prénom + "." + première lettre du nom.
     *
     * Exemple : John KISINA => john.k
     * En cas de doublon : john.k2, john.k3, etc.
     */
    public function generate(string $firstname, string $lastname): string
    {

        $firstname = mb_strtolower(trim($firstname));
        $lastname = mb_strtolower(trim($lastname));

        if ($firstname === '' || $lastname === '') {
            throw new \InvalidArgumentException(
                'Le prénom et le nom sont obligatoires pour générer un nom d’utilisateur.'
            );
        }

        // Format : prenom.premiere_lettre_du_nom
        $baseUsername = $firstname . '.' . mb_substr($lastname, 0, 1);

        $generatedUsername = $baseUsername;
        $suffix = 2;

        // Vérifier si le nom d'utilisateur existe déjà.
        while ($this->repo->findOneBy([
            'username' => $generatedUsername,
        ]) !== null) {
            $generatedUsername = $baseUsername . $suffix;
            ++$suffix;
        }

        return $generatedUsername;
    }
}

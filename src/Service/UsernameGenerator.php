<?php

namespace App\Service;

use App\Repository\UserRepository;

final class UsernameGenerator
{
    public function __construct(private readonly UserRepository $repo) {}

    public function generate(string $firstname, string $lastname): string
    {
        $firstname = trim($firstname);
        $lastname = trim($lastname);

        if ($firstname === '' || $lastname === '') {
            throw new \InvalidArgumentException('Firstname and lastname are required to generate a username.');
        }

        // Example: KISINA John => john.k
        $baseUsername = mb_strtolower($firstname) . '.' . mb_strtolower(mb_substr($lastname, 0, 1));
        $generatedUsername = $baseUsername;
        $suffix = 2;

        while ($this->repo->findOneBy(['username' => $generatedUsername]) !== null) {
            $generatedUsername = $baseUsername . $suffix++;
        }

        return $generatedUsername;
    }
}

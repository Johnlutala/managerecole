<?php 

namespace App\Dto\Api;

use Symfony\Component\Validator\Constraints as Assert;

class LoginPayload
{
    #[Assert\NotBlank(message: "Identifiant obligatoire")]
    private string $identifier;

    #[Assert\NotBlank(message: "Mot de passe obligatoire")]
    #[Assert\Length(min: 8, minMessage: "Le mot de passe doit contenir au moins huit caractères")]
    private string $password;

    public function __construct(
        string $identifier_,
        string $password_,
    ) {
        $this->identifier = $identifier_;
        $this->password = $password_;
    }

}
<?php

namespace App\Dto\Api;

use Symfony\Component\Validator\Constraints as Assert;

class UserCreationPayload
{
    #[Assert\NotBlank(message: 'Prenom obligatoire')]
    #[Assert\Length(min: 2, minMessage: 'Le prenom doit contenir au moins deux caracteres')]
    #[Assert\Regex(pattern: '/^[a-zA-Z\\s-]+$/', message: 'Le prenom ne doit contenir que des lettres, des tirets et des espaces')]
    private string $firstname;

    #[Assert\NotBlank(message: 'Nom obligatoire')]
    #[Assert\Length(min: 2, minMessage: 'Le nom doit contenir au moins deux caracteres')]
    #[Assert\Regex(pattern: '/^[a-zA-Z\\s-]+$/', message: 'Le nom ne doit contenir que des lettres, des tirets et des espaces')]
    private string $lastname;

    #[Assert\NotBlank(message: 'Adresse mail obligatoire')]
    #[Assert\Length(min: 6, minMessage: 'L\'adresse mail doit contenir au moins six caracteres')]
    #[Assert\Email(message: 'L\'adresse mail n\'est pas valide')]
    private string $email;

    #[Assert\NotBlank(message: 'Code marchand obligatoire')]
    private string $merchant;

    public function __construct(string $firstname, string $lastname, string $email, string $merchant)
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->merchant = $merchant;
    }
}
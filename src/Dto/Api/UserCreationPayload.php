<?php 

namespace App\Dto\Api;

use Symfony\Component\Validator\Constraints as Assert;

class UserCreationPayload
{
    #[Assert\NotBlank(message: "Prénom obligatoire")]
    #[Assert\Length(min: 2, minMessage: "Le prénom doit contenir au moins deux caractères")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z\s-]+$/",
        message: "Le prénom ne doit contenir que des lettres, des tirets et des espaces"
    )]
    private string $firstname;

    #[Assert\NotBlank(message: "Nom obligatoire")]
    #[Assert\Length(min: 2, minMessage: "Le nom doit contenir au moins deux caractères")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z\s-]+$/",
        message: "Le nom ne doit contenir que des lettres, des tirets et des espaces"
    )]
    private string $lastname;

    #[Assert\NotBlank(message: "Addresse mail obligatoire")]
    #[Assert\Length(min: 6, minMessage: "L'adresse mail doit contenir au moins six caractères")]
    #[Assert\Email(message: "L'adresse mail n'est pas valide")]
    private string $email;

    #[Assert\NotBlank(message: "Mot de passe obligatoire")]
    #[Assert\Length(min: 8, minMessage: "Le mot de passe doit contenir au moins huit caractères")] 
    #[Assert\Regex(
        pattern: "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/",
        message: "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial"
    )]
    private string $password;


    #[Assert\NotBlank(message: "Code marchand obligatoire")]
    private string $merchant;

    public function __construct(
        string $firstname_,
        string $lastname_,
        string $email_,
        string $password_,
        string $merchant_
    ) {
        $this->firstname = $firstname_;
        $this->lastname = $lastname_;
        $this->email = $email_;
        $this->password = $password_;
        $this->merchant = $merchant_;
    }

}
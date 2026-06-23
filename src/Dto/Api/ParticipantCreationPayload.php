<?php 

namespace App\Dto\Api;

use Symfony\Component\Validator\Constraints as Assert;

class ParticipantCreationPayload
{
    #[Assert\NotBlank(message: "Prénom obligatoire")]
    #[Assert\Length(min: 2, minMessage: "Le prénom doit contenir au moins deux caractères")]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s-]+$/",
        message: "Le prénom ne doit contenir que des lettres, des tirets et des espaces"
    )]
    private string $firstname;

    #[Assert\NotBlank(message: "Nom obligatoire")]
    #[Assert\Length(min: 2, minMessage: "Le nom doit contenir au moins deux caractères")]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s-]+$/",
        message: "Le nom ne doit contenir que des lettres, des tirets et des espaces"
    )]
    private string $lastname;
    
    
    #[Assert\Length(min: 2, minMessage: "Le nom doit contenir au moins deux caractères")]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s-]+$/",
        message: "Le nom de famille ne doit contenir que des lettres, des tirets et des espaces"
    )]
    private string $middlename;

    #[Assert\NotBlank(message: "Genre obligatoire")]
    #[Assert\Choice(
        choices: ['Male', 'Female'],
        message: "Le genre doit être 'Male' ou 'Female'"
    )]
    private string $gender;

    #[Assert\NotBlank(message: "Téléphone obligatoire")]
    #[Assert\Length(
        min: 10, 
        max: 15, 
        minMessage: "Le numéro de téléphone doit contenir 10 chiffres", 
        maxMessage: "Le numéro de téléphone doit contenir 10 chiffres"
    )]
    #[Assert\Regex(
        pattern: "/^243\d{9}$/",
        message: "Le numéro de téléphone doit commencer par 243 suivi de 9 chiffres"
    )]
    private string $phone;

    #[Assert\NotBlank(message: "Téléphone obligatoire")]
    #[Assert\Length(
        min: 10, 
        max: 15, 
        minMessage: "Le numéro de téléphone doit contenir 10 chiffres", 
        maxMessage: "Le numéro de téléphone doit contenir 10 chiffres"
    )]
    #[Assert\Regex(
        pattern: "/^243\d{9}$/",
        message: "Le numéro de téléphone doit commencer par 243 suivi de 9 chiffres"
    )]
    private string $phoneEMoney;

    #[Assert\NotBlank(message: "Données biométriques obligatoires")]
    #[Assert\All([
        new Assert\Collection(
            fields: [
                'pos' => [
                    new Assert\NotBlank(message: "Position obligatoire"),
                    new Assert\Range(
                        min: 1,
                        max: 10,
                        notInRangeMessage: "La position doit être entre 1 et 10"
                    )
                ],
                'data' => new Assert\NotBlank(message: "Données obligatoire"),
            ],
            allowExtraFields: false,
            missingFieldsMessage: "Le champ {{ field }} est manquant dans l'entrée biométrique",
        )
    ])]
    private array $biometrics;


    private ?string $organization;
    private ?string $grade;


    public function __construct(
        string $firstname_,
        string $lastname_,
        string $gender_,
        string $middlename,
        string $phone_,
        string $phoneEMoney_,
        array $biometrics_,
        string $organization_,
        string $grade_,
    ) {
        $this->firstname = $firstname_;
        $this->lastname = $lastname_;
        $this->gender = $gender_;
        $this->middlename = $middlename;
        $this->phone = $phone_;
        $this->phoneEMoney = $phoneEMoney_;
        $this->biometrics = $biometrics_;
        $this->organization = $organization_;
        $this->grade = $grade_;
    }

}
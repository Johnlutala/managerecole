<?php 

namespace App\Dto\Api;

use Symfony\Component\Validator\Constraints as Assert;

class WorkshopPayload
{
    #[Assert\NotBlank(message: "Nom de l'atelier obligatoire")]
    private string $name;

    private string $description;

    #[Assert\NotBlank(message: "Montant quotidien obligatoire")]
    #[Assert\Positive(message: "Le montant quotidien doit être un nombre positif")]
    private float $dailyAmount;

    #[Assert\NotBlank(message: "Devise obligatoire")]
    #[Assert\Choice(
        choices: ["USD", "CDF"],
        message: "Choisir une devise valide (USD, CDF)"
    )]
    private string $currency;

    #[Assert\NotBlank(message: "Dates obligatoires")]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
        new Assert\Regex(
            pattern: "/^\d{2}\/\d{2}\/\d{4}$/",
            message: "Chaque date doit être au format DD/MM/YYYY"
        )
    ])]
    private array $dates;

    public function __construct(
        string $name,
        string $description,
        float $dailyAmount,
        string $currency,
        array $dates
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->dailyAmount = $dailyAmount;
        $this->currency = $currency;
        $this->dates = $dates;
    }

}
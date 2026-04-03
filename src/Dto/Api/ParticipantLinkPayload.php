<?php 

namespace App\Dto\Api;

use Symfony\Component\Validator\Constraints as Assert;

class ParticipantLinkPayload
{
    #[Assert\NotBlank(message: "ID du participant obligatoire")]
    #[Assert\Positive(message: "L'ID du participant doit être un nombre positif")]
    private string $participantId;

    #[Assert\NotBlank(message: "ID de l'atelier obligatoire")]
    #[Assert\Positive(message: "L'ID de l'atelier doit être un nombre positif")]
    private string $workshopId;


    public function __construct(
        string $participantId_,
        string $workshopId_,
    ) {
        $this->participantId = $participantId_;
        $this->workshopId = $workshopId_;
    }

}
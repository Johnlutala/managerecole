<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
    use EntityTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    private ?Workshop $workshop = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    private ?WorkshopDay $day = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    private ?Participant $participant = null;

    #[ORM\Column]
    private ?bool $isPresent = null;


    public function __construct()
    {
        $this->code = uniqid();        
        $this->isPresent = false;
        $this->enabled = true;
        $this->deleted = false;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkshop(): ?Workshop
    {
        return $this->workshop;
    }

    public function setWorkshop(?Workshop $workshop): static
    {
        $this->workshop = $workshop;

        return $this;
    }

    public function getDay(): ?WorkshopDay
    {
        return $this->day;
    }

    public function setDay(?WorkshopDay $day): static
    {
        $this->day = $day;

        return $this;
    }

    public function getParticipant(): ?Participant
    {
        return $this->participant;
    }

    public function setParticipant(?Participant $participant): static
    {
        $this->participant = $participant;

        return $this;
    }

    public function isPresent(): ?bool
    {
        return $this->isPresent;
    }

    public function setIsPresent(bool $isPresent): static
    {
        $this->isPresent = $isPresent;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\PresenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PresenceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Presence
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureArrivee = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureDepart = null;

    #[ORM\ManyToOne(inversedBy: 'presences')]
    private ?Eleve $eleve = null;

    #[ORM\ManyToOne(inversedBy: 'presences')]
    private ?Classe $classe = null;

    public function __construct()
    {
        $this->code = uniqid('PRE', true);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getHeureArrivee(): ?\DateTime
    {
        return $this->heureArrivee;
    }

    public function setHeureArrivee(?\DateTime $heureArrivee): static
    {
        $this->heureArrivee = $heureArrivee;

        return $this;
    }

    public function getHeureDepart(): ?\DateTime
    {
        return $this->heureDepart;
    }

    public function setHeureDepart(?\DateTime $heureDepart): static
    {
        $this->heureDepart = $heureDepart;

        return $this;
    }

    public function getEleve(): ?Eleve
    {
        return $this->eleve;
    }

    public function setEleve(?Eleve $eleve): static
    {
        $this->eleve = $eleve;

        return $this;
    }

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): static
    {
        $this->classe = $classe;

        return $this;
    }
}

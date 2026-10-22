<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\PresencePersonnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PresencePersonnelRepository::class)]
#[ORM\Table(name: 'presence_personnel', uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_presence_personnel_jour', columns: ['professeur_id', 'user_id', 'date'])])]
#[ORM\HasLifecycleCallbacks]
class PresencePersonnel
{
    use EntityTrait;
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;
    #[ORM\Column(length: 30)]
    private ?string $statut = null;
    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureArrivee = null;
    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureDepart = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observation = null;
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Professeur $professeur = null;
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $personnel = null;
    public function getId(): ?int { return $this->id; }
    public function getDate(): ?\DateTime { return $this->date; }
    public function setDate(\DateTime $date): static { $this->date = $date; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getHeureArrivee(): ?\DateTime { return $this->heureArrivee; }
    public function setHeureArrivee(?\DateTime $value): static { $this->heureArrivee = $value; return $this; }
    public function getHeureDepart(): ?\DateTime { return $this->heureDepart; }
    public function setHeureDepart(?\DateTime $value): static { $this->heureDepart = $value; return $this; }
    public function getObservation(): ?string { return $this->observation; }
    public function setObservation(?string $value): static { $this->observation = $value; return $this; }
    public function getProfesseur(): ?Professeur { return $this->professeur; }
    public function setProfesseur(?Professeur $value): static { $this->professeur = $value; return $this; }
    public function getPersonnel(): ?User { return $this->personnel; }
    public function setPersonnel(?User $value): static { $this->personnel = $value; return $this; }
}

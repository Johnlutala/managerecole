<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\PresenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PresenceRepository::class)]
#[ORM\Table(name: 'presence', uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_presence_eleve_jour', columns: ['eleve_id', 'date'])])]
#[ORM\HasLifecycleCallbacks]
class Presence
{
    use EntityTrait;
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::DATE_MUTABLE)] private ?\DateTime $date = null;
    #[ORM\Column(length: 50)] private ?string $statut = null;
    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)] private ?\DateTime $heureArrivee = null;
    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)] private ?\DateTime $heureDepart = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $observation = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTime $validatedAt = null;
    #[ORM\ManyToOne] #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')] private ?User $validatedBy = null;
    #[ORM\ManyToOne(inversedBy: 'presences')] private ?Eleve $eleve = null;
    #[ORM\ManyToOne(inversedBy: 'presences')] private ?Classe $classe = null;
    public function getId(): ?int { return $this->id; }
    public function getDate(): ?\DateTime { return $this->date; } public function setDate(\DateTime $v): static { $this->date=$v; return $this; }
    public function getStatut(): ?string { return $this->statut; } public function setStatut(string $v): static { $this->statut=$v; return $this; }
    public function getHeureArrivee(): ?\DateTime { return $this->heureArrivee; } public function setHeureArrivee(?\DateTime $v): static { $this->heureArrivee=$v; return $this; }
    public function getHeureDepart(): ?\DateTime { return $this->heureDepart; } public function setHeureDepart(?\DateTime $v): static { $this->heureDepart=$v; return $this; }
    public function getObservation(): ?string { return $this->observation; } public function setObservation(?string $v): static { $this->observation=$v; return $this; }
    public function getValidatedAt(): ?\DateTime { return $this->validatedAt; } public function setValidatedAt(?\DateTime $v): static { $this->validatedAt=$v; return $this; }
    public function getValidatedBy(): ?User { return $this->validatedBy; } public function setValidatedBy(?User $v): static { $this->validatedBy=$v; return $this; }
    public function getEleve(): ?Eleve { return $this->eleve; } public function setEleve(?Eleve $v): static { $this->eleve=$v; return $this; }
    public function getClasse(): ?Classe { return $this->classe; } public function setClasse(?Classe $v): static { $this->classe=$v; return $this; }
}

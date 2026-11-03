<?php

namespace App\Entity;

use App\Repository\ProfesseurMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfesseurMessageRepository::class)]
#[ORM\Table(name: 'message_professeur')]
#[ORM\Index(name: 'IDX_MESSAGE_PROFESSEUR_RECIPIENT_READ', columns: ['professeur_id', 'lu_le'])]
class MessageProfesseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Professeur $professeur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $expediteur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Cours $cours = null;

    #[ORM\Column(length: 120)]
    private string $expediteurNom = '';

    #[ORM\Column(length: 180)]
    private string $expediteurEmail = '';

    #[ORM\Column(length: 180)]
    private string $sujet = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $contenu = '';

    #[ORM\Column]
    private \DateTimeImmutable $envoyeLe;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $luLe = null;

    public function __construct()
    {
        $this->envoyeLe = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfesseur(): ?Professeur
    {
        return $this->professeur;
    }

    public function setProfesseur(Professeur $professeur): static
    {
        $this->professeur = $professeur;

        return $this;
    }

    public function getExpediteur(): ?User
    {
        return $this->expediteur;
    }

    public function setExpediteur(User $expediteur): static
    {
        $this->expediteur = $expediteur;

        return $this;
    }

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): static
    {
        $this->cours = $cours;

        return $this;
    }

    public function getExpediteurNom(): string
    {
        return $this->expediteurNom;
    }

    public function setExpediteurNom(string $expediteurNom): static
    {
        $this->expediteurNom = $expediteurNom;

        return $this;
    }

    public function getExpediteurEmail(): string
    {
        return $this->expediteurEmail;
    }

    public function setExpediteurEmail(string $expediteurEmail): static
    {
        $this->expediteurEmail = $expediteurEmail;

        return $this;
    }

    public function getSujet(): string
    {
        return $this->sujet;
    }

    public function setSujet(string $sujet): static
    {
        $this->sujet = $sujet;

        return $this;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getEnvoyeLe(): \DateTimeImmutable
    {
        return $this->envoyeLe;
    }

    public function getLuLe(): ?\DateTimeImmutable
    {
        return $this->luLe;
    }

    public function setLuLe(?\DateTimeImmutable $luLe): static
    {
        $this->luLe = $luLe;

        return $this;
    }
}

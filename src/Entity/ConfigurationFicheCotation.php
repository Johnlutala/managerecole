<?php

namespace App\Entity;

use App\Repository\ConfigurationFicheCotationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigurationFicheCotationRepository::class)]
#[ORM\Table(name: 'configuration_fiche_cotation')]
class ConfigurationFicheCotation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?Ecole $ecole = null;

    #[ORM\Column(length: 20)]
    private ?string $structure = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEcole(): ?Ecole
    {
        return $this->ecole;
    }

    public function setEcole(?Ecole $ecole): static
    {
        $this->ecole = $ecole;

        return $this;
    }

    public function getStructure(): ?string
    {
        return $this->structure;
    }

    public function setStructure(string $structure): static
    {
        $this->structure = $structure;

        return $this;
    }
}

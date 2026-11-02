<?php

namespace App\Entity;

use App\Repository\AutorisationCotationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AutorisationCotationRepository::class)]
#[ORM\Table(name: 'autorisation_cotation')]
#[ORM\UniqueConstraint(name: 'UNIQ_AUTORISATION_COTATION_PERIODE', columns: ['structure', 'periode'])]
class AutorisationCotation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $structure;

    #[ORM\Column(length: 100)]
    private string $periode;

    #[ORM\Column]
    private bool $autorisee = false;

    public function __construct(string $structure, string $periode)
    {
        $this->structure = $structure;
        $this->periode = $periode;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStructure(): string
    {
        return $this->structure;
    }

    public function getPeriode(): string
    {
        return $this->periode;
    }

    public function isAutorisee(): bool
    {
        return $this->autorisee;
    }

    public function setAutorisee(bool $autorisee): static
    {
        $this->autorisee = $autorisee;

        return $this;
    }
}

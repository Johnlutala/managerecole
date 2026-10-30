<?php

namespace App\Entity;

use App\Repository\ConfigurationBulletinRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigurationBulletinRepository::class)]
class ConfigurationBulletin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'brouillon';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'configurationBulletins')]
    private ?ModeleBulletin $modeleBulletin = null;

    #[ORM\ManyToOne(inversedBy: 'configurationBulletins')]
    private ?Ecole $ecole = null;

    #[ORM\ManyToOne(inversedBy: 'configurationBulletins')]
    private ?AnneeScolaire $anneeScolaire = null;

    #[ORM\ManyToOne(inversedBy: 'configurationBulletins')]
    private ?Classe $classe = null;

    #[ORM\ManyToOne(inversedBy: 'configurationBulletins')]
    private ?Option $options = null;

    /**
     * @var Collection<int, Evaluation>
     */
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'configurationBulletin')]
    private Collection $evaluations;

    public function __construct()
    {
        $this->evaluations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModeleBulletin(): ?ModeleBulletin
    {
        return $this->modeleBulletin;
    }

    public function setModeleBulletin(?ModeleBulletin $modeleBulletin): static
    {
        $this->modeleBulletin = $modeleBulletin;

        return $this;
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

    public function getAnneeScolaire(): ?AnneeScolaire
    {
        return $this->anneeScolaire;
    }

    public function setAnneeScolaire(?AnneeScolaire $anneeScolaire): static
    {
        $this->anneeScolaire = $anneeScolaire;

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

    public function getOptions(): ?Option
    {
        return $this->options;
    }

    public function setOptions(?Option $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setConfigurationBulletin($this);
        }

        return $this;
    }

     public function removeEvaluation(Evaluation $evaluation): static
    {
        $this->evaluations->removeElement($evaluation);

        return $this;
    }
}

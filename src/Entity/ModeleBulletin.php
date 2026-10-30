<?php

namespace App\Entity;

use App\Repository\ModeleBulletinRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModeleBulletinRepository::class)]
class ModeleBulletin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'actif';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, ConfigurationBulletin>
     */
    #[ORM\OneToMany(
        targetEntity: ConfigurationBulletin::class,
        mappedBy: 'modeleBulletin'
    )]
    private Collection $configurationBulletins;

    public function __construct()
    {
        $this->configurationBulletins = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    /**
     * @return Collection<int, ConfigurationBulletin>
     */
    public function getConfigurationBulletins(): Collection
    {
        return $this->configurationBulletins;
    }

    public function addConfigurationBulletin(
        ConfigurationBulletin $configurationBulletin
    ): static {
        if (!$this->configurationBulletins->contains($configurationBulletin)) {
            $this->configurationBulletins->add($configurationBulletin);
            $configurationBulletin->setModeleBulletin($this);
        }

        return $this;
    }

    public function removeConfigurationBulletin(
        ConfigurationBulletin $configurationBulletin
    ): static {
        if ($this->configurationBulletins->removeElement($configurationBulletin)) {
            if ($configurationBulletin->getModeleBulletin() === $this) {
                $configurationBulletin->setModeleBulletin(null);
            }
        }

        return $this;
    }
}

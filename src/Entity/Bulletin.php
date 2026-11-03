<?php

namespace App\Entity;

use App\Repository\BulletinRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BulletinRepository::class)]
#[ORM\Table(name: 'bulletin')]
#[ORM\Index(name: 'IDX_BULLETIN_ELEVE', columns: ['eleve_id'])]
#[ORM\Index(name: 'IDX_BULLETIN_CONFIGURATION', columns: ['configuration_bulletin_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_BULLETIN_ELEVE_CONFIGURATION', columns: ['eleve_id', 'configuration_bulletin_id'])]
class Bulletin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Eleve $eleve = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ConfigurationBulletin $configurationBulletin = null;

    #[ORM\Column]
    private int $totalCotes = 0;

    #[ORM\Column]
    private int $totalBaremes = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $pourcentage = '0.00';

    #[ORM\Column(length: 20)]
    private string $decision = 'ajourne';

    /**
     * @var array<int, array{cours: string, evaluation: string, cote: int, bareme: int}>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $details = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEleve(): ?Eleve
    {
        return $this->eleve;
    }

    public function setEleve(Eleve $eleve): static
    {
        $this->eleve = $eleve;

        return $this;
    }

    public function getConfigurationBulletin(): ?ConfigurationBulletin
    {
        return $this->configurationBulletin;
    }

    public function setConfigurationBulletin(ConfigurationBulletin $configurationBulletin): static
    {
        $this->configurationBulletin = $configurationBulletin;

        return $this;
    }

    public function getTotalCotes(): int
    {
        return $this->totalCotes;
    }

    public function setTotalCotes(int $totalCotes): static
    {
        $this->totalCotes = $totalCotes;

        return $this;
    }

    public function getTotalBaremes(): int
    {
        return $this->totalBaremes;
    }

    public function setTotalBaremes(int $totalBaremes): static
    {
        $this->totalBaremes = $totalBaremes;

        return $this;
    }

    public function getPourcentage(): string
    {
        return $this->pourcentage;
    }

    public function setPourcentage(string $pourcentage): static
    {
        $this->pourcentage = $pourcentage;

        return $this;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function setDecision(string $decision): static
    {
        $this->decision = $decision;

        return $this;
    }

    /**
     * @return array<int, array{cours: string, evaluation: string, cote: int, bareme: int}>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param array<int, array{cours: string, evaluation: string, cote: int, bareme: int}> $details
     */
    public function setDetails(array $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

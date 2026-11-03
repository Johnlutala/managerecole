<?php

namespace App\Entity;

use App\Repository\BulletinAnnuelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BulletinAnnuelRepository::class)]
#[ORM\Table(name: 'bulletin_annuel')]
#[ORM\UniqueConstraint(name: 'UNIQ_BULLETIN_ANNUEL_ELEVE_ANNEE', columns: ['eleve_id', 'annee_scolaire_id'])]
#[ORM\Index(name: 'IDX_BULLETIN_ANNUEL_CLASSE_ANNEE', columns: ['classe_id', 'annee_scolaire_id'])]
#[ORM\Index(name: 'IDX_BULLETIN_ANNUEL_ECOLE', columns: ['ecole_id'])]
#[ORM\Index(name: 'IDX_BULLETIN_ANNUEL_OPTION', columns: ['option_id'])]
#[ORM\Index(name: 'IDX_BULLETIN_ANNUEL_ANNEE', columns: ['annee_scolaire_id'])]
class BulletinAnnuel
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
    private ?Classe $classe = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ecole $ecole = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AnneeScolaire $anneeScolaire = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Option $option = null;

    #[ORM\Column]
    private int $totalCotes = 0;

    #[ORM\Column]
    private int $totalBaremes = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private string $pourcentage = '0.00';

    #[ORM\Column(length: 20)]
    private string $decision = 'ajourne';

    /**
     * @var array<int, array{
     *     cours_id?: int|null,
     *     cours: string,
     *     domaine: ?string,
     *     evaluations: array<int, array{periode: string, libelle?: string, semestre?: string, cote: int, bareme: int, provisoire?: bool, autorisee?: bool}>,
     *     total_cotes: int,
     *     total_baremes: int,
     *     pourcentage: string
     * }>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $details = [];

    #[ORM\Column]
    private \DateTimeImmutable $calculeLe;

    public function __construct()
    {
        $this->calculeLe = new \DateTimeImmutable();
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

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(Classe $classe): static
    {
        $this->classe = $classe;

        return $this;
    }

    public function getEcole(): ?Ecole
    {
        return $this->ecole;
    }

    public function setEcole(Ecole $ecole): static
    {
        $this->ecole = $ecole;

        return $this;
    }

    public function getAnneeScolaire(): ?AnneeScolaire
    {
        return $this->anneeScolaire;
    }

    public function setAnneeScolaire(AnneeScolaire $anneeScolaire): static
    {
        $this->anneeScolaire = $anneeScolaire;

        return $this;
    }

    public function getOption(): ?Option
    {
        return $this->option;
    }

    public function setOption(?Option $option): static
    {
        $this->option = $option;

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
     * @return array<int, array{
     *     cours: string,
     *     domaine: ?string,
     *     evaluations: array<int, array{periode: string, cote: int, bareme: int, provisoire?: bool}>,
     *     total_cotes: int,
     *     total_baremes: int,
     *     pourcentage: string
     * }>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param array<int, array{
     *     cours_id?: int|null,
     *     cours: string,
     *     evaluations: array<int, array{periode: string, libelle?: string, semestre?: string, cote: int, bareme: int, provisoire?: bool, autorisee?: bool}>,
     *     total_cotes: int,
     *     total_baremes: int,
     *     pourcentage: string
     * }> $details
     */
    public function setDetails(array $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getCalculeLe(): \DateTimeImmutable
    {
        return $this->calculeLe;
    }

    public function setCalculeLe(\DateTimeImmutable $calculeLe): static
    {
        $this->calculeLe = $calculeLe;

        return $this;
    }
}

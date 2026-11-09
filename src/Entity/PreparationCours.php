<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\PreparationCoursRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PreparationCoursRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uniq_preparation_prof_cours_numero', columns: ['professeur_id', 'cours_id', 'numero'])]
class PreparationCours
{
    use EntityTrait;

    public const BROUILLON = 'brouillon';
    public const SOUMISE = 'soumise';
    public const VALIDEE = 'validee';
    public const A_CORRIGER = 'a_corriger';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $numero = 1;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Professeur $professeur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cours $cours = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTime $dateCours;

    #[ORM\Column(length: 20)]
    private string $jour = 'Lundi';

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureFin = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le sujet de la leçon est obligatoire.')]
    #[Assert\Length(max: 255)]
    private string $sujet = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objectifGeneral = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objectifsSpecifiques = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $matiere = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $methode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $materielDidactique = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $activitesInitialesEnseignant = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $activitesInitialesEleves = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $activitesPrincipalesEnseignant = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $activitesPrincipalesEleves = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $activitesFinalesEnseignant = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $activitesFinalesEleves = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $evaluation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $devoir = null;

    #[ORM\Column(length: 20)]
    private string $statut = self::BROUILLON;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireDirection = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $soumiseLe = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $traiteeLe = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $traiteePar = null;

    public function __construct()
    {
        $this->dateCours = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getProfesseur(): ?Professeur
    {
        return $this->professeur;
    }

    public function setProfesseur(?Professeur $professeur): static
    {
        $this->professeur = $professeur;

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

    public function getDateCours(): \DateTime
    {
        return $this->dateCours;
    }

    public function setDateCours(\DateTime $dateCours): static
    {
        $this->dateCours = $dateCours;

        return $this;
    }

    public function getJour(): string
    {
        return $this->jour;
    }

    public function setJour(string $jour): static
    {
        $this->jour = $jour;

        return $this;
    }

    public function getHeureDebut(): ?\DateTime
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?\DateTime $heureDebut): static
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTime
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTime $heureFin): static
    {
        $this->heureFin = $heureFin;

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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getObjectifGeneral(): ?string
    {
        return $this->objectifGeneral;
    }

    public function setObjectifGeneral(?string $objectifGeneral): static
    {
        $this->objectifGeneral = $objectifGeneral;

        return $this;
    }

    public function getObjectifsSpecifiques(): ?string
    {
        return $this->objectifsSpecifiques;
    }

    public function setObjectifsSpecifiques(?string $objectifsSpecifiques): static
    {
        $this->objectifsSpecifiques = $objectifsSpecifiques;

        return $this;
    }

    public function getMatiere(): ?string
    {
        return $this->matiere;
    }

    public function setMatiere(?string $matiere): static
    {
        $this->matiere = $matiere;

        return $this;
    }

    public function getMethode(): ?string
    {
        return $this->methode;
    }

    public function setMethode(?string $methode): static
    {
        $this->methode = $methode;

        return $this;
    }

    public function getMaterielDidactique(): ?string
    {
        return $this->materielDidactique;
    }

    public function setMaterielDidactique(?string $materielDidactique): static
    {
        $this->materielDidactique = $materielDidactique;

        return $this;
    }

    public function getActivitesInitialesEnseignant(): ?string
    {
        return $this->activitesInitialesEnseignant;
    }

    public function setActivitesInitialesEnseignant(?string $value): static
    {
        $this->activitesInitialesEnseignant = $value;

        return $this;
    }

    public function getActivitesInitialesEleves(): ?string
    {
        return $this->activitesInitialesEleves;
    }

    public function setActivitesInitialesEleves(?string $value): static
    {
        $this->activitesInitialesEleves = $value;

        return $this;
    }

    public function getActivitesPrincipalesEnseignant(): ?string
    {
        return $this->activitesPrincipalesEnseignant;
    }

    public function setActivitesPrincipalesEnseignant(?string $value): static
    {
        $this->activitesPrincipalesEnseignant = $value;

        return $this;
    }

    public function getActivitesPrincipalesEleves(): ?string
    {
        return $this->activitesPrincipalesEleves;
    }

    public function setActivitesPrincipalesEleves(?string $value): static
    {
        $this->activitesPrincipalesEleves = $value;

        return $this;
    }

    public function getActivitesFinalesEnseignant(): ?string
    {
        return $this->activitesFinalesEnseignant;
    }

    public function setActivitesFinalesEnseignant(?string $value): static
    {
        $this->activitesFinalesEnseignant = $value;

        return $this;
    }

    public function getActivitesFinalesEleves(): ?string
    {
        return $this->activitesFinalesEleves;
    }

    public function setActivitesFinalesEleves(?string $value): static
    {
        $this->activitesFinalesEleves = $value;

        return $this;
    }

    public function getEvaluation(): ?string
    {
        return $this->evaluation;
    }

    public function setEvaluation(?string $evaluation): static
    {
        $this->evaluation = $evaluation;

        return $this;
    }

    public function getDevoir(): ?string
    {
        return $this->devoir;
    }

    public function setDevoir(?string $devoir): static
    {
        $this->devoir = $devoir;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCommentaireDirection(): ?string
    {
        return $this->commentaireDirection;
    }

    public function setCommentaireDirection(?string $commentaire): static
    {
        $this->commentaireDirection = $commentaire;

        return $this;
    }

    public function getSoumiseLe(): ?\DateTimeImmutable
    {
        return $this->soumiseLe;
    }

    public function setSoumiseLe(?\DateTimeImmutable $soumiseLe): static
    {
        $this->soumiseLe = $soumiseLe;

        return $this;
    }

    public function getTraiteeLe(): ?\DateTimeImmutable
    {
        return $this->traiteeLe;
    }

    public function setTraiteeLe(?\DateTimeImmutable $traiteeLe): static
    {
        $this->traiteeLe = $traiteeLe;

        return $this;
    }

    public function getTraiteePar(): ?User
    {
        return $this->traiteePar;
    }

    public function setTraiteePar(?User $traiteePar): static
    {
        $this->traiteePar = $traiteePar;

        return $this;
    }

    #[Assert\Callback]
    public function validateHoraire(ExecutionContextInterface $context): void
    {
        if (($this->heureDebut === null) !== ($this->heureFin === null)) {
            $context->buildViolation('Veuillez renseigner les deux heures ou laisser l’horaire vide.')
                ->atPath('heureFin')
                ->addViolation();

            return;
        }

        if ($this->heureDebut !== null && $this->heureFin <= $this->heureDebut) {
            $context->buildViolation('L’heure de fin doit être postérieure à l’heure de début.')
                ->atPath('heureFin')
                ->addViolation();
        }
    }
}

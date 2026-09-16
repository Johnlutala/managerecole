<?php

namespace App\Entity;

use App\Repository\InscriptionEleveRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InscriptionEleveRepository::class)]
#[ORM\HasLifecycleCallbacks]
class InscriptionEleve
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    private ?string $postnom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 20)]
    private ?string $sexe = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateNaissance = null;

    #[ORM\Column(length: 50)]
    private ?string $lieuNaissance = null;

    #[ORM\Column(length: 50, unique:true, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeInscription = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(length: 50)]
    private ?string $nomParent = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $postnomParent = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $prenomParent = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telephoneParent = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $emailParent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseParent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $professionParent = null;

    #[ORM\Column(length: 50)]
    private ?string $lienParental = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptionEleves')]
    private ?Ecole $etablissement = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptionEleves')]
    private ?AnneeScolaire $anneeScolaire = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptionEleves')]
    private ?Section $section = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptionEleves')]
    private ?Classe $classe = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptionEleves')]
    private ?Option $options = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateValidation = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $motifRejet = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $enabled = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $deleted = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'inscriptionEleves')]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: true)]
    private ?User $createdBy = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();

        if ($this->deleted === null) {
            $this->deleted = false;
        }

        if ($this->enabled === null) {
            $this->enabled = true;
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }


    // ========================================
    // CODE
    // ========================================

    protected function generateCode(string $nom): string
    {
        $words = preg_split('/\s+/', trim($nom));

        $initials = '';

        foreach ($words as $word) {
            if ($word !== '') {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return $initials . str_pad(
            (string) random_int(0, 999),
            3,
            '0',
            STR_PAD_LEFT
        );
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

    public function getPostnom(): ?string
    {
        return $this->postnom;
    }

    public function setPostnom(string $postnom): static
    {
        $this->postnom = $postnom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTime $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieuNaissance;
    }

    public function setLieuNaissance(string $lieuNaissance): static
    {
        $this->lieuNaissance = $lieuNaissance;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

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

    public function getTypeInscription(): ?string
    {
        return $this->typeInscription;
    }

    public function setTypeInscription(?string $typeInscription): static
    {
        $this->typeInscription = $typeInscription;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getNomParent(): ?string
    {
        return $this->nomParent;
    }

    public function setNomParent(string $nomParent): static
    {
        $this->nomParent = $nomParent;

        return $this;
    }

    public function getPostnomParent(): ?string
    {
        return $this->postnomParent;
    }

    public function setPostnomParent(?string $postnomParent): static
    {
        $this->postnomParent = $postnomParent;

        return $this;
    }

    public function getPrenomParent(): ?string
    {
        return $this->prenomParent;
    }

    public function setPrenomParent(?string $prenomParent): static
    {
        $this->prenomParent = $prenomParent;

        return $this;
    }

    public function getTelephoneParent(): ?string
    {
        return $this->telephoneParent;
    }

    public function setTelephoneParent(?string $telephoneParent): static
    {
        $this->telephoneParent = $telephoneParent;

        return $this;
    }

    public function getEmailParent(): ?string
    {
        return $this->emailParent;
    }

    public function setEmailParent(?string $emailParent): static
    {
        $this->emailParent = $emailParent;

        return $this;
    }

    public function getAdresseParent(): ?string
    {
        return $this->adresseParent;
    }

    public function setAdresseParent(?string $adresseParent): static
    {
        $this->adresseParent = $adresseParent;

        return $this;
    }

    public function getProfessionParent(): ?string
    {
        return $this->professionParent;
    }

    public function setProfessionParent(?string $professionParent): static
    {
        $this->professionParent = $professionParent;

        return $this;
    }

    public function getLienParental(): ?string
    {
        return $this->lienParental;
    }

    public function setLienParental(string $lienParental): static
    {
        $this->lienParental = $lienParental;

        return $this;
    }

    public function getEtablissement(): ?Ecole
    {
        return $this->etablissement;
    }

    public function setEtablissement(?Ecole $etablissement): static
    {
        $this->etablissement = $etablissement;

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

    public function getSection(): ?Section
    {
        return $this->section;
    }

    public function setSection(?Section $section): static
    {
        $this->section = $section;

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

    public function getDateValidation(): ?\DateTime
    {
        return $this->dateValidation;
    }

    public function setDateValidation(\DateTime $dateValidation): static
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getMotifRejet(): ?string
    {
        return $this->motifRejet;
    }

    public function setMotifRejet(?string $motifRejet): static
    {
        $this->motifRejet = $motifRejet;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }


    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }


    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }


    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }


    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}

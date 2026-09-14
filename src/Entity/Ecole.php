<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;

use App\Repository\EcoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EcoleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Ecole
{
    use EntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;
   
    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    /**
     * @var Collection<int, Classe>
     */
    #[ORM\OneToMany(targetEntity: Classe::class, mappedBy: 'ecole')]
    private Collection $classes;

    /**
     * @var Collection<int, Professeur>
     */
    #[ORM\ManyToMany(targetEntity: Professeur::class, mappedBy: 'ecoles')]
    private Collection $professeurs;

    /**
     * @var Collection<int, Eleve>
     */
    #[ORM\OneToMany(targetEntity: Eleve::class, mappedBy: 'ecole')]
    private Collection $eleves;

    /**
     * @var Collection<int, Section>
     */
    #[ORM\OneToMany(targetEntity: Section::class, mappedBy: 'ecole')]
    private Collection $sections;

    /**
     * @var Collection<int, AnneeScolaire>
     */
    #[ORM\OneToMany(targetEntity: AnneeScolaire::class, mappedBy: 'ecole')]
    private Collection $anneeScolaires;

    /**
     * @var Collection<int, InscriptionEleve>
     */
    #[ORM\OneToMany(targetEntity: InscriptionEleve::class, mappedBy: 'etablissement')]
    private Collection $inscriptionEleves;

    public function __construct()
    {
        $this->classes = new ArrayCollection();
        $this->professeurs = new ArrayCollection();
        $this->eleves = new ArrayCollection();
        $this->sections = new ArrayCollection();
        $this->anneeScolaires = new ArrayCollection();
        $this->inscriptionEleves = new ArrayCollection();
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

         if ($this->code === null) {
         $this->code = $this->generateCode($nom);
    }
       
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

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

    /**
     * @return Collection<int, Classe>
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    public function addClasse(Classe $classe): static
    {
        if (!$this->classes->contains($classe)) {
            $this->classes->add($classe);
            $classe->setEcole($this);
        }

        return $this;
    }

    public function removeClasse(Classe $classe): static
    {
        if ($this->classes->removeElement($classe)) {
            if ($classe->getEcole() === $this) {
                $classe->setEcole(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Professeur>
     */
    public function getProfesseurs(): Collection
    {
        return $this->professeurs;
    }

    public function addProfesseur(Professeur $professeur): static
    {
        if (!$this->professeurs->contains($professeur)) {
            $this->professeurs->add($professeur);
            $professeur->addEcole($this);
        }

        return $this;
    }

    public function removeProfesseur(Professeur $professeur): static
    {
        if ($this->professeurs->removeElement($professeur)) {
            $professeur->removeEcole($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Eleve>
     */
    public function getEleves(): Collection
    {
        return $this->eleves;
    }

    public function addElefe(Eleve $elefe): static
    {
        if (!$this->eleves->contains($elefe)) {
            $this->eleves->add($elefe);
            $elefe->setEcole($this);
        }

        return $this;
    }

    public function removeElefe(Eleve $elefe): static
    {
        if ($this->eleves->removeElement($elefe)) {
            // set the owning side to null (unless already changed)
            if ($elefe->getEcole() === $this) {
                $elefe->setEcole(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Section>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(Section $section): static
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setEcole($this);
        }

        return $this;
    }

    public function removeSection(Section $section): static
    {
        if ($this->sections->removeElement($section)) {
            // set the owning side to null (unless already changed)
            if ($section->getEcole() === $this) {
                $section->setEcole(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AnneeScolaire>
     */
    public function getAnneeScolaires(): Collection
    {
        return $this->anneeScolaires;
    }

    public function addAnneeScolaire(AnneeScolaire $anneeScolaire): static
    {
        if (!$this->anneeScolaires->contains($anneeScolaire)) {
            $this->anneeScolaires->add($anneeScolaire);
            $anneeScolaire->setEcole($this);
        }

        return $this;
    }

    public function removeAnneeScolaire(AnneeScolaire $anneeScolaire): static
    {
        if ($this->anneeScolaires->removeElement($anneeScolaire)) {
            // set the owning side to null (unless already changed)
            if ($anneeScolaire->getEcole() === $this) {
                $anneeScolaire->setEcole(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, InscriptionEleve>
     */
    public function getInscriptionEleves(): Collection
    {
        return $this->inscriptionEleves;
    }

    public function addInscriptionElefe(InscriptionEleve $inscriptionElefe): static
    {
        if (!$this->inscriptionEleves->contains($inscriptionElefe)) {
            $this->inscriptionEleves->add($inscriptionElefe);
            $inscriptionElefe->setEtablissement($this);
        }

        return $this;
    }

    public function removeInscriptionElefe(InscriptionEleve $inscriptionElefe): static
    {
        if ($this->inscriptionEleves->removeElement($inscriptionElefe)) {
            // set the owning side to null (unless already changed)
            if ($inscriptionElefe->getEtablissement() === $this) {
                $inscriptionElefe->setEtablissement(null);
            }
        }

        return $this;
    }
}
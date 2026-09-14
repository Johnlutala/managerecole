<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;

use App\Repository\ClasseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Classe
{
    use EntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(nullable: true)]
    private ?int $capacite = null;

    #[ORM\ManyToOne(inversedBy: 'classes')]
    private ?Ecole $ecole = null;

    /**
     * @var Collection<int, Option>
     */
    #[ORM\OneToMany(targetEntity: Option::class, mappedBy: 'classe')]
    private Collection $options;

    /**
     * @var Collection<int, Cours>
     */
    #[ORM\OneToMany(targetEntity: Cours::class, mappedBy: 'classe')]
    private Collection $cours;

    /**
     * @var Collection<int, Eleve>
     */
    #[ORM\OneToMany(targetEntity: Eleve::class, mappedBy: 'classe')]
    private Collection $eleves;

    /**
     * @var Collection<int, Presence>
     */
    #[ORM\OneToMany(targetEntity: Presence::class, mappedBy: 'classe')]
    private Collection $presences;

    /**
     * @var Collection<int, InscriptionEleve>
     */
    #[ORM\OneToMany(targetEntity: InscriptionEleve::class, mappedBy: 'classe')]
    private Collection $inscriptionEleves;

    #[ORM\ManyToOne(inversedBy: 'classes')]
    private ?Section $section = null;
  

    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->cours = new ArrayCollection();
        $this->eleves = new ArrayCollection();
        $this->presences = new ArrayCollection();
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


    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;

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

    /**
     * @return Collection<int, Option>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(Option $option): static
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setClasse($this);
        }

        return $this;
    }

    public function removeOption(Option $option): static
    {
        if ($this->options->removeElement($option)) {
            // set the owning side to null (unless already changed)
            if ($option->getClasse() === $this) {
                $option->setClasse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Cours>
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCour(Cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
            $cour->setClasse($this);
        }

        return $this;
    }

    public function removeCour(Cours $cour): static
    {
        if ($this->cours->removeElement($cour)) {
            // set the owning side to null (unless already changed)
            if ($cour->getClasse() === $this) {
                $cour->setClasse(null);
            }
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
            $elefe->setClasse($this);
        }

        return $this;
    }

    public function removeElefe(Eleve $elefe): static
    {
        if ($this->eleves->removeElement($elefe)) {
            // set the owning side to null (unless already changed)
            if ($elefe->getClasse() === $this) {
                $elefe->setClasse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Presence>
     */
    public function getPresences(): Collection
    {
        return $this->presences;
    }

    public function addPresence(Presence $presence): static
    {
        if (!$this->presences->contains($presence)) {
            $this->presences->add($presence);
            $presence->setClasse($this);
        }

        return $this;
    }

    public function removePresence(Presence $presence): static
    {
        if ($this->presences->removeElement($presence)) {
            // set the owning side to null (unless already changed)
            if ($presence->getClasse() === $this) {
                $presence->setClasse(null);
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
            $inscriptionElefe->setClasse($this);
        }

        return $this;
    }

    public function removeInscriptionElefe(InscriptionEleve $inscriptionElefe): static
    {
        if ($this->inscriptionEleves->removeElement($inscriptionElefe)) {
            // set the owning side to null (unless already changed)
            if ($inscriptionElefe->getClasse() === $this) {
                $inscriptionElefe->setClasse(null);
            }
        }

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
  
}

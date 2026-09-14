<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\SectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SectionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Section
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    private ?Ecole $ecole = null;

    /**
     * @var Collection<int, InscriptionEleve>
     */
    #[ORM\OneToMany(targetEntity: InscriptionEleve::class, mappedBy: 'section')]
    private Collection $inscriptionEleves;

    /**
     * @var Collection<int, Classe>
     */
    #[ORM\OneToMany(targetEntity: Classe::class, mappedBy: 'section')]
    private Collection $classes;

    public function __construct()
    {
        $this->inscriptionEleves = new ArrayCollection();
        $this->classes = new ArrayCollection();
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
            $inscriptionElefe->setSection($this);
        }

        return $this;
    }

    public function removeInscriptionElefe(InscriptionEleve $inscriptionElefe): static
    {
        if ($this->inscriptionEleves->removeElement($inscriptionElefe)) {
            // set the owning side to null (unless already changed)
            if ($inscriptionElefe->getSection() === $this) {
                $inscriptionElefe->setSection(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Classe>
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    public function addClass(Classe $class): static
    {
        if (!$this->classes->contains($class)) {
            $this->classes->add($class);
            $class->setSection($this);
        }

        return $this;
    }

    public function removeClass(Classe $class): static
    {
        if ($this->classes->removeElement($class)) {
            // set the owning side to null (unless already changed)
            if ($class->getSection() === $this) {
                $class->setSection(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}

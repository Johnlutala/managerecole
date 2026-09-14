<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;

use App\Repository\OptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OptionRepository::class)]
#[ORM\Table(name: '`option`')]
#[ORM\HasLifecycleCallbacks]
class Option
{
    use EntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'options')]
    private ?Classe $classe = null;

    /**
     * @var Collection<int, InscriptionEleve>
     */
    #[ORM\OneToMany(targetEntity: InscriptionEleve::class, mappedBy: 'options')]
    private Collection $inscriptionEleves;

    public function __construct()
    {
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
   

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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
            $inscriptionElefe->setOptions($this);
        }

        return $this;
    }

    public function removeInscriptionElefe(InscriptionEleve $inscriptionElefe): static
    {
        if ($this->inscriptionEleves->removeElement($inscriptionElefe)) {
            // set the owning side to null (unless already changed)
            if ($inscriptionElefe->getOptions() === $this) {
                $inscriptionElefe->setOptions(null);
            }
        }

        return $this;
    }
}

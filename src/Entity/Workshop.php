<?php

namespace App\Entity;

use App\Repository\WorkshopRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkshopRepository::class)]
class Workshop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $daysNumber = null;

    #[ORM\OneToOne(mappedBy: 'workshop', cascade: ['persist', 'remove'])]
    private ?WorkshopList $workshopList = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDaysNumber(): ?int
    {
        return $this->daysNumber;
    }

    public function setDaysNumber(?int $daysNumber): static
    {
        $this->daysNumber = $daysNumber;

        return $this;
    }

    public function getWorkshopList(): ?WorkshopList
    {
        return $this->workshopList;
    }

    public function setWorkshopList(WorkshopList $workshopList): static
    {
        // set the owning side of the relation if necessary
        if ($workshopList->getWorkshop() !== $this) {
            $workshopList->setWorkshop($this);
        }

        $this->workshopList = $workshopList;

        return $this;
    }
}

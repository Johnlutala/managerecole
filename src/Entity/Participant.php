<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
class Participant
{
    use EntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Demographic $demographic = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $phone = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WorkshopList $workshopList = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Company $company = null;


    public function __construct()
    {
        $this->code = uniqid();
        $this->enabled = true;
        $this->deleted = false;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemographic(): ?Demographic
    {
        return $this->demographic;
    }

    public function setDemographic(?Demographic $demographic): static
    {
        $this->demographic = $demographic;

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

    public function getWorkshopList(): ?WorkshopList
    {
        return $this->workshopList;
    }

    public function setWorkshopList(?WorkshopList $workshopList): static
    {
        $this->workshopList = $workshopList;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }
}

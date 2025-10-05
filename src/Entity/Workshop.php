<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\WorkshopRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkshopRepository::class)]
class Workshop
{
    use EntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique:true)]
    private ?string $name = null;


    #[ORM\OneToOne(mappedBy: 'workshop', cascade: ['persist', 'remove'])]
    private ?WorkshopList $workshopList = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $dates = null;

    #[ORM\Column]
    private ?float $dailyAmount = null;

    #[ORM\Column(length: 5)]
    private ?string $currency = null;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): Workshop
    {
        $this->name = $name;

        return $this;
    }

    

    public function getWorkshopList(): ?WorkshopList
    {
        return $this->workshopList;
    }

    public function setWorkshopList(WorkshopList $workshopList): Workshop
    {
        // set the owning side of the relation if necessary
        if ($workshopList->getWorkshop() !== $this) {
            $workshopList->setWorkshop($this);
        }

        $this->workshopList = $workshopList;

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

    public function getDates(): ?array
    {
        return $this->dates;
    }

    public function setDates(?array $dates): static
    {
        $this->dates = $dates;

        return $this;
    }

    public function getDailyAmount(): ?float
    {
        return $this->dailyAmount;
    }

    public function setDailyAmount(float $dailyAmount): static
    {
        $this->dailyAmount = $dailyAmount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }
}

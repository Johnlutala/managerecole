<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\WorkshopRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: WorkshopRepository::class)]
class Workshop
{
    use EntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['workshop:read'])]
    private ?int $id = null;
    
    #[Groups(['workshop:read'])]
    #[ORM\Column(length: 255, unique:true)]
    private ?string $name = null;
    
    
    
    #[Groups(['workshop:read'])]
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;
    
    
    #[ORM\Column(nullable: true)]
    private ?float $dailyAmount = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $currency = null;

    /**
     * @var Collection<int, WorkshopDay>
     */
    #[ORM\OneToMany(targetEntity: WorkshopDay::class, mappedBy: 'workshop')]
    private Collection $workshopDays;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'workshop')]
    private Collection $participations;

    #[ORM\Column(nullable: true)]
    private ?bool $isEnded = null;

    #[ORM\ManyToOne(inversedBy: 'workshops')]
    private ?MerchantConfiguration $configuration = null;

    public function __construct()
    {
        $this->code = uniqid();
        $this->enabled = true;
        $this->deleted = false;
        $this->createdAt = new \DateTimeImmutable();
        $this->workshopDays = new ArrayCollection();
        $this->participations = new ArrayCollection();
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

    

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    /**
     * @return Collection<int, WorkshopDay>
     */
    public function getWorkshopDays(): Collection
    {
        return $this->workshopDays;
    }

    public function addWorkshopDay(WorkshopDay $workshopDay): static
    {
        if (!$this->workshopDays->contains($workshopDay)) {
            $this->workshopDays->add($workshopDay);
            $workshopDay->setWorkshop($this);
        }

        return $this;
    }

    public function removeWorkshopDay(WorkshopDay $workshopDay): static
    {
        if ($this->workshopDays->removeElement($workshopDay)) {
            // set the owning side to null (unless already changed)
            if ($workshopDay->getWorkshop() === $this) {
                $workshopDay->setWorkshop(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setWorkshop($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getWorkshop() === $this) {
                $participation->setWorkshop(null);
            }
        }

        return $this;
    }

    public function isEnded(): ?bool
    {
        return $this->isEnded;
    }

    public function setIsEnded(?bool $isEnded): static
    {
        $this->isEnded = $isEnded;

        return $this;
    }

    public function getConfiguration(): ?MerchantConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?MerchantConfiguration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}

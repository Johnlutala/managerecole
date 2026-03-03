<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
class Participant
{
    use EntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['participant:read'])]
    private ?int $id = null;
    
    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Demographic $demographic = null;
    
    #[ORM\Column(length: 25, nullable: true)]
    #[Groups(['participant:read'])]
    private ?string $phone = null;


    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Company $company = null;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'participant')]
    private Collection $participations;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $phoneMobileMoney = null;


    public function __construct()
    {
        $this->code = uniqid();
        $this->enabled = true;
        $this->deleted = false;
        $this->createdAt = new \DateTimeImmutable();
        $this->participations = new ArrayCollection();
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


    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

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
            $participation->setParticipant($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getParticipant() === $this) {
                $participation->setParticipant(null);
            }
        }

        return $this;
    }

    public function getFullname(): ?string
    {
        $demographic = $this->getDemographic();
        if (!$demographic) {
            return null;
        }

        $firstname = $demographic->getFirstname();
        $middlename = $demographic->getMiddlename();
        $lastname = $demographic->getLastname();

        $fullname = trim($firstname . ' ' . $middlename . ' ' . $lastname);
        return $fullname !== '' ? $fullname : null;
    }

    public function getPhoneMobileMoney(): ?string
    {
        return $this->phoneMobileMoney;
    }

    public function setPhoneMobileMoney(?string $phoneMobileMoney): static
    {
        $this->phoneMobileMoney = $phoneMobileMoney;

        return $this;
    }
}

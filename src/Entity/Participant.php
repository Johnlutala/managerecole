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
    
    #[ORM\Column(length: 25, nullable: true)]
    #[Groups(['participant:read'])]
    private ?string $phone = null;


    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'participant')]
    private Collection $participations;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $phoneMobileMoney = null;

    #[ORM\Column(length: 50)]
    private ?string $firstname = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $middlename = null;

    #[ORM\Column(length: 50)]
    private ?string $lastname = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\OneToOne(inversedBy: 'participant', cascade: ['persist', 'remove'])]
    private ?Biometric $biometric = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $organization = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $grade = null;


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


    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }
    
    public function getPhoneMomo(): ?string
    {
        return $this->phoneMobileMoney;
    }

    public function setPhoneMomo(?string $phoneMobileMoney): static
    {
        $this->phoneMobileMoney = $phoneMobileMoney;

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
        

        $firstname = $this->getFirstname();
        $middlename = $this->getMiddlename();
        $lastname = $this->getLastname();

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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getMiddlename(): ?string
    {
        return $this->middlename;
    }

    public function setMiddlename(?string $middlename): static
    {
        $this->middlename = $middlename;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getBiometric(): ?Biometric
    {
        return $this->biometric;
    }

    public function setBiometric(?Biometric $biometric): static
    {
        $this->biometric = $biometric;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(?string $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;

        return $this;
    }
}

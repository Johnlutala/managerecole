<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\BiometricRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BiometricRepository::class)]
class Biometric
{
    use EntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rightThumb = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rightIndex = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rightMiddle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rightRing = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rightLittle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $leftThumb = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $leftIndex = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $leftMiddle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $leftRing = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $leftLittle = null;

    #[ORM\OneToOne(mappedBy: 'biometric', cascade: ['persist', 'remove'])]
    private ?Participant $participant = null;

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


    public function getRightThumb(): ?string
    {
        return $this->rightThumb;
    }

    public function setRightThumb(?string $rightThumb): static
    {
        $this->rightThumb = $rightThumb;

        return $this;
    }

    public function getRightIndex(): ?string
    {
        return $this->rightIndex;
    }

    public function setRightIndex(?string $rightIndex): static
    {
        $this->rightIndex = $rightIndex;

        return $this;
    }

    public function getRightMiddle(): ?string
    {
        return $this->rightMiddle;
    }

    public function setRightMiddle(?string $rightMiddle): static
    {
        $this->rightMiddle = $rightMiddle;

        return $this;
    }

    public function getRightRing(): ?string
    {
        return $this->rightRing;
    }

    public function setRightRing(?string $rightRing): static
    {
        $this->rightRing = $rightRing;

        return $this;
    }

    public function getRightLittle(): ?string
    {
        return $this->rightLittle;
    }

    public function setRightLittle(?string $rightLittle): static
    {
        $this->rightLittle = $rightLittle;

        return $this;
    }

    public function getLeftThumb(): ?string
    {
        return $this->leftThumb;
    }

    public function setLeftThumb(?string $leftThumb): static
    {
        $this->leftThumb = $leftThumb;

        return $this;
    }

    public function getLeftIndex(): ?string
    {
        return $this->leftIndex;
    }

    public function setLeftIndex(?string $leftIndex): static
    {
        $this->leftIndex = $leftIndex;

        return $this;
    }

    public function getLeftMiddle(): ?string
    {
        return $this->leftMiddle;
    }

    public function setLeftMiddle(?string $leftMiddle): static
    {
        $this->leftMiddle = $leftMiddle;

        return $this;
    }

    public function getLeftRing(): ?string
    {
        return $this->leftRing;
    }

    public function setLeftRing(?string $leftRing): static
    {
        $this->leftRing = $leftRing;

        return $this;
    }

    public function getLeftLittle(): ?string
    {
        return $this->leftLittle;
    }

    public function setLeftLittle(?string $leftLittle): static
    {
        $this->leftLittle = $leftLittle;

        return $this;
    }

   

    public function getFingers(): array
    {
        return [
            'rightThumb' => [
                'pos' => 1,
                'data' => $this->rightThumb,
            ],
            'leftThumb' => [
                'pos' => 6,
                'data' => $this->leftThumb,
            ],
        ];
    }

    public function getParticipant(): ?Participant
    {
        return $this->participant;
    }

    public function setParticipant(?Participant $participant): static
    {
        // unset the owning side of the relation if necessary
        if ($participant === null && $this->participant !== null) {
            $this->participant->setBiometric(null);
        }

        // set the owning side of the relation if necessary
        if ($participant !== null && $participant->getBiometric() !== $this) {
            $participant->setBiometric($this);
        }

        $this->participant = $participant;

        return $this;
    }
}

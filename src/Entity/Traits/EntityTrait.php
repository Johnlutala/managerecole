<?php

namespace App\Entity\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

trait EntityTrait
{
    #[ORM\Column(type: 'string', length: 50, unique: true, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $enabled = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $deleted = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: true)]
    private ?User $createdBy = null;


    // ========================================
    // AUTOMATISATION
    // ========================================

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();

        if ($this->deleted === null) {
            $this->deleted = false;
        }

        if ($this->enabled === null) {
            $this->enabled = true;
        }
    }


    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }


    // ========================================
    // CODE
    // ========================================

    protected function generateCode(string $nom): string
    {
        $words = preg_split('/\s+/', trim($nom));

        $initials = '';

        foreach ($words as $word) {
            if ($word !== '') {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return $initials . str_pad(
            (string) random_int(0, 999),
            3,
            '0',
            STR_PAD_LEFT
        );
    }


    // ========================================
    // GETTERS / SETTERS
    // ========================================

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }


    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }


    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }


    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }


    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }


    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
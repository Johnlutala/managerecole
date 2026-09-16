<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use EntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 100)]
    private ?string $email = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstname = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastname = null;


    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\OneToOne(inversedBy: 'user')]
    #[ORM\JoinColumn(name: 'eleve_id', referencedColumnName: 'id', unique: true, nullable: true, onDelete: 'SET NULL')]
    private ?Eleve $eleve = null;

    /**
     * @var Collection<int, InscriptionEleve>
     */
    #[ORM\OneToMany(targetEntity: InscriptionEleve::class, mappedBy: 'createdBy')]
    private Collection $inscriptionEleves;

    public function __construct()
    {
        $this->code = uniqid();
        $this->enabled = true;
        $this->deleted = false;
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->inscriptionEleves = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getEleve(): ?Eleve
    {
        return $this->eleve;
    }

    public function setEleve(?Eleve $eleve): static
    {
        $this->eleve = $eleve;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * Hash un mot de passe avec bcrypt
     */
    public function hashPassword($plainPassword)
    {
        return password_hash($plainPassword, PASSWORD_BCRYPT, [
            'cost' => 15
        ]);
    }

    /**
     * Vérifie si un mot de passe correspond au hash
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
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
            $inscriptionElefe->setCreatedBy($this);
        }

        return $this;
    }

    public function removeInscriptionElefe(InscriptionEleve $inscriptionElefe): static
    {
        if ($this->inscriptionEleves->removeElement($inscriptionElefe)) {
            // set the owning side to null (unless already changed)
            if ($inscriptionElefe->getCreatedBy() === $this) {
                $inscriptionElefe->setCreatedBy(null);
            }
        }

        return $this;
    }
}

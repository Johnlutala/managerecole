<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User 
{
    use EntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;
    
    #[Groups(['user:read'])]
    #[ORM\Column(length: 255, unique:true)]
    private ?string $username = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;
    
    #[Groups(['user:read'])]
    #[ORM\Column(length: 100, unique:true)]
    private ?string $email = null;
    
    #[Groups(['user:read'])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstname = null;
    
    #[Groups(['user:read'])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastname = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?MerchantConfiguration $configuration = null;


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

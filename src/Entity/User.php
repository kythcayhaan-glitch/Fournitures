<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true, nullable: true)]
    private ?string $email = null;

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $firstName = '';

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $lastName = '';

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private bool $isActive = true;

    /** @var Collection<int, DemandeMateriel> */
    #[ORM\OneToMany(targetEntity: DemandeMateriel::class, mappedBy: 'requester')]
    private Collection $demandes;

    /** @var Collection<int, DemandeMateriel> */
    #[ORM\OneToMany(targetEntity: DemandeMateriel::class, mappedBy: 'processedBy')]
    private Collection $demandesTraitees;

    /** @var Collection<int, MouvementStock> */
    #[ORM\OneToMany(targetEntity: MouvementStock::class, mappedBy: 'operateur')]
    private Collection $mouvementsStock;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->demandes = new ArrayCollection();
        $this->demandesTraitees = new ArrayCollection();
        $this->mouvementsStock = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->lastName;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles(), true);
    }

    public function isManager(): bool
    {
        return in_array('ROLE_MANAGER', $this->getRoles(), true) || $this->isAdmin();
    }

    /** @return Collection<int, DemandeMateriel> */
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    /** @return Collection<int, DemandeMateriel> */
    public function getDemandesTraitees(): Collection
    {
        return $this->demandesTraitees;
    }

    /** @return Collection<int, MouvementStock> */
    public function getMouvementsStock(): Collection
    {
        return $this->mouvementsStock;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}

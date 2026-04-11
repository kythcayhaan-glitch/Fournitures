<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce nom de catégorie est déjà utilisé.')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Fourniture> */
    #[ORM\OneToMany(targetEntity: Fourniture::class, mappedBy: 'category')]
    private Collection $fournitures;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->fournitures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Fourniture> */
    public function getFournitures(): Collection
    {
        return $this->fournitures;
    }

    public function addFourniture(Fourniture $fourniture): static
    {
        if (!$this->fournitures->contains($fourniture)) {
            $this->fournitures->add($fourniture);
            $fourniture->setCategory($this);
        }
        return $this;
    }

    public function removeFourniture(Fourniture $fourniture): static
    {
        if ($this->fournitures->removeElement($fourniture)) {
            if ($fourniture->getCategory() === $this) {
                $fourniture->setCategory(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

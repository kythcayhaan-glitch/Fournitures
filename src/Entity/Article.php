<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Index(columns: ['stock_quantity'], name: 'idx_article_stock')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['reference'], message: 'Cette référence est déjà utilisée.')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    private string $name = '';

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $reference = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private float $unitPrice = 0.0;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $unit = 'unité';

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $stockQuantity = 0;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $stockMinimum = 0;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Category $category = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, LigneDemande> */
    #[ORM\OneToMany(targetEntity: LigneDemande::class, mappedBy: 'article')]
    private Collection $lignesDemande;

    /** @var Collection<int, MouvementStock> */
    #[ORM\OneToMany(targetEntity: MouvementStock::class, mappedBy: 'article')]
    private Collection $mouvements;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lignesDemande = new ArrayCollection();
        $this->mouvements = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
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

    public function getUnitPrice(): float
    {
        return (float) $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): static
    {
        $this->stockQuantity = $stockQuantity;
        return $this;
    }

    public function getStockMinimum(): int
    {
        return $this->stockMinimum;
    }

    public function setStockMinimum(int $stockMinimum): static
    {
        $this->stockMinimum = $stockMinimum;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Indique si le stock est sous le seuil minimum.
     */
    public function isStockBas(): bool
    {
        return $this->stockQuantity <= $this->stockMinimum;
    }

    /** @return Collection<int, LigneDemande> */
    public function getLignesDemande(): Collection
    {
        return $this->lignesDemande;
    }

    /** @return Collection<int, MouvementStock> */
    public function getMouvements(): Collection
    {
        return $this->mouvements;
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s', $this->reference, $this->name);
    }
}

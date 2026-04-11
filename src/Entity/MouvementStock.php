<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TypeMouvement;
use App\Repository\MouvementStockRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MouvementStockRepository::class)]
#[ORM\Index(columns: ['created_at'], name: 'idx_mouvement_date')]
#[ORM\Index(columns: ['type'], name: 'idx_mouvement_type')]
class MouvementStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: TypeMouvement::class)]
    private TypeMouvement $type;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    private int $quantite = 0;

    #[ORM\Column(type: 'integer')]
    private int $quantiteAvant = 0;

    #[ORM\Column(type: 'integer')]
    private int $quantiteApres = 0;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $motif = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Fourniture::class, inversedBy: 'mouvements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Fourniture $fourniture = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'mouvementsStock')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $operateur = null;

    #[ORM\ManyToOne(targetEntity: DemandeMateriel::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?DemandeMateriel $demande = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): TypeMouvement
    {
        return $this->type;
    }

    public function setType(TypeMouvement $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getQuantiteAvant(): int
    {
        return $this->quantiteAvant;
    }

    public function setQuantiteAvant(int $quantiteAvant): static
    {
        $this->quantiteAvant = $quantiteAvant;
        return $this;
    }

    public function getQuantiteApres(): int
    {
        return $this->quantiteApres;
    }

    public function setQuantiteApres(int $quantiteApres): static
    {
        $this->quantiteApres = $quantiteApres;
        return $this;
    }

    public function getMotif(): string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getFourniture(): ?Fourniture
    {
        return $this->fourniture;
    }

    public function setFourniture(?Fourniture $fourniture): static
    {
        $this->fourniture = $fourniture;
        return $this;
    }

    public function getOperateur(): ?User
    {
        return $this->operateur;
    }

    public function setOperateur(?User $operateur): static
    {
        $this->operateur = $operateur;
        return $this;
    }

    public function getDemande(): ?DemandeMateriel
    {
        return $this->demande;
    }

    public function setDemande(?DemandeMateriel $demande): static
    {
        $this->demande = $demande;
        return $this;
    }
}

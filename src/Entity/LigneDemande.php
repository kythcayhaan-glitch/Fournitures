<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LigneDemandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LigneDemandeRepository::class)]
class LigneDemande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive(message: 'La quantité demandée doit être supérieure à 0.')]
    private int $quantiteDemandee = 1;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $quantiteServie = 0;

    #[ORM\ManyToOne(targetEntity: Fourniture::class, inversedBy: 'lignesDemande')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner une fourniture.')]
    private ?Fourniture $fourniture = null;

    #[ORM\ManyToOne(targetEntity: DemandeMateriel::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DemandeMateriel $demande = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantiteDemandee(): int
    {
        return $this->quantiteDemandee;
    }

    public function setQuantiteDemandee(int $quantiteDemandee): static
    {
        $this->quantiteDemandee = $quantiteDemandee;
        return $this;
    }

    public function getQuantiteServie(): int
    {
        return $this->quantiteServie;
    }

    public function setQuantiteServie(int $quantiteServie): static
    {
        $this->quantiteServie = $quantiteServie;
        return $this;
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

    public function getDemande(): ?DemandeMateriel
    {
        return $this->demande;
    }

    public function setDemande(?DemandeMateriel $demande): static
    {
        $this->demande = $demande;
        return $this;
    }

    /**
     * Retourne le sous-total de la ligne (quantité servie × prix unitaire).
     * Si non encore servi, utilise la quantité demandée.
     */
    public function getSousTotal(): float
    {
        if ($this->fourniture === null) {
            return 0.0;
        }
        $qty = $this->quantiteServie > 0 ? $this->quantiteServie : $this->quantiteDemandee;
        return $qty * $this->fourniture->getUnitPrice();
    }
}

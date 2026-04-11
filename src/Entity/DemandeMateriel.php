<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DemandeMaterielRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DemandeMaterielRepository::class)]
#[ORM\Index(columns: ['statut'], name: 'idx_demande_statut')]
#[ORM\Index(columns: ['requested_at'], name: 'idx_demande_date')]
#[ORM\Index(columns: ['requester_id'], name: 'idx_demande_requester')]
class DemandeMateriel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motif = null;

    /**
     * Statut géré par le Workflow Symfony (state machine).
     * Valeurs possibles : pending, approved, rejected, delivered
     */
    #[ORM\Column(length: 20)]
    private string $statut = 'pending';

    #[ORM\Column]
    private \DateTimeImmutable $requestedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'demandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requester = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'demandesTraitees')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $processedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    /** @var Collection<int, LigneDemande> */
    #[ORM\OneToMany(
        targetEntity: LigneDemande::class,
        mappedBy: 'demande',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Assert\Count(min: 1, minMessage: 'La demande doit contenir au moins une ligne.')]
    #[Assert\Valid]
    private Collection $lignes;

    public function __construct()
    {
        $this->requestedAt = new \DateTimeImmutable();
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function getRequester(): ?User
    {
        return $this->requester;
    }

    public function setRequester(?User $requester): static
    {
        $this->requester = $requester;
        return $this;
    }

    public function getProcessedBy(): ?User
    {
        return $this->processedBy;
    }

    public function setProcessedBy(?User $processedBy): static
    {
        $this->processedBy = $processedBy;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    /** @return Collection<int, LigneDemande> */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneDemande $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setDemande($this);
        }
        return $this;
    }

    public function removeLigne(LigneDemande $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            if ($ligne->getDemande() === $this) {
                $ligne->setDemande(null);
            }
        }
        return $this;
    }

    /**
     * Calcule le montant total estimé de la demande.
     */
    public function getMontantTotal(): float
    {
        return array_sum(
            $this->lignes->map(fn(LigneDemande $l) => $l->getSousTotal())->toArray()
        );
    }

    public function isPending(): bool
    {
        return $this->statut === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->statut === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->statut === 'rejected';
    }

    public function isDelivered(): bool
    {
        return $this->statut === 'delivered';
    }
}

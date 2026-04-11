<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\DemandeMateriel;
use App\Entity\LigneDemande;
use App\Service\NotificationService;
use App\Service\StockService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;

class WorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
        private readonly Security $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.demande_materiel.transition'             => 'onTransition',
            'workflow.demande_materiel.transition.approve'     => 'onApprove',
            'workflow.demande_materiel.transition.reject'      => 'onReject',
            'workflow.demande_materiel.transition.deliver'     => 'onDeliver',
            'workflow.demande_materiel.guard.approve'          => 'guardManagerOnly',
            'workflow.demande_materiel.guard.reject'           => 'guardManagerOnly',
            'workflow.demande_materiel.guard.deliver'          => 'guardManagerOnly',
        ];
    }

    /**
     * Logge toutes les transitions.
     */
    public function onTransition(Event $event): void
    {
        /** @var DemandeMateriel $demande */
        $demande = $event->getSubject();
        $transition = $event->getTransition();

        $this->logger->info('Workflow transition', [
            'demande'    => $demande->getReference(),
            'transition' => $transition?->getName(),
            'from'       => implode(', ', $transition?->getFroms() ?? []),
            'to'         => implode(', ', $transition?->getTos() ?? []),
        ]);
    }

    /**
     * Actions lors de l'approbation.
     */
    public function onApprove(Event $event): void
    {
        /** @var DemandeMateriel $demande */
        $demande = $event->getSubject();
        $user = $this->security->getUser();

        if ($user !== null) {
            $demande->setProcessedBy($user); // @phpstan-ignore-line
            $demande->setProcessedAt(new \DateTimeImmutable());
        }

        $this->notificationService->notifierUtilisateur($demande);
    }

    /**
     * Actions lors du rejet.
     */
    public function onReject(Event $event): void
    {
        /** @var DemandeMateriel $demande */
        $demande = $event->getSubject();
        $user = $this->security->getUser();

        if ($user !== null) {
            $demande->setProcessedBy($user); // @phpstan-ignore-line
            $demande->setProcessedAt(new \DateTimeImmutable());
        }

        $this->notificationService->notifierUtilisateur($demande);
    }

    /**
     * Actions lors de la livraison : déduction du stock pour chaque ligne.
     */
    public function onDeliver(Event $event): void
    {
        /** @var DemandeMateriel $demande */
        $demande = $event->getSubject();
        $user = $this->security->getUser();

        if ($user === null) {
            $this->logger->error('Livraison sans utilisateur authentifié', [
                'demande' => $demande->getReference(),
            ]);
            return;
        }

        $demande->setProcessedAt(new \DateTimeImmutable());

        /** @var LigneDemande $ligne */
        foreach ($demande->getLignes() as $ligne) {
            $this->stockService->deduireStock($ligne, $user, $demande); // @phpstan-ignore-line
        }

        $this->notificationService->notifierUtilisateur($demande);

        $this->logger->info('Livraison effectuée, stock mis à jour', [
            'demande' => $demande->getReference(),
            'lignes'  => $demande->getLignes()->count(),
        ]);
    }

    /**
     * Guard : seuls les ROLE_MANAGER peuvent approuver, rejeter et livrer.
     */
    public function guardManagerOnly(GuardEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user === null || !$this->security->isGranted('ROLE_MANAGER')) {
            $event->setBlocked(true, 'Seuls les managers peuvent effectuer cette action.');
        }
    }
}

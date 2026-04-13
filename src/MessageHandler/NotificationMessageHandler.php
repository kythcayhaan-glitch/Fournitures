<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\NotificationMessage;
use App\Repository\DemandeMaterielRepository;
use App\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class NotificationMessageHandler
{
    public function __construct(
        private readonly DemandeMaterielRepository $demandeRepository,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(NotificationMessage $message): void
    {
        $demande = $this->demandeRepository->find($message->demandeMaterielId);

        if ($demande === null) {
            $this->logger->warning('NotificationMessageHandler : demande introuvable', [
                'id'   => $message->demandeMaterielId,
                'type' => $message->type,
            ]);
            return;
        }

        match ($message->type) {
            'new_demande'   => $this->notificationService->notifierManager($demande),
            'status_change' => $this->notificationService->notifierUtilisateur($demande),
            default         => $this->logger->warning('NotificationMessageHandler : type inconnu', [
                'type' => $message->type,
            ]),
        };
    }
}

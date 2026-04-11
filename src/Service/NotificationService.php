<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DemandeMateriel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Notifie les managers qu'une nouvelle demande est en attente.
     */
    public function notifierManager(DemandeMateriel $demande): void
    {
        $requester = $demande->getRequester();
        if ($requester === null) {
            return;
        }

        $this->logger->info('Notification manager : nouvelle demande', [
            'reference' => $demande->getReference(),
            'requester' => $requester->getEmail(),
        ]);

        // En production, on enverrait un email aux managers.
        // Exemple avec Symfony Mailer :
        /*
        $email = (new Email())
            ->from('noreply@example.com')
            ->to('manager@example.com')
            ->subject('Nouvelle demande de matériel : ' . $demande->getReference())
            ->html('<p>Une nouvelle demande de matériel a été soumise par '
                . $requester->getFullName()
                . '.</p><p>Référence : ' . $demande->getReference() . '</p>');

        $this->mailer->send($email);
        */
    }

    /**
     * Notifie l'utilisateur du changement de statut de sa demande.
     */
    public function notifierUtilisateur(DemandeMateriel $demande): void
    {
        $requester = $demande->getRequester();
        if ($requester === null) {
            return;
        }

        $this->logger->info('Notification utilisateur : statut modifié', [
            'reference' => $demande->getReference(),
            'statut'    => $demande->getStatut(),
            'requester' => $requester->getEmail(),
        ]);

        /*
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($requester->getEmail())
            ->subject('Votre demande ' . $demande->getReference() . ' a été mise à jour')
            ->html('<p>Votre demande <strong>' . $demande->getReference()
                . '</strong> est maintenant : <strong>' . $demande->getStatut() . '</strong>.</p>');

        $this->mailer->send($email);
        */
    }
}

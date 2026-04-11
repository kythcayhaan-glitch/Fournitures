<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\DemandeMaterielRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:demande:purger',
    description: 'Supprime les anciennes demandes d\'un statut donné.',
)]
class DemandePurgerCommand extends Command
{
    public function __construct(
        private readonly DemandeMaterielRepository $demandeRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'statut',
                's',
                InputOption::VALUE_REQUIRED,
                'Statut des demandes à purger (rejected, delivered, pending)',
                'rejected'
            )
            ->addOption(
                'jours',
                'j',
                InputOption::VALUE_REQUIRED,
                'Supprimer les demandes plus anciennes que N jours',
                '90'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Affiche ce qui serait supprimé sans le faire'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $statut = (string) $input->getOption('statut');
        $jours = (int) $input->getOption('jours');
        $dryRun = (bool) $input->getOption('dry-run');

        if (!in_array($statut, ['rejected', 'delivered', 'pending'], true)) {
            $io->error(sprintf('Statut "%s" invalide. Valeurs acceptées : rejected, delivered, pending.', $statut));
            return Command::FAILURE;
        }

        if ($jours <= 0) {
            $io->error('Le nombre de jours doit être supérieur à 0.');
            return Command::FAILURE;
        }

        $before = new \DateTimeImmutable("-{$jours} days");

        $io->title('Purge des demandes');
        $io->definitionList(
            ['Statut cible' => $statut],
            ['Antérieures au' => $before->format('d/m/Y')],
            ['Mode' => $dryRun ? '<comment>DRY-RUN (simulation)</comment>' : '<info>Suppression réelle</info>'],
        );

        $demandes = $this->demandeRepository->findOlderThan($statut, $before);

        if (empty($demandes)) {
            $io->success('Aucune demande à purger.');
            return Command::SUCCESS;
        }

        $io->warning(sprintf('%d demande(s) concernée(s) :', count($demandes)));

        $rows = [];
        foreach ($demandes as $d) {
            $rows[] = [
                $d->getReference(),
                $d->getRequester()?->getEmail() ?? 'N/A',
                $d->getRequestedAt()->format('d/m/Y'),
                $d->getStatut(),
                $d->getLignes()->count() . ' ligne(s)',
            ];
        }

        $io->table(['Référence', 'Demandeur', 'Date', 'Statut', 'Lignes'], $rows);

        if ($dryRun) {
            $io->note('Mode DRY-RUN : aucune suppression effectuée. Relancez sans --dry-run pour confirmer.');
            return Command::SUCCESS;
        }

        if (!$io->confirm(sprintf('Supprimer définitivement %d demande(s) ?', count($demandes)), false)) {
            $io->info('Opération annulée.');
            return Command::SUCCESS;
        }

        foreach ($demandes as $demande) {
            $this->em->remove($demande);
        }
        $this->em->flush();

        $io->success(sprintf('%d demande(s) supprimée(s) avec succès.', count($demandes)));

        return Command::SUCCESS;
    }
}

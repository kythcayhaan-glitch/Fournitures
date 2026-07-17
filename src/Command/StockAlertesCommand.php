<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\StockService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:stock:alertes',
    description: 'Affiche tous les articles dont le stock est sous le seuil minimum.',
)]
class StockAlertesCommand extends Command
{
    public function __construct(
        private readonly StockService $stockService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Alertes Stock Bas');

        $articles = $this->stockService->getArticlesStockBas();

        if (empty($articles)) {
            $io->success('Tous les stocks sont à niveau. Aucune alerte.');
            return Command::SUCCESS;
        }

        $io->warning(sprintf('%d article(s) en stock bas :', count($articles)));

        $rows = [];
        foreach ($articles as $f) {
            $rows[] = [
                $f->getReference(),
                $f->getName(),
                $f->getCategory()->getName(),
                $f->getStockQuantity() . ' ' . $f->getUnit(),
                $f->getStockMinimum() . ' ' . $f->getUnit(),
                $f->getStockQuantity() === 0 ? '<error>RUPTURE</error>' : '<comment>Bas</comment>',
            ];
        }

        $io->table(
            ['Référence', 'Nom', 'Catégorie', 'Stock actuel', 'Stock min.', 'Statut'],
            $rows
        );

        return Command::SUCCESS;
    }
}

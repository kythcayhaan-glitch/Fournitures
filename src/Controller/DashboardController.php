<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\DemandeMaterielRepository;
use App\Repository\MouvementStockRepository;
use App\Service\StockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DemandeMaterielRepository $demandeRepository,
        private readonly MouvementStockRepository $mouvementRepository,
        private readonly StockService $stockService,
    ) {}

    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $mesDemandesEnCours = $this->demandeRepository->findByStatutAndUser('pending', $user);
        $mesDemandesApprouvees = $this->demandeRepository->findByStatutAndUser('approved', $user);
        $statsParStatut = $this->demandeRepository->countByStatutForUser($user);
        $stocksBas = $this->stockService->getFournituresStockBas();
        $mouvementsRecents = $this->mouvementRepository->findRecent(10);

        return $this->render('dashboard/index.html.twig', [
            'mesDemandesEnCours'   => $mesDemandesEnCours,
            'mesDemandesApprouvees' => $mesDemandesApprouvees,
            'statsParStatut'       => $statsParStatut,
            'stocksBas'            => $stocksBas,
            'mouvementsRecents'    => $mouvementsRecents,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\DemandeMaterielRepository;
use App\Repository\ArticleRepository;
use App\Repository\LigneDemandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MANAGER')]
class StatistiquesController extends AbstractController
{
    public function __construct(
        private readonly DemandeMaterielRepository $demandeRepository,
        private readonly LigneDemandeRepository $ligneRepository,
        private readonly ArticleRepository $articleRepository,
    ) {}

    #[Route('/statistiques', name: 'app_statistiques', methods: ['GET'])]
    public function index(): Response
    {
        $parStatut     = $this->demandeRepository->countByStatut();
        $parService    = $this->demandeRepository->countByService();
        $parMois       = $this->demandeRepository->countParMois(6);
        $topDemandeurs = $this->demandeRepository->topDemandeurs(5);
        $topArticles   = $this->ligneRepository->topArticlesDemandes(10);
        $stocksBas     = $this->articleRepository->findStockBas();
        $totalArticles = $this->articleRepository->count([]);

        $totalDemandes  = array_sum($parStatut);
        $maxDemandeurs  = $topDemandeurs ? max(array_column($topDemandeurs, 'cnt')) : 1;
        $maxService     = $parService ? max(array_column($parService, 'cnt')) : 1;
        $moisLabels     = array_keys($parMois);
        $moisData       = array_values($parMois);

        return $this->render('statistiques/index.html.twig', [
            'parStatut'     => $parStatut,
            'parMois'       => $parMois,
            'topDemandeurs' => $topDemandeurs,
            'topArticles'   => $topArticles,
            'stocksBas'     => $stocksBas,
            'totalArticles' => $totalArticles,
            'totalDemandes' => $totalDemandes,
            'maxDemandeurs' => $maxDemandeurs,
            'moisLabels'    => $moisLabels,
            'moisData'      => $moisData,
            'parService'    => $parService,
            'maxService'    => $maxService,
        ]);
    }
}

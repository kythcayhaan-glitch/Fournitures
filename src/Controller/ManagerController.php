<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\DemandeMateriel;
use App\Entity\Article;
use App\Form\AjustementStockType;
use App\Form\ProcessDemandeType;
use App\Repository\CategoryRepository;
use App\Repository\DemandeMaterielRepository;
use App\Repository\ArticleRepository;
use App\Security\Voter\DemandeVoter;
use App\Service\StockService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/manager')]
#[IsGranted('ROLE_MANAGER')]
class ManagerController extends AbstractController
{
    public function __construct(
        private readonly DemandeMaterielRepository $demandeRepository,
        private readonly ArticleRepository $articleRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $em,
        private readonly PaginatorInterface $paginator,
        private readonly WorkflowInterface $demandeMaterielStateMachine,
        private readonly StockService $stockService,
    ) {}

    /**
     * Tableau de bord manager : toutes les demandes avec filtres.
     */
    #[Route('', name: 'app_manager_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $statut = $request->query->get('statut');
        $from = null;
        $to = null;

        if ($request->query->get('from')) {
            try {
                $from = new \DateTimeImmutable($request->query->getString('from'));
            } catch (\Exception) {}
        }
        if ($request->query->get('to')) {
            try {
                $to = new \DateTimeImmutable($request->query->getString('to'));
            } catch (\Exception) {}
        }

        $qb = $this->demandeRepository->createManagerQueryBuilder($statut ?: null, $from, $to);

        $pagination = $this->paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            20
        );

        $nbEnAttente = count($this->demandeRepository->findPending());

        return $this->render('manager/index.html.twig', [
            'pagination'  => $pagination,
            'statut'      => $statut,
            'from'        => $from,
            'to'          => $to,
            'nbEnAttente' => $nbEnAttente,
        ]);
    }

    /**
     * Approuver ou rejeter une demande.
     */
    #[Route('/demandes/{id}/process', name: 'app_manager_process', methods: ['GET', 'POST'])]
    public function process(Request $request, DemandeMateriel $demande): Response
    {
        $this->denyAccessUnlessGranted(DemandeVoter::APPROVE, $demande);

        if (!$demande->isPending()) {
            $this->addFlash('warning', 'Cette demande ne peut plus être traitée.');
            return $this->redirectToRoute('app_manager_index');
        }

        foreach ($demande->getLignes() as $ligne) {
            if ($ligne->getQuantiteServie() === 0) {
                $ligne->setQuantiteServie($ligne->getQuantiteDemandee());
            }
        }

        $form = $this->createForm(ProcessDemandeType::class, [
            'action' => 'approve',
            'lignes' => $demande->getLignes(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $action = $data['action'];
            $commentaire = $data['commentaire'] ?? null;

            if ($action === 'reject' && empty(trim((string) $commentaire))) {
                $this->addFlash('error', 'Un commentaire est obligatoire pour rejeter une demande.');
                return $this->render('manager/process.html.twig', [
                    'form'    => $form,
                    'demande' => $demande,
                ]);
            }

            if ($commentaire) {
                $demande->setCommentaire($commentaire);
            }

            if ($action === 'reject') {
                foreach ($demande->getLignes() as $ligne) {
                    $ligne->setQuantiteServie(0);
                }
            }

            if ($this->demandeMaterielStateMachine->can($demande, $action)) {
                $this->demandeMaterielStateMachine->apply($demande, $action);
                $this->em->flush();

                $label = $action === 'approve' ? 'approuvée' : 'rejetée';
                $this->addFlash('success', sprintf('La demande %s a été %s.', $demande->getReference(), $label));
            } else {
                $this->addFlash('error', 'Transition impossible.');
            }

            return $this->redirectToRoute('app_manager_index');
        }

        return $this->render('manager/process.html.twig', [
            'form'    => $form,
            'demande' => $demande,
        ]);
    }

    #[Route('/stock/{id}/ajuster', name: 'app_manager_ajuster', methods: ['GET', 'POST'])]
    public function ajusterStock(Request $request, Article $article): Response
    {
        $form = $this->createForm(AjustementStockType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $data = $form->getData();
            $motif = (string) ($data['motif'] ?? '');

            if ($data['mode'] === 'ajout') {
                $this->stockService->ajouterStock($article, (int) $data['nouvelleQuantite'], $motif ?: 'Livraison', $user);
                $this->addFlash('success', sprintf(
                    '%d %s ajouté(s) au stock de "%s".',
                    $data['nouvelleQuantite'],
                    $article->getUnit(),
                    $article->getName()
                ));
            } else {
                $this->stockService->ajustementStock($article, (int) $data['nouvelleQuantite'], $motif, $user);
                $this->addFlash('success', sprintf(
                    'Stock de "%s" ajusté à %d %s.',
                    $article->getName(),
                    $data['nouvelleQuantite'],
                    $article->getUnit()
                ));
            }

            return $this->redirectToRoute('app_manager_stock');
        }

        return $this->render('admin/inventaire_ajuster.html.twig', [
            'form'       => $form,
            'article' => $article,
        ]);
    }

    /**
     * Vue d'ensemble du stock pour le manager.
     */
    #[Route('/stock', name: 'app_manager_stock', methods: ['GET'])]
    public function stock(Request $request): Response
    {
        $search = $request->query->get('search');
        $categoryId = ($c = $request->query->get('category')) && ctype_digit((string) $c) ? (int) $c : null;

        $qb = $this->articleRepository->createAdminQueryBuilder(
            $search ?: null,
            $categoryId
        );

        $pagination = $this->paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            25
        );

        $stocksBas  = $this->articleRepository->findStockBas();
        $categories = $this->categoryRepository->findAllOrderedByName();

        return $this->render('manager/stock.html.twig', [
            'pagination'  => $pagination,
            'stocksBas'   => $stocksBas,
            'categories'  => $categories,
            'search'      => $search,
            'categoryId'  => $categoryId,
        ]);
    }
}

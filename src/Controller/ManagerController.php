<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\DemandeMateriel;
use App\Form\DeliverDemandeType;
use App\Form\ProcessDemandeType;
use App\Repository\DemandeMaterielRepository;
use App\Repository\FournitureRepository;
use App\Security\Voter\DemandeVoter;
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
        private readonly FournitureRepository $fournitureRepository,
        private readonly EntityManagerInterface $em,
        private readonly PaginatorInterface $paginator,
        private readonly WorkflowInterface $demandeMaterielStateMachine,
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

        $form = $this->createForm(ProcessDemandeType::class);
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

    /**
     * Confirmer la livraison (saisie des quantités servies).
     */
    #[Route('/demandes/{id}/deliver', name: 'app_manager_deliver', methods: ['GET', 'POST'])]
    public function deliver(Request $request, DemandeMateriel $demande): Response
    {
        $this->denyAccessUnlessGranted(DemandeVoter::DELIVER, $demande);

        if (!$demande->isApproved()) {
            $this->addFlash('warning', 'Seules les demandes approuvées peuvent être livrées.');
            return $this->redirectToRoute('app_manager_index');
        }

        $form = $this->createForm(DeliverDemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->demandeMaterielStateMachine->can($demande, 'deliver')) {
                $this->demandeMaterielStateMachine->apply($demande, 'deliver');
                $this->em->flush();

                $this->addFlash('success', sprintf(
                    'La demande %s a été marquée comme livrée et le stock mis à jour.',
                    $demande->getReference()
                ));
            } else {
                $this->addFlash('error', 'Impossible de marquer cette demande comme livrée.');
            }

            return $this->redirectToRoute('app_manager_index');
        }

        return $this->render('manager/deliver.html.twig', [
            'form'    => $form,
            'demande' => $demande,
        ]);
    }

    /**
     * Vue d'ensemble du stock pour le manager.
     */
    #[Route('/stock', name: 'app_manager_stock', methods: ['GET'])]
    public function stock(Request $request): Response
    {
        $search = $request->query->get('search');
        $categoryId = $request->query->getInt('category') ?: null;

        $qb = $this->fournitureRepository->createAdminQueryBuilder(
            $search ?: null,
            $categoryId,
            true
        );

        $pagination = $this->paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            25
        );

        $stocksBas = $this->fournitureRepository->findStockBas();

        return $this->render('manager/stock.html.twig', [
            'pagination' => $pagination,
            'stocksBas'  => $stocksBas,
            'search'     => $search,
        ]);
    }
}

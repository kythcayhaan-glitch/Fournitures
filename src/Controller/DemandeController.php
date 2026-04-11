<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\DemandeMateriel;
use App\Form\DemandeType;
use App\Repository\DemandeMaterielRepository;
use App\Security\Voter\DemandeVoter;
use App\Service\DemandeService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/demandes')]
#[IsGranted('ROLE_USER')]
class DemandeController extends AbstractController
{
    public function __construct(
        private readonly DemandeMaterielRepository $demandeRepository,
        private readonly DemandeService $demandeService,
        private readonly EntityManagerInterface $em,
        private readonly PaginatorInterface $paginator,
    ) {}

    /**
     * Liste les demandes de l'utilisateur connecté avec filtres.
     */
    #[Route('', name: 'app_demande_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $statut = $request->query->get('statut');
        $from = $request->query->get('from')
            ? new \DateTimeImmutable($request->query->getString('from'))
            : null;

        $qb = $this->demandeRepository->findByUserWithFilters($user, $statut ?: null, $from);

        $pagination = $this->paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('demande/index.html.twig', [
            'pagination' => $pagination,
            'statut'     => $statut,
            'from'       => $from,
        ]);
    }

    /**
     * Formulaire de création d'une nouvelle demande.
     */
    #[Route('/new', name: 'app_demande_new', methods: ['GET', 'POST'])]
    #[IsGranted(DemandeVoter::CREATE)]
    public function new(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $demande = new DemandeMateriel();
        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->demandeService->creerDemande($user, $demande);

            $this->addFlash('success', sprintf(
                'Votre demande %s a été créée avec succès.',
                $demande->getReference()
            ));

            return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
        }

        return $this->render('demande/new.html.twig', [
            'form'    => $form,
            'demande' => $demande,
        ]);
    }

    /**
     * Détail d'une demande.
     */
    #[Route('/{id}', name: 'app_demande_show', methods: ['GET'])]
    public function show(DemandeMateriel $demande): Response
    {
        $this->denyAccessUnlessGranted(DemandeVoter::VIEW, $demande);

        return $this->render('demande/show.html.twig', [
            'demande' => $demande,
        ]);
    }

    /**
     * Annulation d'une demande en attente par son auteur.
     */
    #[Route('/{id}/cancel', name: 'app_demande_cancel', methods: ['POST'])]
    public function cancel(Request $request, DemandeMateriel $demande): Response
    {
        $this->denyAccessUnlessGranted(DemandeVoter::CANCEL, $demande);

        if (!$this->isCsrfTokenValid('cancel_demande_' . $demande->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
        }

        $demande->setStatut('rejected');
        $demande->setCommentaire('Annulée par le demandeur');
        $demande->setProcessedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->addFlash('warning', 'Votre demande a été annulée.');
        return $this->redirectToRoute('app_demande_index');
    }
}

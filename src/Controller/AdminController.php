<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Fourniture;
use App\Entity\User;
use App\Form\AjustementStockType;
use App\Form\CategoryType;
use App\Form\FournitureType;
use App\Form\UserCreateType;
use App\Repository\CategoryRepository;
use App\Entity\DemandeMateriel;
use App\Repository\DemandeMaterielRepository;
use App\Repository\FournitureRepository;
use App\Repository\MouvementStockRepository;
use App\Repository\UserRepository;
use App\Security\Voter\FournitureVoter;
use App\Service\DemandeService;
use App\Service\StockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_MANAGER')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly FournitureRepository $fournitureRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly UserRepository $userRepository,
        private readonly MouvementStockRepository $mouvementRepository,
        private readonly DemandeMaterielRepository $demandeRepository,
        private readonly EntityManagerInterface $em,
        private readonly PaginatorInterface $paginator,
        private readonly StockService $stockService,
        private readonly DemandeService $demandeService,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    // ─── FOURNITURES ────────────────────────────────────────────────────────

    #[Route('/fournitures', name: 'app_admin_fournitures', methods: ['GET'])]
    public function fournitures(Request $request): Response
    {
        $search = $request->query->get('search');
        $categoryId = ($c = $request->query->get('category')) && ctype_digit((string) $c) ? (int) $c : null;
        $isActive = $request->query->has('active') ? (bool) $request->query->get('active') : null;

        $qb = $this->fournitureRepository->createAdminQueryBuilder(
            $search ?: null,
            $categoryId,
            $isActive
        );

        $pagination = $this->paginator->paginate($qb, $request->query->getInt('page', 1), 20);
        $categories = $this->categoryRepository->findAllOrderedByName();

        return $this->render('admin/fournitures/index.html.twig', [
            'pagination' => $pagination,
            'categories' => $categories,
            'search'     => $search,
        ]);
    }

    #[Route('/fournitures/new', name: 'app_admin_fournitures_new', methods: ['GET', 'POST'])]
    public function fournitureNew(Request $request): Response
    {
        $this->denyAccessUnlessGranted(FournitureVoter::CREATE);

        $fourniture = new Fourniture();
        $form = $this->createForm(FournitureType::class, $fourniture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($fourniture);
            $this->em->flush();
            $this->addFlash('success', 'Fourniture créée avec succès.');
            return $this->redirectToRoute('app_admin_fournitures');
        }

        return $this->render('admin/fournitures/new.html.twig', [
            'form'       => $form,
            'fourniture' => $fourniture,
        ]);
    }

    #[Route('/fournitures/{id}/edit', name: 'app_admin_fournitures_edit', methods: ['GET', 'POST'])]
    public function fournitureEdit(Request $request, Fourniture $fourniture): Response
    {
        $this->denyAccessUnlessGranted(FournitureVoter::EDIT, $fourniture);

        $form = $this->createForm(FournitureType::class, $fourniture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Fourniture mise à jour.');
            return $this->redirectToRoute('app_admin_fournitures');
        }

        return $this->render('admin/fournitures/edit.html.twig', [
            'form'       => $form,
            'fourniture' => $fourniture,
        ]);
    }

    #[Route('/fournitures/{id}/delete', name: 'app_admin_fournitures_delete', methods: ['POST'])]
    public function fournitureDelete(Request $request, Fourniture $fourniture): Response
    {
        $this->denyAccessUnlessGranted(FournitureVoter::DELETE, $fourniture);

        if ($this->isCsrfTokenValid('delete_fourniture_' . $fourniture->getId(), $request->request->get('_token'))) {
            $fourniture->setIsActive(false); // Soft delete
            $this->em->flush();
            $this->addFlash('success', 'Fourniture désactivée.');
        }

        return $this->redirectToRoute('app_admin_fournitures');
    }

    // ─── CATÉGORIES ─────────────────────────────────────────────────────────

    #[Route('/categories', name: 'app_admin_categories', methods: ['GET'])]
    public function categories(): Response
    {
        $categories = $this->categoryRepository->findWithFournitureCount();

        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/new', name: 'app_admin_categories_new', methods: ['GET', 'POST'])]
    public function categoryNew(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($category);
            $this->em->flush();
            $this->addFlash('success', 'Catégorie créée.');
            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/categories/new.html.twig', [
            'form'     => $form,
            'category' => $category,
        ]);
    }

    #[Route('/categories/{id}/edit', name: 'app_admin_categories_edit', methods: ['GET', 'POST'])]
    public function categoryEdit(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Catégorie mise à jour.');
            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/categories/edit.html.twig', [
            'form'     => $form,
            'category' => $category,
        ]);
    }

    #[Route('/categories/{id}/delete', name: 'app_admin_categories_delete', methods: ['POST'])]
    public function categoryDelete(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete_category_' . $category->getId(), $request->request->get('_token'))) {
            if ($category->getFournitures()->isEmpty()) {
                $this->em->remove($category);
                $this->em->flush();
                $this->addFlash('success', 'Catégorie supprimée.');
            } else {
                $this->addFlash('error', 'Impossible de supprimer une catégorie contenant des fournitures.');
            }
        }

        return $this->redirectToRoute('app_admin_categories');
    }

    // ─── UTILISATEURS ───────────────────────────────────────────────────────

    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(): Response
    {
        $users = $this->userRepository->findAllOrderedByName();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/new', name: 'app_admin_users_new', methods: ['GET', 'POST'])]
    public function userNew(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserCreateType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($this->hasher->hashPassword($user, $plainPassword));

            $role = $form->get('role')->getData();
            $user->setRoles($role === 'ROLE_USER' ? [] : [$role]);

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', sprintf('Utilisateur %s créé avec succès.', $user->getFullName()));
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_users_edit', methods: ['GET', 'POST'])]
    public function userEdit(Request $request, User $user): Response
    {
        $currentRole = 'ROLE_USER';
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $currentRole = 'ROLE_ADMIN';
        } elseif (in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            $currentRole = 'ROLE_MANAGER';
        }

        $form = $this->createForm(UserCreateType::class, $user, [
            'require_password' => false,
            'show_active'      => true,
            'initial_role'     => $currentRole,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
            }

            $role = $form->get('role')->getData();
            $user->setRoles($role === 'ROLE_USER' ? [] : [$role]);

            $this->em->flush();
            $this->addFlash('success', sprintf('Utilisateur %s mis à jour.', $user->getFullName()));
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function userDelete(Request $request, User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            if (!$user->getDemandes()->isEmpty() || !$user->getDemandesTraitees()->isEmpty() || !$user->getMouvementsStock()->isEmpty()) {
                $this->addFlash('error', sprintf(
                    'Impossible de supprimer %s : il a des demandes ou mouvements de stock associés. Désactivez le compte à la place.',
                    $user->getFullName()
                ));
                return $this->redirectToRoute('app_admin_users');
            }

            $this->em->remove($user);
            $this->em->flush();
            $this->addFlash('success', sprintf('Utilisateur %s supprimé.', $user->getFullName()));
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/set-role', name: 'app_admin_users_set_role', methods: ['POST'])]
    public function userSetRole(Request $request, User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre rôle.');
            return $this->redirectToRoute('app_admin_users');
        }

        if (!$this->isCsrfTokenValid('set_role_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        $allowed = ['ROLE_USER', 'ROLE_MANAGER', 'ROLE_ADMIN'];
        $role = $request->request->get('role');

        if (!in_array($role, $allowed, true)) {
            $this->addFlash('error', 'Rôle invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        $roles = $role === 'ROLE_USER' ? [] : [$role];
        $user->setRoles($roles);
        $this->em->flush();

        $this->addFlash('success', sprintf(
            'Rôle de %s mis à jour : %s.',
            $user->getFullName(),
            $role
        ));

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/toggle', name: 'app_admin_users_toggle', methods: ['POST'])]
    public function userToggle(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('toggle_user_' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $this->em->flush();
            $status = $user->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', sprintf('Utilisateur %s %s.', $user->getFullName(), $status));
        }

        return $this->redirectToRoute('app_admin_users');
    }

    // ─── INVENTAIRE ─────────────────────────────────────────────────────────

    #[Route('/inventaire', name: 'app_admin_inventaire', methods: ['GET'])]
    public function inventaire(Request $request): Response
    {
        $search = $request->query->get('search');
        $qb = $this->fournitureRepository->createAdminQueryBuilder($search ?: null, null, null);

        $pagination = $this->paginator->paginate($qb, $request->query->getInt('page', 1), 25);

        return $this->render('admin/inventaire.html.twig', [
            'pagination' => $pagination,
            'search'     => $search,
        ]);
    }

    #[Route('/inventaire/{id}/ajuster', name: 'app_admin_inventaire_ajuster', methods: ['GET', 'POST'])]
    public function ajusterStock(Request $request, Fourniture $fourniture): Response
    {
        $form = $this->createForm(AjustementStockType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $data = $form->getData();

            $this->stockService->ajustementStock(
                $fourniture,
                (int) $data['nouvelleQuantite'],
                (string) $data['motif'],
                $user
            );

            $this->addFlash('success', sprintf(
                'Stock de "%s" ajusté à %d %s.',
                $fourniture->getName(),
                $data['nouvelleQuantite'],
                $fourniture->getUnit()
            ));

            return $this->redirectToRoute('app_admin_inventaire');
        }

        return $this->render('admin/inventaire_ajuster.html.twig', [
            'form'       => $form,
            'fourniture' => $fourniture,
        ]);
    }

    // ─── HISTORIQUE MOUVEMENTS ──────────────────────────────────────────────

    #[Route('/historique', name: 'app_admin_historique', methods: ['GET'])]
    public function historique(Request $request): Response
    {
        $type = $request->query->get('type');
        $from = null;
        $to = null;

        if ($request->query->get('from')) {
            try { $from = new \DateTimeImmutable($request->query->getString('from')); } catch (\Exception) {}
        }
        if ($request->query->get('to')) {
            try { $to = new \DateTimeImmutable($request->query->getString('to')); } catch (\Exception) {}
        }

        $typeEnum = $type ? \App\Enum\TypeMouvement::tryFrom($type) : null;
        $qb = $this->mouvementRepository->createHistoriqueQueryBuilder($typeEnum, $from, $to);

        $pagination = $this->paginator->paginate($qb, $request->query->getInt('page', 1), 30);

        return $this->render('admin/historique.html.twig', [
            'pagination' => $pagination,
            'type'       => $type,
            'from'       => $from,
            'to'         => $to,
        ]);
    }

    // ─── SUPPRESSION DEMANDES ────────────────────────────────────────────────

    #[Route('/demandes/{id}/delete', name: 'app_admin_demande_delete', methods: ['POST'])]
    public function demandeDelete(Request $request, DemandeMateriel $demande): Response
    {
        if (!$this->isCsrfTokenValid('delete_demande_' . $demande->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_manager_index');
        }

        $ref = $demande->getReference();
        $this->demandeService->supprimerDemande($demande);
        $this->addFlash('success', sprintf('Demande %s supprimée.', $ref));

        return $this->redirectToRoute('app_manager_index');
    }

    #[Route('/demandes/delete-all', name: 'app_admin_demandes_delete_all', methods: ['POST'])]
    public function demandesDeleteAll(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_all_demandes', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_manager_index');
        }

        $count = $this->demandeService->supprimerToutesDemandes();
        $this->addFlash('success', sprintf('%d demande(s) supprimée(s). Le stock n\'a pas été modifié.', $count));

        return $this->redirectToRoute('app_manager_index');
    }
}

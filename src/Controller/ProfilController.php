<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfilController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    #[Route('', name: 'app_profil', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $profilForm   = $this->createForm(ProfilType::class, $user);
        $passwordForm = $this->createForm(ChangePasswordType::class);

        return $this->render('profil/edit.html.twig', [
            'profilForm'   => $profilForm,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route('/infos', name: 'app_profil_infos', methods: ['POST'])]
    public function updateInfos(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');
            return $this->redirectToRoute('app_profil');
        }

        $passwordForm = $this->createForm(ChangePasswordType::class);

        return $this->render('profil/edit.html.twig', [
            'profilForm'   => $form,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route('/mot-de-passe', name: 'app_profil_password', methods: ['POST'])]
    public function changePassword(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();

            if (!$this->hasher->isPasswordValid($user, $currentPassword)) {
                $form->get('currentPassword')->addError(
                    new \Symfony\Component\Form\FormError('Mot de passe actuel incorrect.')
                );
            } else {
                $newPassword = $form->get('newPassword')->getData();
                $user->setPassword($this->hasher->hashPassword($user, $newPassword));
                $this->em->flush();
                $this->addFlash('success', 'Mot de passe modifié avec succès.');
                return $this->redirectToRoute('app_profil');
            }
        }

        $profilForm = $this->createForm(ProfilType::class, $user);

        return $this->render('profil/edit.html.twig', [
            'profilForm'   => $profilForm,
            'passwordForm' => $form,
        ]);
    }
}

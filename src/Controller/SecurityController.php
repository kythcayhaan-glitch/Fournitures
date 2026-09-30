<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Yaml\Yaml;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $changelog = Yaml::parseFile($this->getParameter('kernel.project_dir') . '/config/changelog.yaml');

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
            'changelog'     => $changelog['changelog'] ?? [],
        ]);
    }

    #[Route('/login/mot-de-passe-oublie', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, UserRepository $userRepository, UriSigner $uriSigner, MailerInterface $mailer): Response
    {
        if (!$request->isMethod('POST')) {
            return $this->render('security/forgot_password.html.twig');
        }

        if (!$this->isCsrfTokenValid('forgot_password', $request->getPayload()->getString('_csrf_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $userRepository->findOneBy(['email' => trim($request->getPayload()->getString('email')), 'isActive' => true]);

        if ($user) {
            $resetUrl = $uriSigner->sign($this->generateUrl('app_reset_password', [
                'id' => $user->getId(),
                'h'  => $this->passwordFingerprint($user),
            ], UrlGeneratorInterface::ABSOLUTE_URL), new \DateInterval('PT1H'));

            $mailer->send((new TemplatedEmail())
                ->to($user->getEmail())
                ->subject('Fournitures - Réinitialisation du mot de passe')
                ->textTemplate('emails/reset_password.txt.twig')
                ->context(['user' => $user, 'resetUrl' => $resetUrl]));
        }

        $this->addFlash('success', 'Si cet email correspond à un compte, un lien de réinitialisation vient d\'y être envoyé.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/login/reinitialiser/{id}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, User $user, UriSigner $uriSigner, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        if (!$uriSigner->checkRequest($request) || $request->query->get('h') !== $this->passwordFingerprint($user)) {
            $this->addFlash('error', 'Ce lien est invalide ou a expiré. Faites une nouvelle demande.');

            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ChangePasswordType::class, null, ['require_current' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('newPassword')->getData()));
            $user->setMustChangePassword(false);
            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe modifié. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank — it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Change dès que le mot de passe change : le lien ne sert qu'une fois.
     */
    private function passwordFingerprint(User $user): string
    {
        return substr(hash('sha256', $user->getPassword()), 0, 16);
    }
}

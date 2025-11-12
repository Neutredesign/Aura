<?php

namespace App\Controller;

use App\Entity\Garment;
use App\Entity\Outfit;
use App\Form\ProfileEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/profil')]
class ProfileController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        // Statistiques
        $garmentsCount  = $this->em->getRepository(Garment::class)->count(['user' => $user]);
        $outfitsCount   = $this->em->getRepository(Outfit::class)->count(['user' => $user]);
        $favoritesCount = 0; // V1 : pas encore d'entité favoris

        return $this->render('profile/index.html.twig', [
            'user'           => $user,
            'garmentsCount'  => $garmentsCount,
            'outfitsCount'   => $outfitsCount,
            'favoritesCount' => $favoritesCount,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET','POST'])]
    public function edit(
        Request $request,
        #[Autowire('%upload_dir_avatars%')] string $uploadDir,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Fichier d'avatar (non mappé -> FileType mapped:false dans le FormType)
            $file = $form->get('avatarFile')->getData();
            if ($file) {
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }

                // Supprime l'ancienne image si présente
                $old = $user->getAvatarUrl();
                if ($old && is_file($projectDir . '/public' . $old)) {
                    @unlink($projectDir . '/public' . $old);
                }

                $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'png');
                $safeName = 'avatar_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $file->move($uploadDir, $safeName);

                $user->setAvatarUrl('/uploads/avatars/' . $safeName);
            }

            // username est mappé directement par le formulaire
            $this->em->flush();

            $this->addFlash('success', 'Profil mis à jour');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/mot-de-passe', name: 'app_profile_password', methods: ['GET','POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        if ($request->isMethod('POST')) {
            $old     = (string) $request->request->get('old_password');
            $new     = (string) $request->request->get('new_password');
            $confirm = (string) $request->request->get('confirm_password');

            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            // 1) Vérification ancien mot de passe
            if (!$hasher->isPasswordValid($user, $old)) {
                $this->addFlash('error', 'Ancien mot de passe incorrect.');
                return $this->redirectToRoute('app_profile_password');
            }

            // 2) Vérifs de base
            if ($new !== $confirm) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_profile_password');
            }

            if (mb_strlen($new) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('app_profile_password');
            }

            // 3) Hash + sauvegarde
            $user->setPassword($hasher->hashPassword($user, $new));
            $this->em->flush();

            $this->addFlash('success', 'Mot de passe mis à jour avec succès');
            return $this->redirectToRoute('app_profile');
        }

        // GET -> affiche le formulaire
        return $this->render('profile/password.html.twig');
    }

    #[Route('/supprimer', name: 'app_profile_delete', methods: ['POST'])]
    public function deleteAccount(Request $request, Security $security): Response
    {
        if (!$this->isCsrfTokenValid('delete_account', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_profile');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        foreach ($this->em->getRepository(Garment::class)->findBy(['user' => $user]) as $g) {
            $this->em->remove($g);
        }
        foreach ($this->em->getRepository(Outfit::class)->findBy(['user' => $user]) as $o) {
            $this->em->remove($o);
        }

        $this->em->remove($user);
        $this->em->flush();

        // Déconnecte la session courante proprement
        $security->logout(false);

        $this->addFlash('success', 'Compte supprimé avec succès.');
        return $this->redirectToRoute('app_login');
    }
}

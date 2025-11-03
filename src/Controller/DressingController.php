<?php

namespace App\Controller;

use App\Entity\Garment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dressing')]
class DressingController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('', name: 'app_dressing', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        $garments = $this->em->getRepository(Garment::class)
            ->createQueryBuilder('g')
            ->andWhere('g.user = :u')->setParameter('u', $user)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('dressing/index.html.twig', [
            'garments' => $garments,
        ]);
    }

    #[Route('/upload', name: 'app_dressing_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        #[Autowire('%upload_dir_garments%')] string $uploadDirGarments,
    ): Response {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');
        if (!$file) {
            $this->addFlash('error', 'Image manquante.');
            return $this->redirectToRoute('app_dressing');
        }

        $ext = strtolower($file->guessExtension() ?? 'bin');
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $this->addFlash('error', 'Format invalide (png/jpg/jpeg/webp).');
            return $this->redirectToRoute('app_dressing');
        }

        // Crée le dossier si nécessaire
        if (!is_dir($uploadDirGarments)) {
            @mkdir($uploadDirGarments, 0775, true);
        }

        // Sauvegarde
        $filename   = bin2hex(random_bytes(8)) . '.' . $ext;
        $file->move($uploadDirGarments, $filename);
        $publicPath = '/uploads/garments/' . $filename;

        // Enregistrement BDD
        $g = new Garment();
        $g->setUser($this->getUser());
        $g->setName($request->request->get('name', 'Vêtement'));
        $g->setCategory($request->request->get('category', 'top'));
        $g->setColor($request->request->get('color') ?: null);
        $g->setSeason($request->request->get('season') ?: null);
        $tags = json_decode((string) $request->request->get('styleTags', '[]'), true);
        $g->setStyleTags(is_array($tags) ? $tags : []);
        $g->setImageUrl($publicPath);
        $g->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($g);
        $this->em->flush();

        $this->addFlash('success', 'Vêtement ajouté avec succès !');
        return $this->redirectToRoute('app_dressing');
    }

    #[Route('/{id}/delete', name: 'app_garment_delete', methods: ['POST'])]
    public function delete(Garment $garment, Request $request): Response
    {
        // Vérif CSRF
        if (!$this->isCsrfTokenValid('delete_garment_' . $garment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_dressing');
        }

        // Vérif propriétaire
        if ($garment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->em->remove($garment);
        $this->em->flush();

        $this->addFlash('success', 'Le vêtement a bien été supprimé.');
        return $this->redirectToRoute('app_dressing');
    }
}

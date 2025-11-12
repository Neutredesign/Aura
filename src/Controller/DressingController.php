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
        // ⚠️ Assure-toi que ce paramètre pointe vers "%kernel.project_dir%/public/uploads/garments"
        #[Autowire('%upload_dir_garments%')] string $uploadDirGarments,
    ): Response {
        /** @var UploadedFile|null $uploaded */
        $uploaded = $request->files->get('image'); // DOIT correspondre à name="image" dans le formulaire

        // 1) Présence du fichier
        if (!$uploaded instanceof UploadedFile) {
            $this->addFlash('error', 'Image manquante (aucun fichier reçu).');
            return $this->redirectToRoute('app_dressing');
        }

        // 2) Upload PHP valide
        if (!$uploaded->isValid()) {
            $this->addFlash('error', 'Upload invalide (taille/type).');
            return $this->redirectToRoute('app_dressing');
        }

        // 3) Sécurité MIME + extension
        $allowedMimes = ['image/png', 'image/jpeg', 'image/webp'];
        $mime = (string) $uploaded->getMimeType();
        if (!in_array($mime, $allowedMimes, true)) {
            $this->addFlash('error', 'Format invalide (png/jpg/jpeg/webp).');
            return $this->redirectToRoute('app_dressing');
        }

        $ext = strtolower($uploaded->guessExtension() ?: $uploaded->getClientOriginalExtension() ?: 'bin');
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $this->addFlash('error', 'Extension non supportée.');
            return $this->redirectToRoute('app_dressing');
        }

        // 4) Dossier d’upload
        if (!is_dir($uploadDirGarments)) {
            @mkdir($uploadDirGarments, 0775, true);
        }

        // 5) Nom de fichier aléatoire + déplacement
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;

        try {
            $uploaded->move($uploadDirGarments, $filename);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible de sauvegarder le fichier.');
            return $this->redirectToRoute('app_dressing');
        }

        $publicPath = '/uploads/garments/' . $filename;

        // 6) Enregistrement en BDD
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

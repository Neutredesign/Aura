<?php

namespace App\Controller;

use App\Entity\Outfit;
use App\Repository\GarmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tenues')]
class OutfitController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    // -----------------------
    // LISTE
    // -----------------------
    #[Route('', name: 'app_outfit_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $outfits = $this->em->getRepository(Outfit::class)->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('outfit/index.html.twig', [
            'outfits' => $outfits,
        ]);
    }

    // CREATION - FORMULAIRE (GET)
    #[Route('/new', name: 'app_outfit_new', methods: ['GET'])]
    public function new(GarmentRepository $garmentRepo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Vêtements de l’utilisateur pour composer la tenue
        $garments = $garmentRepo->findByUserOrdered($this->getUser());

        return $this->render('outfit/create.html.twig', [
            'garments'     => $garments,
            'initialItems' => json_encode([]),
            'initialName'  => 'Ma tenue',
            'outfitId'     => null,
        ]);
    }

    // CREATION - ENREGISTRE (POST)
    #[Route('/new', name: 'app_outfit_store', methods: ['POST'])]
    public function store(
        Request $request,
        #[Autowire('%kernel.project_dir%')] string $projectDir
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['snapshot'], $data['items'])) {
            return new JsonResponse(['error' => 'Payload invalide'], 400);
        }

        $name = trim($data['name'] ?? 'Ma tenue');

        // 1) PNG base64 -> binaire
        $snapshot = $data['snapshot'];
        if (str_starts_with($snapshot, 'data:image')) {
            [, $snapshot] = explode(',', $snapshot, 2);
        }
        $binary = base64_decode($snapshot);
        if ($binary === false) {
            return new JsonResponse(['error' => 'Base64 invalide'], 400);
        }

        // Garde-fou taille (~3 Mo)
        if (strlen($snapshot) > 3 * 1024 * 1024 * 4 / 3) {
            return new JsonResponse(['error' => 'Image trop lourde'], 413);
        }

        // 2) Dossier upload
        $uploadDir = $projectDir . '/public/uploads/outfits';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        if (!is_writable($uploadDir)) {
            return new JsonResponse(['error' => 'Dossier non inscriptible'], 500);
        }

        $filename = 'outfit_' . bin2hex(random_bytes(6)) . '.png';
        file_put_contents($uploadDir . '/' . $filename, $binary);

        // 3) Sauvegarde BDD
        $outfit = new Outfit();
        $outfit->setName($name);
        $outfit->setItems(json_decode($data['items'], true) ?? []);
        $outfit->setSnapshotUrl('/uploads/outfits/' . $filename);
        $outfit->setUser($this->getUser());
        if (method_exists($outfit, 'setCreatedAt')) {
            $outfit->setCreatedAt(new \DateTimeImmutable());
        }

        $this->em->persist($outfit);
        $this->em->flush();

        return new JsonResponse(['ok' => true, 'id' => $outfit->getId()], 201);
    }

    // OUVRIR / EDITER (GET)
    #[Route('/{id}', name: 'app_outfit_show', methods: ['GET'])]
    public function show(Outfit $outfit, GarmentRepository $garmentRepo): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $outfit);

        $garments = $garmentRepo->findByUserOrdered($this->getUser());

        return $this->render('outfit/create.html.twig', [
            'garments'     => $garments,
            'initialItems' => json_encode($outfit->getItems() ?? []),
            'initialName'  => $outfit->getName(),
            'outfitId'     => $outfit->getId(),
        ]);
    }

    // METTRE A JOUR (POST)
    #[Route('/{id}/update', name: 'app_outfit_update', methods: ['POST'])]
    public function update(
        Outfit $outfit,
        Request $request,
        #[Autowire('%kernel.project_dir%')] string $projectDir
    ): JsonResponse {
        $this->denyAccessUnlessGranted('EDIT', $outfit);

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['snapshot'], $data['items'])) {
            return new JsonResponse(['error' => 'Payload invalide'], 400);
        }

        $name = trim($data['name'] ?? $outfit->getName());

        // 1) Base64 -> binaire
        $snapshot = $data['snapshot'];
        if (str_starts_with($snapshot, 'data:image')) {
            [, $snapshot] = explode(',', $snapshot, 2);
        }
        $binary = base64_decode($snapshot);
        if ($binary === false) {
            return new JsonResponse(['error' => 'Base64 invalide'], 400);
        }

        // 2) Dossier
        $uploadDir = $projectDir . '/public/uploads/outfits';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $filename = 'outfit_' . bin2hex(random_bytes(6)) . '.png';
        file_put_contents($uploadDir . '/' . $filename, $binary);

        // 3) Supprimer ancien snapshot si présent
        $old = $outfit->getSnapshotUrl();
        if ($old && is_file($projectDir . '/public' . $old)) {
            @unlink($projectDir . '/public' . $old);
        }

        // 4) MAJ BDD
        $outfit->setName($name);
        $outfit->setItems(json_decode($data['items'], true) ?? []);
        $outfit->setSnapshotUrl('/uploads/outfits/' . $filename);

        $this->em->flush();

        return new JsonResponse(['ok' => true, 'id' => $outfit->getId()], 200);
    }

    // INSPIRE — OpenAI si clé, sinon fallback local
    #[Route('/inspire', name: 'app_outfit_inspire', methods: ['GET'])]
    public function inspire(
        GarmentRepository $garmentRepo,
        #[Autowire('%openai.api_key%')] ?string $openAIApiKey,
        HttpClientInterface $httpClient,
        #[Autowire('%openai.model%')] ?string $openAiModel = 'gpt-4o-mini',
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $garments = $garmentRepo->findByUserOrdered($this->getUser());
        if (!$garments) {
            return new JsonResponse(['items' => []]);
        }

        // Normalisation
        $catalog = [];
        foreach ($garments as $g) {
            $url = $g->getImageUrl();
            if (str_starts_with($url, 'public/')) {
                $url = substr($url, 6);
            }
            $catalog[] = [
                'id'       => $g->getId(),
                'name'     => (string) $g->getName(),
                'category' => (string) $g->getCategory(),
                'color'    => $g->getColor(),
                'season'   => $g->getSeason(),
                'url'      => $url,
            ];
        }

        // Positions par défaut pour 4 items max
        $positions = [
            ['left'=>80,  'top'=>60,  'w'=>280],
            ['left'=>360, 'top'=>70,  'w'=>260],
            ['left'=>120, 'top'=>300, 'w'=>260],
            ['left'=>420, 'top'=>320, 'w'=>240],
        ];

        // Fallback si pas de clé
        if (empty($openAIApiKey)) {
            shuffle($catalog);
            $pick = array_slice($catalog, 0, min(4, count($catalog)));
            $resp = [];
            foreach ($pick as $i => $g) {
                $p = $positions[$i % count($positions)];
                $resp[] = ['url'=>$g['url'], 'left'=>$p['left'], 'top'=>$p['top'], 'width'=>$p['w']];
            }
            return new JsonResponse(['items' => $resp, 'source' => 'local-no-key']);
        }

        // Appel OpenAI
        try {
            $system = "Tu es un assistant styliste. Tu reçois un catalogue (JSON).
Retourne STRICTEMENT un JSON {\"pick\":[{\"id\":...},{\"id\":...}]} (max 4 ids) cohérents (haut/bas/chaussures/accessoire),
sans aucun autre texte.";

            $userMsg = json_encode(['catalog' => $catalog], JSON_UNESCAPED_UNICODE);

            $response = $httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$openAIApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => $openAiModel ?? 'gpt-4o-mini',
                    'temperature' => 0.7,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user',   'content' => $userMsg],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ],
                'timeout' => 25,
            ]);

            $data = $response->toArray(false);
            $text = $data['choices'][0]['message']['content'] ?? '{}';
            $json = json_decode($text, true);

            $byId = [];
            foreach ($catalog as $g) { $byId[$g['id']] = $g; }

            $resp = [];
            if (isset($json['pick']) && is_array($json['pick'])) {
                $i = 0;
                foreach ($json['pick'] as $item) {
                    $id = $item['id'] ?? null;
                    if ($id && isset($byId[$id])) {
                        $p = $positions[$i % count($positions)];
                        $resp[] = ['url'=>$byId[$id]['url'], 'left'=>$p['left'], 'top'=>$p['top'], 'width'=>$p['w']];
                        $i++;
                        if ($i >= 4) break;
                    }
                }
            }

            if (!$resp) {
                shuffle($catalog);
                $pick = array_slice($catalog, 0, min(4, count($catalog)));
                foreach ($pick as $k => $g) {
                    $p = $positions[$k % count($positions)];
                    $resp[] = ['url'=>$g['url'], 'left'=>$p['left'], 'top'=>$p['top'], 'width'=>$p['w']];
                }
                return new JsonResponse(['items' => $resp, 'source' => 'fallback-local']);
            }

            return new JsonResponse(['items' => $resp, 'source' => 'openai']);
        } catch (\Throwable $e) {
            shuffle($catalog);
            $pick = array_slice($catalog, 0, min(4, count($catalog)));
            $resp = [];
            foreach ($pick as $i => $g) {
                $p = $positions[$i % count($positions)];
                $resp[] = ['url'=>$g['url'], 'left'=>$p['left'], 'top'=>$p['top'], 'width'=>$p['w']];
            }
            return new JsonResponse([
                'items'  => $resp,
                'source' => 'fallback-local',
                'error'  => 'AI error: '.$e->getMessage(),
            ]);
        }
    }

    // SUPPRIMER (POST)
    #[Route('/{id}/delete', name: 'app_outfit_delete', methods: ['POST'])]
    public function delete(
        Outfit $outfit,
        Request $request,
        #[Autowire('%kernel.project_dir%')] string $projectDir
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('DELETE', $outfit);

        $submittedToken = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_outfit_' . $outfit->getId(), $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_outfit_index');
        }

        // Supprimer le fichier snapshot
        $file = $projectDir . '/public' . $outfit->getSnapshotUrl();
        if (is_file($file)) {
            @unlink($file);
        }

        $this->em->remove($outfit);
        $this->em->flush();

        $this->addFlash('success', 'Tenue supprimée');
        return $this->redirectToRoute('app_outfit_index');
    }
}

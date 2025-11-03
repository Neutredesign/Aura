<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\GarmentRepository;
use App\Repository\OutfitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    public function __construct(
        private UserRepository $users,
        private GarmentRepository $garments,
        private OutfitRepository $outfits
    ) {}

    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/index.html.twig', [
            'usersCount'    => $this->users->count([]),
            'garmentsCount' => $this->garments->count([]),
            'outfitsCount'  => $this->outfits->count([]),
        ]);
    }
}

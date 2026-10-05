<?php

namespace App\Controller;

use App\Repository\EtapeFabricationRepository;
use App\Repository\OrdreFabricationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class OrdreFabricationController extends AbstractController
{
    #[Route('/ordre/fabrication', name: 'app_ordre_fabrication')]
    public function index(
        OrdreFabricationRepository $repository
    ): Response {
        $ordres = $repository->findAll();

        return $this->render('ordre_fabrication/index.html.twig', [
            'ordres' => $ordres,
        ]);
    }

    #[Route(
        '/ordre/fabrication/{id}/etape/{etapeId}/statut',
        name: 'app_ordre_fabrication_etape_statut',
        methods: ['POST']
    )]
    public function changerStatutEtape(
        int $id,
        int $etapeId,
        Request $request,
        EntityManagerInterface $entityManager,
        EtapeFabricationRepository $etapeRepository,
        OrdreFabricationRepository $ordreRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Récupération de l'ordre de fabrication
        $ordre = $ordreRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException(
                'Ordre de fabrication introuvable.'
            );
        }

        // Récupération de l'étape
        $etape = $etapeRepository->find($etapeId);

        if (!$etape) {
            throw $this->createNotFoundException(
                'Étape introuvable.'
            );
        }

        // Vérification que l'étape appartient bien à cet OF
        if ($etape->getOrdreFabrication() !== $ordre) {
            throw $this->createAccessDeniedException(
                'Cette étape n’appartient pas à cet ordre de fabrication.'
            );
        }

        // Vérification du token CSRF
        $token = $request->request->get('_token');

        if (!$csrfTokenManager->isTokenValid(
            new CsrfToken('modifier_etape', $token)
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        // Récupération du nouveau statut
        $nouveauStatut = $request->request->get('statut');

        // Vérification du statut
        if (!in_array(
            $nouveauStatut,
            ['A_FAIRE', 'EN_COURS', 'TERMINEE'],
            true
        )) {
            throw $this->createAccessDeniedException(
                'Statut invalide.'
            );
        }

        // Modification
        $etape->setStatut($nouveauStatut);

        // Enregistrement en base
        $entityManager->flush();

        // Retour au Kanban
        return $this->redirectToRoute(
            'app_ordre_fabrication'
        );
    }
}
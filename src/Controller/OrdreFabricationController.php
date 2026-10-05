<?php

namespace App\Controller;

use App\Entity\EtapeFabrication;
use App\Entity\OrdreFabrication;
use App\Form\OrdreFabricationType;
use App\Repository\EtapeFabricationRepository;
use App\Repository\OrdreFabricationRepository;
use App\Security\Voter\OrdreFabricationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
        '/ordre/fabrication/nouveau',
        name: 'app_ordre_fabrication_new'
    )]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $ordre = new OrdreFabrication();

        $ordre->setDateCreation(new \DateTimeImmutable());
        $ordre->setStatut('EN_ATTENTE');
        $ordre->setUser($this->getUser());

        $form = $this->createForm(
            OrdreFabricationType::class,
            $ordre
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $etapes = [
                [
                    'nom' => 'Découpe',
                    'ordre' => 1,
                ],
                [
                    'nom' => 'Assemblage',
                    'ordre' => 2,
                ],
                [
                    'nom' => 'Contrôle',
                    'ordre' => 3,
                ],
            ];

            foreach ($etapes as $donnees) {

                $etape = new EtapeFabrication();

                $etape->setNom($donnees['nom']);
                $etape->setOrdre($donnees['ordre']);
                $etape->setStatut('A_FAIRE');
                $etape->setOrdreFabrication($ordre);

                $entityManager->persist($etape);
            }

            $entityManager->persist($ordre);

            $entityManager->flush();

            return $this->redirectToRoute(
                'app_ordre_fabrication'
            );
        }

        return $this->render(
            'ordre_fabrication/new.html.twig',
            [
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/ordre/fabrication/{id}/modifier',
        name: 'app_ordre_fabrication_edit'
    )]
    #[IsGranted('ROLE_USER')]
    public function edit(
        int $id,
        OrdreFabricationRepository $ordreRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $ordre = $ordreRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException(
                'Ordre de fabrication introuvable.'
            );
        }
$this->denyAccessUnlessGranted(
    OrdreFabricationVoter::EDIT,
    $ordre
);
        $form = $this->createForm(
            OrdreFabricationType::class,
            $ordre
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            return $this->redirectToRoute(
                'app_ordre_fabrication'
            );
        }

        return $this->render(
            'ordre_fabrication/edit.html.twig',
            [
                'ordre' => $ordre,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/ordre/fabrication/{id}/etape/{etapeId}/statut',
        name: 'app_ordre_fabrication_etape_statut',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function changerStatutEtape(
        int $id,
        int $etapeId,
        Request $request,
        EntityManagerInterface $entityManager,
        EtapeFabricationRepository $etapeRepository,
        OrdreFabricationRepository $ordreRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $ordre = $ordreRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException(
                'Ordre de fabrication introuvable.'
            );
        }

        // Vérification avec le Voter
        $this->denyAccessUnlessGranted(
            OrdreFabricationVoter::EDIT,
            $ordre
        );

        $etape = $etapeRepository->find($etapeId);

        if (!$etape) {
            throw $this->createNotFoundException(
                'Étape introuvable.'
            );
        }

        if ($etape->getOrdreFabrication() !== $ordre) {
            throw $this->createAccessDeniedException(
                'Cette étape n’appartient pas à cet ordre de fabrication.'
            );
        }

        $token = $request->request->get('_token');

        if (!$csrfTokenManager->isTokenValid(
            new CsrfToken('modifier_etape', $token)
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $nouveauStatut = $request->request->get('statut');

        if (!in_array(
            $nouveauStatut,
            [
                'A_FAIRE',
                'EN_COURS',
                'TERMINEE',
            ],
            true
        )) {
            throw $this->createAccessDeniedException(
                'Statut invalide.'
            );
        }

        $etape->setStatut($nouveauStatut);

        $entityManager->flush();

        return $this->redirectToRoute(
            'app_ordre_fabrication'
        );
    }
}
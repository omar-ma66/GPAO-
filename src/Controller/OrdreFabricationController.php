<?php

namespace App\Controller;

use App\Entity\EtapeFabrication;
use App\Entity\OrdreFabrication;
use App\Entity\TypeEtape;
use App\Entity\User;
use App\Form\OrdreFabricationType;
use App\Repository\OrdreFabricationRepository;
use App\Repository\TypeEtapeRepository;
use App\Security\Voter\OrdreFabricationVoter;
use App\Service\OrdreFabricationNumeroGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ordre/fabrication')]
class OrdreFabricationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrdreFabricationRepository $ordreFabricationRepository,
        private TypeEtapeRepository $typeEtapeRepository,
        private OrdreFabricationNumeroGenerator $numeroGenerator,
    ) {
    }

    #[Route('', name: 'app_ordre_fabrication', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('ordre_fabrication/index.html.twig', [
            'ordres' => $this->ordreFabricationRepository->findActifs(),
        ]);
    }

    #[Route('/archives', name: 'app_ordre_fabrication_archives', methods: ['GET'])]
    public function archives(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('ordre_fabrication/archives.html.twig', [
            'ordres' => $this->ordreFabricationRepository->findArchives(),
        ]);
    }

    #[Route('/new', name: 'app_ordre_fabrication_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ordre = new OrdreFabrication();

        $form = $this->createForm(OrdreFabricationType::class, $ordre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();

            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $ordre->setNumero($this->numeroGenerator->generate());
            $ordre->setDateCreation(new \DateTimeImmutable());
            $ordre->setStatut('EN_ATTENTE');
            $ordre->setUser($user);
            $ordre->setDateArchivage(null);

            $this->entityManager->persist($ordre);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Ordre de fabrication créé. Vous devez maintenant définir ses étapes.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication_configurer_etapes',
                ['id' => $ordre->getId()]
            );
        }

        return $this->render('ordre_fabrication/new.html.twig', [
            'ordre' => $ordre,
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id<\d+>}/edit',
        name: 'app_ordre_fabrication_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(Request $request, int $id): Response
    {
        $ordre = $this->ordreFabricationRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException();
        }

        if ($ordre->getDateArchivage() !== null) {
            throw $this->createAccessDeniedException(
                'Un OF archivé ne peut plus être modifié.'
            );
        }

        $this->denyAccessUnlessGranted(
            OrdreFabricationVoter::EDIT,
            $ordre
        );

        $form = $this->createForm(OrdreFabricationType::class, $ordre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->recalculerStatutOrdre($ordre);

            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Ordre de fabrication modifié avec succès.'
            );

            return $this->redirectToRoute('app_ordre_fabrication');
        }

        return $this->render('ordre_fabrication/edit.html.twig', [
            'ordre' => $ordre,
            'form' => $form,
        ]);
    }

    /*
     * ============================================================
     * CONFIGURATION DES ETAPES
     * ============================================================
     */

    #[Route(
        '/{id<\d+>}/etapes',
        name: 'app_ordre_fabrication_configurer_etapes',
        methods: ['GET']
    )]
    public function configurerEtapes(int $id): Response
    {
        $ordre = $this->ordreFabricationRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException();
        }

        if ($ordre->getDateArchivage() !== null) {
            throw $this->createAccessDeniedException(
                'Un OF archivé ne peut plus être modifié.'
            );
        }

        $this->denyAccessUnlessGranted(
            OrdreFabricationVoter::EDIT,
            $ordre
        );

        $etapes = $this->getEtapesTriees($ordre);

        return $this->render(
            'ordre_fabrication/configurer_etapes.html.twig',
            [
                'ordre' => $ordre,
                'etapes' => $etapes,
                'typesEtapes' => $this->typeEtapeRepository->findBy(
                    [],
                    ['nom' => 'ASC']
                ),
                'etapesCommencees' => $this->ordreEstCommence($ordre),
            ]
        );
    }

    #[Route(
        '/{id<\d+>}/etapes/ajouter',
        name: 'app_ordre_fabrication_etape_ajouter',
        methods: ['POST']
    )]
    public function ajouterEtape(
        Request $request,
        int $id
    ): Response {
        $ordre = $this->ordreFabricationRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(
            OrdreFabricationVoter::EDIT,
            $ordre
        );

        if ($this->ordreEstCommence($ordre)) {
            $this->addFlash(
                'error',
                'Impossible de modifier les étapes d’un OF déjà commencé.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication_configurer_etapes',
                ['id' => $id]
            );
        }

        if (!$this->isCsrfTokenValid(
            'ajouter_etape_' . $id,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $typeEtapeId = $request->request->get('type_etape_id');

        if (!$typeEtapeId) {
            $this->addFlash(
                'error',
                'Veuillez sélectionner une étape.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication_configurer_etapes',
                ['id' => $id]
            );
        }

        $typeEtape = $this->typeEtapeRepository->find($typeEtapeId);

        if (!$typeEtape instanceof TypeEtape) {
            $this->addFlash(
                'error',
                'Le type d’étape sélectionné est invalide.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication_configurer_etapes',
                ['id' => $id]
            );
        }

        foreach ($ordre->getEtapeFabrications() as $etapeExistante) {
            if (
                $etapeExistante->getTypeEtape()?->getId()
                === $typeEtape->getId()
            ) {
                $this->addFlash(
                    'error',
                    'Cette étape existe déjà dans cet OF.'
                );

                return $this->redirectToRoute(
                    'app_ordre_fabrication_configurer_etapes',
                    ['id' => $id]
                );
            }
        }

        $etape = new EtapeFabrication();

        $etape->setNom($typeEtape->getNom() ?? '');
        $etape->setTypeEtape($typeEtape);
        $etape->setOrdre($this->prochainOrdre($ordre));
        $etape->setStatut('A_FAIRE');
        $etape->setDateDebut(null);
        $etape->setDateFin(null);
        $etape->setOrdreFabrication($ordre);

        $this->entityManager->persist($etape);
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'Étape "' . $typeEtape->getNom() . '" ajoutée à l’OF.'
        );

        return $this->redirectToRoute(
            'app_ordre_fabrication_configurer_etapes',
            ['id' => $id]
        );
    }

    #[Route(
        '/{id<\d+>}/etapes/{etapeId<\d+>}/supprimer',
        name: 'app_ordre_fabrication_etape_supprimer',
        methods: ['POST']
    )]
    public function supprimerEtape(
        Request $request,
        int $id,
        int $etapeId
    ): Response {
        $ordre = $this->ordreFabricationRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(
            OrdreFabricationVoter::EDIT,
            $ordre
        );

        if ($this->ordreEstCommence($ordre)) {
            $this->addFlash(
                'error',
                'Impossible de modifier les étapes d’un OF déjà commencé.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication_configurer_etapes',
                ['id' => $id]
            );
        }

        if (!$this->isCsrfTokenValid(
            'supprimer_etape_' . $etapeId,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $etape = $this->entityManager
            ->getRepository(EtapeFabrication::class)
            ->find($etapeId);

        if (!$etape instanceof EtapeFabrication) {
            throw $this->createNotFoundException();
        }

        if ($etape->getOrdreFabrication() !== $ordre) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($etape);
        $this->reordonnerEtapesApresSuppression(
            $ordre,
            $etape
        );

        $this->entityManager->flush();

        $this->addFlash('success', 'Étape supprimée.');

        return $this->redirectToRoute(
            'app_ordre_fabrication_configurer_etapes',
            ['id' => $id]
        );
    }

    #[Route(
        '/{id<\d+>}/etapes/{etapeId<\d+>}/monter',
        name: 'app_ordre_fabrication_etape_monter',
        methods: ['POST']
    )]
    public function monterEtape(
        Request $request,
        int $id,
        int $etapeId
    ): Response {
        return $this->deplacerEtape(
            $request,
            $id,
            $etapeId,
            -1
        );
    }

    #[Route(
        '/{id<\d+>}/etapes/{etapeId<\d+>}/descendre',
        name: 'app_ordre_fabrication_etape_descendre',
        methods: ['POST']
    )]
    public function descendreEtape(
        Request $request,
        int $id,
        int $etapeId
    ): Response {
        return $this->deplacerEtape(
            $request,
            $id,
            $etapeId,
            1
        );
    }

    private function deplacerEtape(
        Request $request,
        int $id,
        int $etapeId,
        int $direction
    ): Response {
        $ordre = $this->ordreFabricationRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(
            OrdreFabricationVoter::EDIT,
            $ordre
        );

        if ($this->ordreEstCommence($ordre)) {
            $this->addFlash(
                'error',
                'Impossible de modifier l’ordre des étapes d’un OF déjà commencé.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication_configurer_etapes',
                ['id' => $id]
            );
        }

        if (!$this->isCsrfTokenValid(
            'deplacer_etape_' . $etapeId,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $etape = $this->entityManager
            ->getRepository(EtapeFabrication::class)
            ->find($etapeId);

        if (!$etape instanceof EtapeFabrication) {
            throw $this->createNotFoundException();
        }

        if ($etape->getOrdreFabrication() !== $ordre) {
            throw $this->createAccessDeniedException();
        }

        $etapes = $this->getEtapesTriees($ordre);

        $index = array_search($etape, $etapes, true);

        if ($index === false) {
            throw $this->createNotFoundException();
        }

        $nouvelIndex = $index + $direction;

        if (
            $nouvelIndex < 0
            || $nouvelIndex >= count($etapes)
        ) {
            return $this->redirectToRoute(
                'app_ordre_fabrication_configurer_etapes',
                ['id' => $id]
            );
        }

        $autreEtape = $etapes[$nouvelIndex];

        $ordreActuel = $etape->getOrdre();
        $ordreAutre = $autreEtape->getOrdre();

        $etape->setOrdre($ordreAutre ?? 0);
        $autreEtape->setOrdre($ordreActuel ?? 0);

        $this->entityManager->flush();

        return $this->redirectToRoute(
            'app_ordre_fabrication_configurer_etapes',
            ['id' => $id]
        );
    }

    /*
     * ============================================================
     * STATUT D'UNE ETAPE
     * ============================================================
     */

    #[Route(
        '/{id<\d+>}/etape/{etapeId<\d+>}/statut',
        name: 'app_ordre_fabrication_etape_statut',
        methods: ['POST']
    )]
    public function changerStatutEtape(
        Request $request,
        int $id,
        int $etapeId
    ): Response {
        $ordre = $this->ordreFabricationRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(
            OrdreFabricationVoter::EDIT,
            $ordre
        );

        if (!$this->isCsrfTokenValid(
            'modifier_etape',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $etape = $this->entityManager
            ->getRepository(EtapeFabrication::class)
            ->find($etapeId);

        if (!$etape instanceof EtapeFabrication) {
            throw $this->createNotFoundException();
        }

        if ($etape->getOrdreFabrication() !== $ordre) {
            throw $this->createAccessDeniedException();
        }

        $nouveauStatut = $request->request->get('statut');

        if (!in_array(
            $nouveauStatut,
            ['A_FAIRE', 'EN_COURS', 'TERMINEE'],
            true
        )) {
            $this->addFlash(
                'error',
                'Statut d’étape invalide.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication'
            );
        }

        $statutActuel = $etape->getStatut();

        if ($statutActuel === 'TERMINEE') {
            $this->addFlash(
                'error',
                'Une étape terminée ne peut plus être modifiée.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication'
            );
        }

        if (
            $nouveauStatut === 'A_FAIRE'
            && $statutActuel !== 'A_FAIRE'
        ) {
            $this->addFlash(
                'error',
                'Une étape démarrée ne peut pas revenir à "À faire".'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication'
            );
        }

        if (
            in_array(
                $nouveauStatut,
                ['EN_COURS', 'TERMINEE'],
                true
            )
        ) {
            foreach (
                $this->getEtapesTriees($ordre)
                as $autreEtape
            ) {
                if ($autreEtape === $etape) {
                    break;
                }

                if ($autreEtape->getStatut() !== 'TERMINEE') {
                    $this->addFlash(
                        'error',
                        'Vous devez terminer toutes les étapes précédentes avant de continuer.'
                    );

                    return $this->redirectToRoute(
                        'app_ordre_fabrication'
                    );
                }
            }
        }

        if (
            $nouveauStatut === 'TERMINEE'
            && $statutActuel !== 'EN_COURS'
        ) {
            $this->addFlash(
                'error',
                'Une étape doit être "En cours" avant de pouvoir être terminée.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication'
            );
        }

        if (
            $nouveauStatut === 'EN_COURS'
            && $statutActuel === 'A_FAIRE'
        ) {
            $etape->setDateDebut(new \DateTimeImmutable());
        }

        if ($nouveauStatut === 'TERMINEE') {
            $etape->setDateFin(new \DateTimeImmutable());
        }

        $etape->setStatut($nouveauStatut);

        $this->recalculerStatutOrdre($ordre);

        $this->entityManager->flush();

        return $this->redirectToRoute(
            'app_ordre_fabrication'
        );
    }

    /*
     * ============================================================
     * KANBAN
     * ============================================================
     */

    #[Route(
        '/{id<\d+>}/kanban/statut',
        name: 'app_ordre_fabrication_kanban_statut',
        methods: ['POST']
    )]
    public function changerStatutKanban(
        Request $request,
        int $id
    ): JsonResponse {
        $ordre = $this->ordreFabricationRepository->find($id);

        if (!$ordre) {
            return new JsonResponse([
                'success' => false,
                'message' => 'OF introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted(
            OrdreFabricationVoter::EDIT,
            $ordre
        )) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n’avez pas les droits pour modifier cet OF.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$this->isCsrfTokenValid(
            'kanban_statut',
            $request->request->get('_token')
        )) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token CSRF invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $statutDemande = $request->request->get('statut');

        if (!in_array(
            $statutDemande,
            ['EN_ATTENTE', 'EN_COURS', 'TERMINE'],
            true
        )) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Statut Kanban invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $etapes = $this->getEtapesTriees($ordre);

        if ($statutDemande === 'EN_ATTENTE') {
            foreach ($etapes as $etape) {
                if ($etape->getStatut() !== 'A_FAIRE') {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Un OF déjà commencé ne peut pas revenir à "En attente".',
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $ordre->setStatut('EN_ATTENTE');
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'statut' => 'EN_ATTENTE',
            ]);
        }

        if ($statutDemande === 'EN_COURS') {
            if (count($etapes) === 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Cet OF ne possède aucune étape de fabrication.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $etapeACommencer = null;

            foreach ($etapes as $index => $etape) {
                if ($etape->getStatut() === 'A_FAIRE') {
                    $precedentesTerminees = true;

                    for ($i = 0; $i < $index; $i++) {
                        if (
                            $etapes[$i]->getStatut()
                            !== 'TERMINEE'
                        ) {
                            $precedentesTerminees = false;
                            break;
                        }
                    }

                    if ($precedentesTerminees) {
                        $etapeACommencer = $etape;
                    }

                    break;
                }
            }

            if ($etapeACommencer !== null) {
                $etapeACommencer->setStatut('EN_COURS');

                if ($etapeACommencer->getDateDebut() === null) {
                    $etapeACommencer->setDateDebut(
                        new \DateTimeImmutable()
                    );
                }
            }

            $this->recalculerStatutOrdre($ordre);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'statut' => $ordre->getStatut(),
            ]);
        }

        if ($statutDemande === 'TERMINE') {
            foreach ($etapes as $etape) {
                if ($etape->getStatut() !== 'TERMINEE') {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Toutes les étapes doivent être terminées avant de terminer l’OF.',
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            if (count($etapes) === 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Impossible de terminer un OF sans étape.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $ordre->setStatut('TERMINE');
            $ordre->setDateFin(
                $ordre->getDateFin()
                ?? new \DateTimeImmutable()
            );

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'statut' => 'TERMINE',
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Action impossible.',
        ], Response::HTTP_BAD_REQUEST);
    }

    /*
     * ============================================================
     * SUPPRESSION D'UN OF EN ATTENTE
     * ============================================================
     */

    #[Route(
        '/{id<\d+>}/supprimer',
        name: 'app_ordre_fabrication_supprimer',
        methods: ['POST']
    )]
    public function supprimer(
        Request $request,
        int $id
    ): Response {
        $ordre = $this->ordreFabricationRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(
            OrdreFabricationVoter::EDIT,
            $ordre
        );

        if (!$this->isCsrfTokenValid(
            'supprimer_' . $id,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        if ($ordre->getDateArchivage() !== null) {
            $this->addFlash(
                'error',
                'Un OF archivé ne peut pas être supprimé.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication'
            );
        }

        if ($ordre->getStatut() !== 'EN_ATTENTE') {
            $this->addFlash(
                'error',
                'Seul un OF en attente peut être supprimé.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication'
            );
        }

        $numero = $ordre->getNumero();

        $this->entityManager->remove($ordre);
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'L’ordre de fabrication ' . $numero . ' a été supprimé.'
        );

        return $this->redirectToRoute(
            'app_ordre_fabrication'
        );
    }

    /*
     * ============================================================
     * ARCHIVAGE
     * ============================================================
     */

    #[Route(
        '/{id<\d+>}/archiver',
        name: 'app_ordre_fabrication_archiver',
        methods: ['POST']
    )]
    public function archiver(
        Request $request,
        int $id
    ): Response {
        $ordre = $this->ordreFabricationRepository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(
            OrdreFabricationVoter::EDIT,
            $ordre
        );

        if (!$this->isCsrfTokenValid(
            'archiver_' . $id,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        if ($ordre->getDateArchivage() !== null) {
            $this->addFlash(
                'error',
                'Cet OF est déjà archivé.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication_archives'
            );
        }

        if ($ordre->getStatut() !== 'TERMINE') {
            $this->addFlash(
                'error',
                'Seul un OF terminé peut être archivé.'
            );

            return $this->redirectToRoute(
                'app_ordre_fabrication'
            );
        }

        foreach ($this->getEtapesTriees($ordre) as $etape) {
            if ($etape->getStatut() !== 'TERMINEE') {
                $this->addFlash(
                    'error',
                    'Toutes les étapes doivent être terminées avant archivage.'
                );

                return $this->redirectToRoute(
                    'app_ordre_fabrication'
                );
            }
        }

        $ordre->setDateArchivage(
            new \DateTimeImmutable()
        );

        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'L’ordre de fabrication ' . $ordre->getNumero()
            . ' a été archivé.'
        );

        return $this->redirectToRoute(
            'app_ordre_fabrication'
        );
    }

    /*
     * ============================================================
     * OUTILS
     * ============================================================
     */

    private function getEtapesTriees(
        OrdreFabrication $ordre
    ): array {
        $etapes = $ordre->getEtapeFabrications()->toArray();

        usort(
            $etapes,
            fn (
                EtapeFabrication $a,
                EtapeFabrication $b
            ) =>
                ($a->getOrdre() ?? 0)
                <=>
                ($b->getOrdre() ?? 0)
        );

        return $etapes;
    }

    private function ordreEstCommence(
        OrdreFabrication $ordre
    ): bool {
        foreach ($this->getEtapesTriees($ordre) as $etape) {
            if ($etape->getStatut() !== 'A_FAIRE') {
                return true;
            }
        }

        return false;
    }

    private function prochainOrdre(
        OrdreFabrication $ordre
    ): int {
        $etapes = $this->getEtapesTriees($ordre);

        if (count($etapes) === 0) {
            return 1;
        }

        $dernier = $etapes[array_key_last($etapes)];

        return ($dernier->getOrdre() ?? 0) + 1;
    }

    private function reordonnerEtapesApresSuppression(
        OrdreFabrication $ordre,
        EtapeFabrication $etapeSupprimee
    ): void {
        $etapes = array_filter(
            $this->getEtapesTriees($ordre),
            fn (
                EtapeFabrication $etape
            ) => $etape !== $etapeSupprimee
        );

        $position = 1;

        foreach ($etapes as $etape) {
            $etape->setOrdre($position);
            $position++;
        }
    }

    private function recalculerStatutOrdre(
        OrdreFabrication $ordre
    ): void {
        $etapes = $this->getEtapesTriees($ordre);

        if (count($etapes) === 0) {
            $ordre->setStatut('EN_ATTENTE');
            return;
        }

        $toutesTerminees = true;
        $auMoinsUneEnCours = false;
        $auMoinsUneTerminee = false;

        foreach ($etapes as $etape) {
            if ($etape->getStatut() !== 'TERMINEE') {
                $toutesTerminees = false;
            }

            if ($etape->getStatut() === 'EN_COURS') {
                $auMoinsUneEnCours = true;
            }

            if ($etape->getStatut() === 'TERMINEE') {
                $auMoinsUneTerminee = true;
            }
        }

        if ($toutesTerminees) {
            $ordre->setStatut('TERMINE');
            $ordre->setDateFin(
                $ordre->getDateFin()
                ?? new \DateTimeImmutable()
            );

            return;
        }

        if (
            $auMoinsUneEnCours
            || $auMoinsUneTerminee
        ) {
            $ordre->setStatut('EN_COURS');
            return;
        }

        $ordre->setStatut('EN_ATTENTE');
    }
}

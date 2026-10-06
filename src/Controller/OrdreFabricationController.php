<?php

namespace App\Controller;

use App\Entity\EtapeFabrication;
use App\Entity\OrdreFabrication;
use App\Form\OrdreFabricationType;
use App\Repository\OrdreFabricationRepository;
use App\Service\OrdreFabricationNumeroGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ordre/fabrication')]
class OrdreFabricationController extends AbstractController
{
    #[Route('', name: 'app_ordre_fabrication', methods: ['GET'])]
    public function index(
        OrdreFabricationRepository $repository
    ): Response {
        $ordres = $repository->findAll();

        return $this->render('ordre_fabrication/index.html.twig', [
            'ordres' => $ordres,
        ]);
    }

    #[Route('/nouveau', name: 'app_ordre_fabrication_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        OrdreFabricationNumeroGenerator $numeroGenerator
    ): Response {
        $ordre = new OrdreFabrication();

        $ordre->setNumero($numeroGenerator->generate());
        $ordre->setDateCreation(new \DateTimeImmutable());
        $ordre->setStatut('EN_ATTENTE');
        $ordre->setUser($this->getUser());

        $form = $this->createForm(OrdreFabricationType::class, $ordre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $etape1 = new EtapeFabrication();
            $etape1->setNom('Découpe');
            $etape1->setOrdre(1);
            $etape1->setStatut('A_FAIRE');
            $etape1->setOrdreFabrication($ordre);

            $etape2 = new EtapeFabrication();
            $etape2->setNom('Assemblage');
            $etape2->setOrdre(2);
            $etape2->setStatut('A_FAIRE');
            $etape2->setOrdreFabrication($ordre);

            $etape3 = new EtapeFabrication();
            $etape3->setNom('Contrôle');
            $etape3->setOrdre(3);
            $etape3->setStatut('A_FAIRE');
            $etape3->setOrdreFabrication($ordre);

            $ordre->addEtapeFabrication($etape1);
            $ordre->addEtapeFabrication($etape2);
            $ordre->addEtapeFabrication($etape3);

            $entityManager->persist($ordre);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'L’ordre de fabrication a été créé avec succès.'
            );

            return $this->redirectToRoute('app_ordre_fabrication');
        }

        return $this->render('ordre_fabrication/new.html.twig', [
            'ordre' => $ordre,
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/modifier',
        name: 'app_ordre_fabrication_edit',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request,
        OrdreFabrication $ordre,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('EDIT', $ordre);

        $form = $this->createForm(OrdreFabricationType::class, $ordre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /*
             * Le statut de l'OF est toujours recalculé
             * depuis les étapes.
             */
            $this->recalculerStatutOrdre($ordre);

            $entityManager->flush();

            $this->addFlash(
                'success',
                'L’ordre de fabrication a été modifié avec succès.'
            );

            return $this->redirectToRoute('app_ordre_fabrication');
        }

        return $this->render('ordre_fabrication/edit.html.twig', [
            'ordre' => $ordre,
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/etape/{etapeId}/statut',
        name: 'app_ordre_fabrication_etape_statut',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function changerStatutEtape(
        int $id,
        int $etapeId,
        Request $request,
        OrdreFabricationRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $ordre = $repository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException(
                'Ordre de fabrication introuvable.'
            );
        }

        $this->denyAccessUnlessGranted('EDIT', $ordre);

        /*
         * Recherche de l'étape et vérification
         * qu'elle appartient bien à cet OF.
         */
        $etape = null;

        foreach ($ordre->getEtapeFabrications() as $etapeFabrication) {
            if ($etapeFabrication->getId() === $etapeId) {
                $etape = $etapeFabrication;
                break;
            }
        }

        if (!$etape) {
            throw $this->createNotFoundException(
                'Étape de fabrication introuvable.'
            );
        }

        /*
         * Vérification CSRF.
         */
        if (!$this->isCsrfTokenValid(
            'modifier_etape',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $nouveauStatut = $request->request->get('statut');

        $statutsAutorises = [
            'A_FAIRE',
            'EN_COURS',
            'TERMINEE',
        ];

        if (!in_array($nouveauStatut, $statutsAutorises, true)) {
            throw $this->createAccessDeniedException(
                'Statut d’étape invalide.'
            );
        }

        $ancienStatut = $etape->getStatut();

        /*
         * Aucun changement.
         */
        if ($ancienStatut === $nouveauStatut) {
            return $this->redirectToRoute(
                'app_ordre_fabrication'
            );
        }

        /*
         * On récupère toutes les étapes dans leur ordre.
         */
        $etapes = $ordre->getEtapeFabrications()->toArray();

        usort(
            $etapes,
            static fn (
                EtapeFabrication $a,
                EtapeFabrication $b
            ) => $a->getOrdre() <=> $b->getOrdre()
        );

        /*
         * =========================================================
         * PASSAGE VERS EN_COURS
         * =========================================================
         *
         * Une étape ne peut démarrer que si toutes les étapes
         * précédentes sont TERMINEE.
         */
        if ($nouveauStatut === 'EN_COURS') {

            /*
             * Une étape terminée ne peut pas revenir en arrière.
             */
            if ($ancienStatut === 'TERMINEE') {

                $this->addFlash(
                    'error',
                    sprintf(
                        'L’étape "%s" est déjà terminée et ne peut pas revenir en cours.',
                        $etape->getNom()
                    )
                );

                return $this->redirectToRoute(
                    'app_ordre_fabrication'
                );
            }

            /*
             * Vérification de TOUTES les étapes précédentes.
             */
            foreach ($etapes as $etapePrecedente) {

                if (
                    $etapePrecedente->getOrdre() < $etape->getOrdre()
                    && $etapePrecedente->getStatut() !== 'TERMINEE'
                ) {

                    $this->addFlash(
                        'error',
                        sprintf(
                            'Impossible de démarrer "%s" : l’étape "%s" doit être terminée avant.',
                            $etape->getNom(),
                            $etapePrecedente->getNom()
                        )
                    );

                    return $this->redirectToRoute(
                        'app_ordre_fabrication'
                    );
                }
            }

            /*
             * On démarre l'étape.
             */
            $etape->setStatut('EN_COURS');

            if ($etape->getDateDebut() === null) {
                $etape->setDateDebut(
                    new \DateTimeImmutable()
                );
            }

            $etape->setDateFin(null);
        }

        /*
         * =========================================================
         * PASSAGE VERS TERMINEE
         * =========================================================
         *
         * C'est ici que nous corrigeons le problème découvert :
         *
         * Une étape 2 ne peut PAS être terminée si l'étape 1
         * est encore EN_COURS.
         */
        if ($nouveauStatut === 'TERMINEE') {

            /*
             * Il faut obligatoirement être passé par EN_COURS.
             */
            if ($ancienStatut !== 'EN_COURS') {

                $this->addFlash(
                    'error',
                    sprintf(
                        'Impossible de terminer "%s" : l’étape doit d’abord être en cours.',
                        $etape->getNom()
                    )
                );

                return $this->redirectToRoute(
                    'app_ordre_fabrication'
                );
            }

            /*
             * NOUVELLE RÈGLE :
             *
             * Toutes les étapes précédentes doivent être
             * TERMINEE avant de pouvoir terminer celle-ci.
             */
            foreach ($etapes as $etapePrecedente) {

                if (
                    $etapePrecedente->getOrdre() < $etape->getOrdre()
                    && $etapePrecedente->getStatut() !== 'TERMINEE'
                ) {

                    $this->addFlash(
                        'error',
                        sprintf(
                            'Impossible de terminer "%s" : l’étape "%s" doit être terminée avant.',
                            $etape->getNom(),
                            $etapePrecedente->getNom()
                        )
                    );

                    return $this->redirectToRoute(
                        'app_ordre_fabrication'
                    );
                }
            }

            /*
             * Toutes les conditions sont respectées.
             * On peut terminer l'étape.
             */
            if ($etape->getDateDebut() === null) {
                $etape->setDateDebut(
                    new \DateTimeImmutable()
                );
            }

            $etape->setStatut('TERMINEE');
            $etape->setDateFin(
                new \DateTimeImmutable()
            );
        }

        /*
         * =========================================================
         * RETOUR VERS A_FAIRE
         * =========================================================
         *
         * On interdit le retour d'une étape terminée.
         */
        if ($nouveauStatut === 'A_FAIRE') {

            if ($ancienStatut === 'TERMINEE') {

                $this->addFlash(
                    'error',
                    sprintf(
                        'L’étape "%s" est terminée et ne peut pas revenir à A_FAIRE.',
                        $etape->getNom()
                    )
                );

                return $this->redirectToRoute(
                    'app_ordre_fabrication'
                );
            }

            $etape->setStatut('A_FAIRE');
            $etape->setDateDebut(null);
            $etape->setDateFin(null);
        }

        /*
         * Recalcul du statut global de l'OF.
         */
        $this->recalculerStatutOrdre($ordre);

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Le statut de l’étape a été mis à jour.'
        );

        return $this->redirectToRoute(
            'app_ordre_fabrication'
        );
    }

    #[Route(
        '/{id}/kanban/statut',
        name: 'app_ordre_fabrication_kanban_statut',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function changerStatutKanban(
        int $id,
        Request $request,
        OrdreFabricationRepository $repository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $ordre = $repository->find($id);

        if (!$ordre) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Ordre de fabrication introuvable.',
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $this->denyAccessUnlessGranted('EDIT', $ordre);

        /*
         * Vérification CSRF.
         */
        if (!$this->isCsrfTokenValid(
            'kanban_statut',
            $request->request->get('_token')
        )) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Token CSRF invalide.',
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $statutDemande = $request->request->get('statut');

        $statutsAutorises = [
            'EN_ATTENTE',
            'EN_COURS',
            'TERMINE',
        ];

        if (!in_array($statutDemande, $statutsAutorises, true)) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Statut invalide.',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        /*
         * Récupération des étapes dans leur ordre.
         */
        $etapes = $ordre->getEtapeFabrications()->toArray();

        usort(
            $etapes,
            static fn (
                EtapeFabrication $a,
                EtapeFabrication $b
            ) => $a->getOrdre() <=> $b->getOrdre()
        );

        if (count($etapes) === 0) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' =>
                        'Cet ordre de fabrication ne possède aucune étape.',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        /*
         * =========================================================
         * KANBAN -> EN_COURS
         * =========================================================
         *
         * On démarre uniquement la prochaine étape autorisée.
         */
        if ($statutDemande === 'EN_COURS') {

            /*
             * Une étape est-elle déjà en cours ?
             */
            foreach ($etapes as $etape) {

                if ($etape->getStatut() === 'EN_COURS') {

                    $this->recalculerStatutOrdre($ordre);
                    $entityManager->flush();

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Cet OF est déjà en cours.',
                        'numero' => $ordre->getNumero(),
                        'statut' => $ordre->getStatut(),
                    ]);
                }
            }

            /*
             * Recherche de la première étape A_FAIRE.
             */
            foreach ($etapes as $index => $etape) {

                if ($etape->getStatut() !== 'A_FAIRE') {
                    continue;
                }

                /*
                 * Toutes les étapes précédentes doivent être terminées.
                 */
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

                if (!$precedentesTerminees) {
                    return new JsonResponse(
                        [
                            'success' => false,
                            'message' =>
                                'Impossible de démarrer la prochaine étape : les étapes précédentes ne sont pas terminées.',
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }

                /*
                 * On démarre la prochaine étape.
                 */
                $etape->setStatut('EN_COURS');

                if ($etape->getDateDebut() === null) {
                    $etape->setDateDebut(
                        new \DateTimeImmutable()
                    );
                }

                $etape->setDateFin(null);

                break;
            }
        }

        /*
         * =========================================================
         * KANBAN -> TERMINE
         * =========================================================
         *
         * Impossible de terminer l'OF si une seule étape
         * n'est pas terminée.
         */
        if ($statutDemande === 'TERMINE') {

            foreach ($etapes as $etape) {

                if ($etape->getStatut() !== 'TERMINEE') {

                    return new JsonResponse(
                        [
                            'success' => false,
                            'message' =>
                                sprintf(
                                    'Impossible de terminer l’OF : l’étape "%s" n’est pas terminée.',
                                    $etape->getNom()
                                ),
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
        }

        /*
         * =========================================================
         * KANBAN -> EN_ATTENTE
         * =========================================================
         *
         * Un OF commencé ne peut pas revenir artificiellement
         * en attente.
         */
        if ($statutDemande === 'EN_ATTENTE') {

            foreach ($etapes as $etape) {

                if ($etape->getStatut() !== 'A_FAIRE') {

                    return new JsonResponse(
                        [
                            'success' => false,
                            'message' =>
                                'Impossible de remettre cet OF en attente : une étape a déjà commencé.',
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
        }

        /*
         * Le statut global est toujours déterminé par
         * les étapes.
         */
        $this->recalculerStatutOrdre($ordre);

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Ordre de fabrication mis à jour.',
            'numero' => $ordre->getNumero(),
            'statut' => $ordre->getStatut(),
        ]);
    }

    #[Route(
        '/{id}/supprimer',
        name: 'app_ordre_fabrication_delete',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function delete(
        int $id,
        Request $request,
        OrdreFabricationRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $ordre = $repository->find($id);

        if (!$ordre) {
            throw $this->createNotFoundException(
                'Ordre de fabrication introuvable.'
            );
        }

        $this->denyAccessUnlessGranted('DELETE', $ordre);

        if (!$this->isCsrfTokenValid(
            'supprimer_ordre',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $entityManager->remove($ordre);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'L’ordre de fabrication a été supprimé.'
        );

        return $this->redirectToRoute(
            'app_ordre_fabrication'
        );
    }

    /**
     * Recalcule le statut global de l'OF
     * à partir des statuts de ses étapes.
     */
    private function recalculerStatutOrdre(
        OrdreFabrication $ordre
    ): void {
        $etapes = $ordre->getEtapeFabrications()->toArray();

        if (count($etapes) === 0) {
            $ordre->setStatut('EN_ATTENTE');
            $ordre->setDateFin(null);

            return;
        }

        $toutesTerminees = true;
        $uneEtapeEnCours = false;
        $uneEtapeTerminee = false;

        foreach ($etapes as $etape) {

            if ($etape->getStatut() !== 'TERMINEE') {
                $toutesTerminees = false;
            }

            if ($etape->getStatut() === 'EN_COURS') {
                $uneEtapeEnCours = true;
            }

            if ($etape->getStatut() === 'TERMINEE') {
                $uneEtapeTerminee = true;
            }
        }

        /*
         * Toutes les étapes sont terminées.
         */
        if ($toutesTerminees) {

            $ordre->setStatut('TERMINE');

            if ($ordre->getDateDebut() === null) {
                $ordre->setDateDebut(
                    new \DateTimeImmutable()
                );
            }

            if ($ordre->getDateFin() === null) {
                $ordre->setDateFin(
                    new \DateTimeImmutable()
                );
            }

            return;
        }

        /*
         * Une étape est en cours ou au moins une étape
         * est déjà terminée.
         */
        if ($uneEtapeEnCours || $uneEtapeTerminee) {

            $ordre->setStatut('EN_COURS');

            if ($ordre->getDateDebut() === null) {
                $ordre->setDateDebut(
                    new \DateTimeImmutable()
                );
            }

            $ordre->setDateFin(null);

            return;
        }

        /*
         * Toutes les étapes sont A_FAIRE.
         */
        $ordre->setStatut('EN_ATTENTE');
        $ordre->setDateFin(null);
    }
}


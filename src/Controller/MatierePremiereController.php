<?php

namespace App\Controller;

use App\Entity\MatierePremiere;
use App\Form\MatierePremiereType;
use App\Repository\MatierePremiereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/matiere-premiere')]
class MatierePremiereController extends AbstractController
{
    #[Route('', name: 'app_matiere_premiere', methods: ['GET'])]
    public function index(
        MatierePremiereRepository $repository
    ): Response {
        $matieres = $repository->findAll();

        return $this->render('matiere_premiere/index.html.twig', [
            'matieres' => $matieres,
        ]);
    }

    #[Route(
        '/nouveau',
        name: 'app_matiere_premiere_new',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $matiere = new MatierePremiere();

        $form = $this->createForm(
            MatierePremiereType::class,
            $matiere
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($matiere);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Matière première créée avec succès.'
            );

            return $this->redirectToRoute(
                'app_matiere_premiere'
            );
        }

        return $this->render(
            'matiere_premiere/new.html.twig',
            [
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}/modifier',
        name: 'app_matiere_premiere_edit',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function edit(
        MatierePremiere $matiere,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(
            MatierePremiereType::class,
            $matiere
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Matière première modifiée avec succès.'
            );

            return $this->redirectToRoute(
                'app_matiere_premiere'
            );
        }

        return $this->render(
            'matiere_premiere/edit.html.twig',
            [
                'matiere' => $matiere,
                'form' => $form,
            ]
        );
    }

    #[Route(
        '/{id}/supprimer',
        name: 'app_matiere_premiere_delete',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        MatierePremiere $matiere,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'supprimer_matiere_premiere',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $entityManager->remove($matiere);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Matière première supprimée avec succès.'
        );

        return $this->redirectToRoute(
            'app_matiere_premiere'
        );
    }
}

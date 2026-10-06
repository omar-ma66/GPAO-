<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/produit')]
class ProduitController extends AbstractController
{
    #[Route('', name: 'app_produit', methods: ['GET'])]
    public function index(
        ProduitRepository $repository
    ): Response {
        $produits = $repository->findAll();

        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/nouveau', name: 'app_produit_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $produit = new Produit();

        $form = $this->createForm(
            ProduitType::class,
            $produit
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Produit créé avec succès.'
            );

            return $this->redirectToRoute(
                'app_produit'
            );
        }

        return $this->render('produit/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/modifier',
        name: 'app_produit_edit',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Produit $produit,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(
            ProduitType::class,
            $produit
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Produit modifié avec succès.'
            );

            return $this->redirectToRoute(
                'app_produit'
            );
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/supprimer',
        name: 'app_produit_delete',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Produit $produit,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'supprimer_produit',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        $entityManager->remove($produit);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Produit supprimé avec succès.'
        );

        return $this->redirectToRoute(
            'app_produit'
        );
    }
}

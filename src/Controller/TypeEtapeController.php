<?php

namespace App\Controller;

use App\Entity\TypeEtape;
use App\Form\TypeEtapeType;
use App\Repository\TypeEtapeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/type-etape')]
class TypeEtapeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TypeEtapeRepository $typeEtapeRepository,
    ) {
    }

    #[Route('', name: 'app_type_etape', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('type_etape/index.html.twig', [
            'typesEtapes' => $this->typeEtapeRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_type_etape_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $typeEtape = new TypeEtape();

        $form = $this->createForm(TypeEtapeType::class, $typeEtape);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($typeEtape);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Type d’étape créé avec succès.'
            );

            return $this->redirectToRoute('app_type_etape');
        }

        return $this->render('type_etape/new.html.twig', [
            'typeEtape' => $typeEtape,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_type_etape_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $typeEtape = $this->typeEtapeRepository->find($id);

        if (!$typeEtape) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(TypeEtapeType::class, $typeEtape);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Type d’étape modifié avec succès.'
            );

            return $this->redirectToRoute('app_type_etape');
        }

        return $this->render('type_etape/edit.html.twig', [
            'typeEtape' => $typeEtape,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}/supprimer', name: 'app_type_etape_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $typeEtape = $this->typeEtapeRepository->find($id);

        if (!$typeEtape) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid(
            'supprimer_type_etape_' . $id,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if (!$typeEtape->getEtapeFabrications()->isEmpty()) {
            $this->addFlash(
                'error',
                'Impossible de supprimer ce type d’étape car il est utilisé par un ou plusieurs ordres de fabrication.'
            );

            return $this->redirectToRoute('app_type_etape');
        }

        $this->entityManager->remove($typeEtape);
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'Type d’étape supprimé avec succès.'
        );

        return $this->redirectToRoute('app_type_etape');
    }
}

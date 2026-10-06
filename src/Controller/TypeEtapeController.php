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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/type-etape')]
class TypeEtapeController extends AbstractController
{
    #[Route('', name: 'app_type_etape', methods: ['GET'])]
    public function index(
        TypeEtapeRepository $repository
    ): Response {
        return $this->render('type_etape/index.html.twig', [
            'type_etapes' => $repository->findBy(
                [],
                ['nom' => 'ASC']
            ),
        ]);
    }

    #[Route(
        '/nouveau',
        name: 'app_type_etape_new',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $typeEtape = new TypeEtape();

        $form = $this->createForm(
            TypeEtapeType::class,
            $typeEtape
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($typeEtape);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'L’étape de fabrication a été créée.'
            );

            return $this->redirectToRoute(
                'app_type_etape'
            );
        }

        return $this->render('type_etape/new.html.twig', [
            'type_etape' => $typeEtape,
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/modifier',
        name: 'app_type_etape_edit',
        methods: ['GET', 'POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request,
        TypeEtape $typeEtape,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(
            TypeEtapeType::class,
            $typeEtape
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $this->addFlash(
                'success',
                'L’étape de fabrication a été modifiée.'
            );

            return $this->redirectToRoute(
                'app_type_etape'
            );
        }

        return $this->render('type_etape/edit.html.twig', [
            'type_etape' => $typeEtape,
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/supprimer',
        name: 'app_type_etape_delete',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        TypeEtape $typeEtape,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'supprimer_type_etape_' . $typeEtape->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        /*
         * On empêche la suppression d'une étape du catalogue
         * déjà utilisée par un OF.
         */
        if (!$typeEtape->getEtapeFabrications()->isEmpty()) {
            $this->addFlash(
                'error',
                'Cette étape ne peut pas être supprimée car elle est utilisée par un ou plusieurs ordres de fabrication.'
            );

            return $this->redirectToRoute(
                'app_type_etape'
            );
        }

        $entityManager->remove($typeEtape);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'L’étape de fabrication a été supprimée.'
        );

        return $this->redirectToRoute(
            'app_type_etape'
        );
    }
}


<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrdreFabricationControllerTest extends WebTestCase
{
    public function testKanbanEstAccessible(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/ordre/fabrication'
        );

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains(
            'h1',
            'Ordres de fabrication'
        );
    }

   public function testCreationOrdreFabricationGenereNumero(): void
{
    $client = static::createClient();

    $container = static::getContainer();

    $userRepository = $container->get(
        \App\Repository\UserRepository::class
    );

    $user = $userRepository->findOneBy([
        'email' => 'production@gpao.fr',
    ]);

    self::assertNotNull($user);

    $client->loginUser($user);

    $crawler = $client->request(
        'GET',
        '/ordre/fabrication/nouveau'
    );

    self::assertResponseIsSuccessful();

    $form = $crawler
        ->selectButton("Créer l'ordre")
        ->form();

    $form['ordre_fabrication[quantite]'] = 10;
    $form['ordre_fabrication[statut]'] = 'EN_ATTENTE';

    $produitRepository = $container->get(
        \App\Repository\ProduitRepository::class
    );

    $produit = $produitRepository->findOneBy([
        'reference' => 'CHA-001',
    ]);

    self::assertNotNull($produit);

    $form['ordre_fabrication[produit]'] = $produit->getId();

    $entityManager = $container->get(
        \Doctrine\ORM\EntityManagerInterface::class
    );

    $repository = $entityManager->getRepository(
        \App\Entity\OrdreFabrication::class
    );

    $nombreAvant = $repository->count([]);

    $client->submit($form);

    self::assertResponseRedirects(
        '/ordre/fabrication'
    );

    $entityManager->clear();

    $nombreApres = $repository->count([]);

    self::assertSame(
        $nombreAvant + 1,
        $nombreApres
    );

    $dernierOrdre = $repository->findOneBy(
    [],
    ['id' => 'DESC']
);

self::assertNotNull($dernierOrdre);


self::assertMatchesRegularExpression(
    '/^OF-\d{4}-\d{3}$/',
    $dernierOrdre->getNumero()
);
}
}

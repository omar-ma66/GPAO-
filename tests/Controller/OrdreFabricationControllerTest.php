<?php

namespace App\Tests\Controller;

use App\Entity\OrdreFabrication;
use App\Repository\OrdreFabricationRepository;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrdreFabricationControllerTest extends WebTestCase
{
    public function testKanbanEstAccessible(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()
            ->get(UserRepository::class);

        $user = $userRepository->findOneBy([
            'email' => 'production@gpao.local',
        ]);

        self::assertNotNull($user);

        $client->loginUser($user);

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
            UserRepository::class
        );

        $user = $userRepository->findOneBy([
            'email' => 'production@gpao.local',
        ]);

        self::assertNotNull($user);

        $client->loginUser($user);

        $crawler = $client->request(
            'GET',
            '/ordre/fabrication/new'
        );

        self::assertResponseIsSuccessful();

        /*
         * On récupère directement le premier formulaire
         * présent sur la page.
         */
        self::assertGreaterThan(
            0,
            $crawler->filter('form')->count()
        );

        $form = $crawler->filter('form')->form();

        $form['ordre_fabrication[quantite]'] = 10;

        /** @var ProduitRepository $produitRepository */
        $produitRepository = $container->get(
            ProduitRepository::class
        );

        $produit = $produitRepository->findOneBy([
            'reference' => 'CHA-001',
        ]);

        self::assertNotNull($produit);

        $form['ordre_fabrication[produit]'] = $produit->getId();

        /** @var OrdreFabricationRepository $ordreRepository */
        $ordreRepository = $container->get(
            OrdreFabricationRepository::class
        );

        $nombreAvant = $ordreRepository->count([]);

        $client->submit($form);

        self::assertResponseRedirects();

        $nombreApres = $ordreRepository->count([]);

        self::assertSame(
            $nombreAvant + 1,
            $nombreApres
        );

        /** @var OrdreFabrication|null $ordre */
        $ordre = $ordreRepository->findOneBy(
            [],
            ['id' => 'DESC']
        );

        self::assertNotNull($ordre);

        self::assertMatchesRegularExpression(
            '/^OF-\d{4}-\d{3}$/',
            $ordre->getNumero()
        );

        self::assertSame(
            'EN_ATTENTE',
            $ordre->getStatut()
        );

        self::assertSame(
            10,
            $ordre->getQuantite()
        );

        self::assertSame(
            $produit->getId(),
            $ordre->getProduit()?->getId()
        );
    }
}


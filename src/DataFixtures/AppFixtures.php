<?php

namespace App\DataFixtures;

use App\Entity\EtapeFabrication;
use App\Entity\MatierePremiere;
use App\Entity\OrdreFabrication;
use App\Entity\Produit;
use App\Entity\TypeEtape;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function getDependencies(): array
    {
        return [
            TypeEtapeFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        /*
         * ============================================================
         * UTILISATEUR ADMIN
         * ============================================================
         */
        $admin = new User();
        $admin->setEmail('admin@gpao.local');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'password')
        );

        $manager->persist($admin);
        $this->addReference('user_admin', $admin);

        /*
         * ============================================================
         * UTILISATEUR PRODUCTION
         * ============================================================
         */
        $production = new User();
        $production->setEmail('production@gpao.local');
        $production->setRoles(['ROLE_USER']);
        $production->setPassword(
            $this->passwordHasher->hashPassword($production, 'password')
        );

        $manager->persist($production);
        $this->addReference('user_production', $production);

        /*
         * ============================================================
         * PRODUITS
         * ============================================================
         */

        $produit1 = new Produit();
        $produit1->setNom('Chaise industrielle');
        $produit1->setReference('CHA-001');
        $produit1->setDescription(
            'Chaise industrielle métallique destinée aux ateliers.'
        );

        $manager->persist($produit1);
        $this->addReference('produit_1', $produit1);

        $produit2 = new Produit();
        $produit2->setNom('Table industrielle');
        $produit2->setReference('TAB-001');
        $produit2->setDescription(
            'Table industrielle robuste pour environnement de production.'
        );

        $manager->persist($produit2);
        $this->addReference('produit_2', $produit2);

        $produit3 = new Produit();
        $produit3->setNom('Établi industriel');
        $produit3->setReference('ETA-001');
        $produit3->setDescription(
            'Établi industriel destiné aux travaux de fabrication.'
        );

        $manager->persist($produit3);
        $this->addReference('produit_3', $produit3);

        /*
         * ============================================================
         * MATIÈRES PREMIÈRES
         * ============================================================
         */

        $acier = new MatierePremiere();
        $acier->setNom('Acier');
        $acier->setReference('MAT-001');
        $acier->setDescription(
            'Acier utilisé pour les structures métalliques.'
        );

        $manager->persist($acier);
        $this->addReference('matiere_acier', $acier);

        $aluminium = new MatierePremiere();
        $aluminium->setNom('Aluminium');
        $aluminium->setReference('MAT-002');
        $aluminium->setDescription(
            'Aluminium utilisé pour les pièces légères.'
        );

        $manager->persist($aluminium);
        $this->addReference('matiere_aluminium', $aluminium);

        $plastique = new MatierePremiere();
        $plastique->setNom('Plastique');
        $plastique->setReference('MAT-003');
        $plastique->setDescription(
            'Plastique utilisé pour certains composants.'
        );

        $manager->persist($plastique);
        $this->addReference('matiere_plastique', $plastique);

        /*
         * ============================================================
         * ASSOCIATION MATIÈRES PREMIÈRES <-> PRODUITS
         * ============================================================
         */

        // Chaise industrielle
        $produit1->addMatierePremiere($acier);
        $produit1->addMatierePremiere($plastique);

        // Table industrielle
        $produit2->addMatierePremiere($acier);
        $produit2->addMatierePremiere($aluminium);

        // Établi industriel
        $produit3->addMatierePremiere($acier);
        $produit3->addMatierePremiere($aluminium);

        /*
         * ============================================================
         * RÉCUPÉRATION DES TYPES D'ÉTAPES
         * ============================================================
         */

        /** @var TypeEtape $typeDecoupe */
        $typeDecoupe = $this->getReference(
            TypeEtapeFixtures::DECOUPE,
            TypeEtape::class
        );

        /** @var TypeEtape $typeAssemblage */
        $typeAssemblage = $this->getReference(
            TypeEtapeFixtures::ASSEMBLAGE,
            TypeEtape::class
        );

        /** @var TypeEtape $typeControle */
        $typeControle = $this->getReference(
            TypeEtapeFixtures::CONTROLE,
            TypeEtape::class
        );

        /*
         * ============================================================
         * ORDRE DE FABRICATION N°1
         * ============================================================
         */

        $of1 = new OrdreFabrication();
        $of1->setNumero('OF-2026-001');
        $of1->setQuantite(100);
        $of1->setStatut('EN_ATTENTE');
        $of1->setDateCreation(
            new \DateTimeImmutable('2026-10-01 08:00:00')
        );
        $of1->setProduit($produit1);
        $of1->setUser($production);

        // Matières utilisées par cet OF
        $of1->addMatierePremiere($acier);
        $of1->addMatierePremiere($plastique);

        $manager->persist($of1);

        /*
         * Étape 1 : Découpe
         */
        $etape1Of1 = new EtapeFabrication();
        $etape1Of1->setNom($typeDecoupe->getNom());
        $etape1Of1->setOrdre(1);
        $etape1Of1->setStatut('A_FAIRE');
        $etape1Of1->setOrdreFabrication($of1);
        $etape1Of1->setTypeEtape($typeDecoupe);

        $manager->persist($etape1Of1);

        /*
         * Étape 2 : Assemblage
         */
        $etape2Of1 = new EtapeFabrication();
        $etape2Of1->setNom($typeAssemblage->getNom());
        $etape2Of1->setOrdre(2);
        $etape2Of1->setStatut('A_FAIRE');
        $etape2Of1->setOrdreFabrication($of1);
        $etape2Of1->setTypeEtape($typeAssemblage);

        $manager->persist($etape2Of1);

        /*
         * Étape 3 : Contrôle
         */
        $etape3Of1 = new EtapeFabrication();
        $etape3Of1->setNom($typeControle->getNom());
        $etape3Of1->setOrdre(3);
        $etape3Of1->setStatut('A_FAIRE');
        $etape3Of1->setOrdreFabrication($of1);
        $etape3Of1->setTypeEtape($typeControle);

        $manager->persist($etape3Of1);

        /*
         * ============================================================
         * ORDRE DE FABRICATION N°2
         * ============================================================
         */

        $of2 = new OrdreFabrication();
        $of2->setNumero('OF-2026-002');
        $of2->setQuantite(50);
        $of2->setStatut('EN_COURS');
        $of2->setDateCreation(
            new \DateTimeImmutable('2026-10-02 08:00:00')
        );
        $of2->setDateDebut(
            new \DateTimeImmutable('2026-10-02 09:00:00')
        );
        $of2->setProduit($produit2);
        $of2->setUser($production);

        // Matières utilisées par cet OF
        $of2->addMatierePremiere($acier);
        $of2->addMatierePremiere($aluminium);

        $manager->persist($of2);

        /*
         * Étape 1 : Découpe terminée
         */
        $etape1Of2 = new EtapeFabrication();
        $etape1Of2->setNom($typeDecoupe->getNom());
        $etape1Of2->setOrdre(1);
        $etape1Of2->setStatut('TERMINEE');
        $etape1Of2->setDateDebut(
            new \DateTimeImmutable('2026-10-02 09:00:00')
        );
        $etape1Of2->setDateFin(
            new \DateTimeImmutable('2026-10-02 10:30:00')
        );
        $etape1Of2->setOrdreFabrication($of2);
        $etape1Of2->setTypeEtape($typeDecoupe);

        $manager->persist($etape1Of2);

        /*
         * Étape 2 : Assemblage en cours
         */
        $etape2Of2 = new EtapeFabrication();
        $etape2Of2->setNom($typeAssemblage->getNom());
        $etape2Of2->setOrdre(2);
        $etape2Of2->setStatut('EN_COURS');
        $etape2Of2->setDateDebut(
            new \DateTimeImmutable('2026-10-02 11:00:00')
        );
        $etape2Of2->setOrdreFabrication($of2);
        $etape2Of2->setTypeEtape($typeAssemblage);

        $manager->persist($etape2Of2);

        /*
         * Étape 3 : Contrôle à faire
         */
        $etape3Of2 = new EtapeFabrication();
        $etape3Of2->setNom($typeControle->getNom());
        $etape3Of2->setOrdre(3);
        $etape3Of2->setStatut('A_FAIRE');
        $etape3Of2->setOrdreFabrication($of2);
        $etape3Of2->setTypeEtape($typeControle);

        $manager->persist($etape3Of2);

        /*
         * ============================================================
         * ORDRE DE FABRICATION N°3
         * ============================================================
         */

        $of3 = new OrdreFabrication();
        $of3->setNumero('OF-2026-003');
        $of3->setQuantite(25);
        $of3->setStatut('TERMINE');
        $of3->setDateCreation(
            new \DateTimeImmutable('2026-10-03 08:00:00')
        );
        $of3->setDateDebut(
            new \DateTimeImmutable('2026-10-03 09:00:00')
        );
        $of3->setDateFin(
            new \DateTimeImmutable('2026-10-03 16:00:00')
        );
        $of3->setProduit($produit3);
        $of3->setUser($production);

        // Matières utilisées par cet OF
        $of3->addMatierePremiere($acier);
        $of3->addMatierePremiere($aluminium);

        $manager->persist($of3);

        /*
         * Étape 1 : Découpe terminée
         */
        $etape1Of3 = new EtapeFabrication();
        $etape1Of3->setNom($typeDecoupe->getNom());
        $etape1Of3->setOrdre(1);
        $etape1Of3->setStatut('TERMINEE');
        $etape1Of3->setDateDebut(
            new \DateTimeImmutable('2026-10-03 09:00:00')
        );
        $etape1Of3->setDateFin(
            new \DateTimeImmutable('2026-10-03 11:00:00')
        );
        $etape1Of3->setOrdreFabrication($of3);
        $etape1Of3->setTypeEtape($typeDecoupe);

        $manager->persist($etape1Of3);

        /*
         * Étape 2 : Assemblage terminée
         */
        $etape2Of3 = new EtapeFabrication();
        $etape2Of3->setNom($typeAssemblage->getNom());
        $etape2Of3->setOrdre(2);
        $etape2Of3->setStatut('TERMINEE');
        $etape2Of3->setDateDebut(
            new \DateTimeImmutable('2026-10-03 11:15:00')
        );
        $etape2Of3->setDateFin(
            new \DateTimeImmutable('2026-10-03 14:00:00')
        );
        $etape2Of3->setOrdreFabrication($of3);
        $etape2Of3->setTypeEtape($typeAssemblage);

        $manager->persist($etape2Of3);

        /*
         * Étape 3 : Contrôle terminé
         */
        $etape3Of3 = new EtapeFabrication();
        $etape3Of3->setNom($typeControle->getNom());
        $etape3Of3->setOrdre(3);
        $etape3Of3->setStatut('TERMINEE');
        $etape3Of3->setDateDebut(
            new \DateTimeImmutable('2026-10-03 14:15:00')
        );
        $etape3Of3->setDateFin(
            new \DateTimeImmutable('2026-10-03 16:00:00')
        );
        $etape3Of3->setOrdreFabrication($of3);
        $etape3Of3->setTypeEtape($typeControle);

        $manager->persist($etape3Of3);

        /*
         * ============================================================
         * ENREGISTREMENT
         * ============================================================
         */

        $manager->flush();
    }
}
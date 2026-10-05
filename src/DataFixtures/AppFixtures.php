<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Produit;
use App\Entity\MatierePremiere;
use App\Entity\User;
use App\Entity\OrdreFabrication;
use App\Entity\EtapeFabrication;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();

        $user->setEmail('production@gpao.fr');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('password');

        $manager->persist($user);



$manager->flush();




$ordre = new OrdreFabrication();

$ordre->setNumero('OF-2026-001');
$ordre->setQuantite(100);
$ordre->setStatut('EN_ATTENTE');
$ordre->setDateCreation(new \DateTimeImmutable());




        $produit1 = new Produit();
        $produit1->setNom('Chaise industrielle');
        $produit1->setReference('CHA-001');
        $produit1->setDescription('Chaise destinée à la production industrielle');

        $produit2 = new Produit();
        $produit2->setNom('Table industrielle');
        $produit2->setReference('TAB-001');
        $produit2->setDescription('Table destinée à la production industrielle');

        $produit3 = new Produit();
        $produit3->setNom('Établi industriel');
        $produit3->setReference('ETA-001');
        $produit3->setDescription('Établi destiné à la production industrielle');

        $acier = new MatierePremiere();
        $acier->setNom('Acier');
        $acier->setReference('MAT-001');
        $acier->setDescription('Acier utilisé pour la fabrication');

        $aluminium = new MatierePremiere();
        $aluminium->setNom('Aluminium');
        $aluminium->setReference('MAT-002');
        $aluminium->setDescription('Aluminium utilisé pour la fabrication');

        $plastique = new MatierePremiere();
        $plastique->setNom('Plastique');
        $plastique->setReference('MAT-003');
        $plastique->setDescription('Plastique utilisé pour la fabrication');


        $produit1->addMatierePremiere($acier);
        $produit1->addMatierePremiere($plastique);

        $produit2->addMatierePremiere($acier);
        $produit2->addMatierePremiere($aluminium);

        $produit3->addMatierePremiere($acier);
        $produit3->addMatierePremiere($aluminium);


$etape1 = new EtapeFabrication();
$etape1->setNom('Découpe');
$etape1->setOrdre(1);
$etape1->setStatut('TERMINEE');
$etape1->setOrdreFabrication($ordre);

$manager->persist($etape1);


$etape2 = new EtapeFabrication();
$etape2->setNom('Assemblage');
$etape2->setOrdre(2);
$etape2->setStatut('EN_COURS');
$etape2->setOrdreFabrication($ordre);

$manager->persist($etape2);


$etape3 = new EtapeFabrication();
$etape3->setNom('Contrôle');
$etape3->setOrdre(3);
$etape3->setStatut('A_FAIRE');
$etape3->setOrdreFabrication($ordre);

$manager->persist($etape3);





        $ordre->setProduit($produit1);
$ordre->setUser($user);

$manager->persist($ordre);

$ordre2 = new OrdreFabrication();
$etape21 = new EtapeFabrication();
$etape21->setNom('Découpe');
$etape21->setOrdre(1);
$etape21->setStatut('TERMINEE');
$etape21->setOrdreFabrication($ordre2);
$manager->persist($etape21);

$etape22 = new EtapeFabrication();
$etape22->setNom('Assemblage');
$etape22->setOrdre(2);
$etape22->setStatut('EN_COURS');
$etape22->setOrdreFabrication($ordre2);
$manager->persist($etape22);

$etape23 = new EtapeFabrication();
$etape23->setNom('Contrôle');
$etape23->setOrdre(3);
$etape23->setStatut('A_FAIRE');
$etape23->setOrdreFabrication($ordre2);
$manager->persist($etape23);





$ordre2->setNumero('OF-2026-002');
$ordre2->setQuantite(50);
$ordre2->setStatut('EN_COURS');
$ordre2->setDateCreation(new \DateTimeImmutable());
$ordre2->setProduit($produit2);
$ordre2->setUser($user);

$manager->persist($ordre2);


$ordre3 = new OrdreFabrication();
$etape31 = new EtapeFabrication();
$etape31->setNom('Découpe');
$etape31->setOrdre(1);
$etape31->setStatut('TERMINEE');
$etape31->setOrdreFabrication($ordre3);
$manager->persist($etape31);

$etape32 = new EtapeFabrication();
$etape32->setNom('Assemblage');
$etape32->setOrdre(2);
$etape32->setStatut('TERMINEE');
$etape32->setOrdreFabrication($ordre3);
$manager->persist($etape32);

$etape33 = new EtapeFabrication();
$etape33->setNom('Contrôle');
$etape33->setOrdre(3);
$etape33->setStatut('TERMINEE');
$etape33->setOrdreFabrication($ordre3);
$manager->persist($etape33);
$ordre3->setNumero('OF-2026-003');
$ordre3->setQuantite(25);
$ordre3->setStatut('TERMINE');
$ordre3->setDateCreation(new \DateTimeImmutable());
$ordre3->setProduit($produit3);
$ordre3->setUser($user);


$ordre->setUser($user);
$ordre2->setUser($user);
$ordre3->setUser($user);




$manager->persist($ordre3);



        $manager->persist($acier);
        $manager->persist($aluminium);
        $manager->persist($plastique);
        $manager->persist($produit1);
        $manager->persist($produit2);
        $manager->persist($produit3);

        $manager->flush();
    }
}

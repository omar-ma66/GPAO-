<?php

namespace App\DataFixtures;

use App\Entity\TypeEtape;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TypeEtapeFixtures extends Fixture
{
    public const DECOUPE = 'type_etape_decoupe';
    public const ASSEMBLAGE = 'type_etape_assemblage';
    public const CONTROLE = 'type_etape_controle';

    public function load(ObjectManager $manager): void
    {
        $donnees = [
            self::DECOUPE => [
                'nom' => 'Découpe',
                'description' => 'Découpe de la matière première.',
            ],
            self::ASSEMBLAGE => [
                'nom' => 'Assemblage',
                'description' => 'Assemblage des différents composants.',
            ],
            self::CONTROLE => [
                'nom' => 'Contrôle',
                'description' => 'Contrôle qualité du produit.',
            ],
        ];

        foreach ($donnees as $reference => $data) {
            $typeEtape = new TypeEtape();

            $typeEtape->setNom($data['nom']);
            $typeEtape->setDescription($data['description']);

            $manager->persist($typeEtape);

            $this->addReference($reference, $typeEtape);
        }

        $manager->flush();
    }
}

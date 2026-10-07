<?php

namespace App\Repository;

use App\Entity\OrdreFabrication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrdreFabricationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrdreFabrication::class);
    }

    /**
     * Retourne les OF qui ne sont pas archivés.
     *
     * @return OrdreFabrication[]
     */
    public function findActifs(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.dateArchivage IS NULL')
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les OF archivés.
     *
     * @return OrdreFabrication[]
     */
    public function findArchives(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.dateArchivage IS NOT NULL')
            ->orderBy('o.dateArchivage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le dernier numéro d'OF de l'année.
     */
    public function findDernierNumeroDeLAnnee(
        string $annee
    ): ?string {
        $resultat = $this->createQueryBuilder('o')
            ->select('o.numero')
            ->andWhere('o.numero LIKE :prefix')
            ->setParameter('prefix', 'OF-' . $annee . '-%')
            ->orderBy('o.numero', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$resultat) {
            return null;
        }

        return $resultat['numero'] ?? null;
    }
}

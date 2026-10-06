<?php

namespace App\Service;

use App\Repository\OrdreFabricationRepository;

class OrdreFabricationNumeroGenerator
{
    public function __construct(
        private OrdreFabricationRepository $repository
    ) {
    }

    public function generate(): string
    {
        $annee = date('Y');

        $dernierNumero = $this->repository->findDernierNumeroDeLAnnee($annee);

        if ($dernierNumero === null) {
            $numero = 1;
        } else {
            $numero = (int) substr($dernierNumero, -3) + 1;
        }

        return sprintf(
            'OF-%s-%03d',
            $annee,
            $numero
        );
    }
}
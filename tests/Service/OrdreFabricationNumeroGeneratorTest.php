<?php

namespace App\Tests\Service;

use App\Repository\OrdreFabricationRepository;
use App\Service\OrdreFabricationNumeroGenerator;
use PHPUnit\Framework\TestCase;

class OrdreFabricationNumeroGeneratorTest extends TestCase
{
    public function testGenereLeProchainNumero(): void
    {
        $repository = $this->createMock(
            OrdreFabricationRepository::class
        );

        $repository
            ->expects(self::once())
            ->method('findDernierNumeroDeLAnnee')
            ->willReturn('OF-2026-003');

        $generator = new OrdreFabricationNumeroGenerator(
            $repository
        );

        $numero = $generator->generate();

        self::assertSame(
            'OF-2026-004',
            $numero
        );
    }
}
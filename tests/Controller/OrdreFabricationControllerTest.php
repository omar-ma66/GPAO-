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
}


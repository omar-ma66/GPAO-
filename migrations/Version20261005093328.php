<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005093328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration conservée pour l’historique : les clés étrangères sont déjà créées par la migration précédente.';
    }

    public function up(Schema $schema): void
    {
        // Les clés étrangères sont déjà créées dans Version20261005091707.
    }

    public function down(Schema $schema): void
    {
        // Aucun changement à annuler.
    }
}
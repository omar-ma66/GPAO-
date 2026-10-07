<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007090550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la date d’archivage des ordres de fabrication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE ordre_fabrication ADD date_archivage DATETIME DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE ordre_fabrication DROP date_archivage'
        );
    }
}

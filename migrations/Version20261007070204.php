<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007070204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend obligatoire la relation entre EtapeFabrication et TypeEtape';
    }

    public function up(Schema $schema): void
    {
        // La migration précédente a créé la clé étrangère FK_ETAPE_TYPE.
        // On la supprime temporairement afin de rendre la colonne obligatoire.
        $this->addSql('
            ALTER TABLE etape_fabrication
            DROP FOREIGN KEY FK_ETAPE_TYPE
        ');

        // La colonne devient obligatoire.
        $this->addSql('
            ALTER TABLE etape_fabrication
            CHANGE type_etape_id type_etape_id INT NOT NULL
        ');

        // On recrée la clé étrangère.
        $this->addSql('
            ALTER TABLE etape_fabrication
            ADD CONSTRAINT FK_ETAPE_TYPE
            FOREIGN KEY (type_etape_id)
            REFERENCES type_etape (id)
        ');
    }

    public function down(Schema $schema): void
    {
        // On supprime la clé étrangère.
        $this->addSql('
            ALTER TABLE etape_fabrication
            DROP FOREIGN KEY FK_ETAPE_TYPE
        ');

        // On rend à nouveau la colonne nullable.
        $this->addSql('
            ALTER TABLE etape_fabrication
            CHANGE type_etape_id type_etape_id INT DEFAULT NULL
        ');

        // On recrée la clé étrangère.
        $this->addSql('
            ALTER TABLE etape_fabrication
            ADD CONSTRAINT FK_ETAPE_TYPE
            FOREIGN KEY (type_etape_id)
            REFERENCES type_etape (id)
        ');
    }
}
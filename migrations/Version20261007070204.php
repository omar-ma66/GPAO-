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
        // On supprime temporairement la clé étrangère
        $this->addSql('
            ALTER TABLE etape_fabrication
            DROP FOREIGN KEY FK_CCE6EE4987738551
        ');

        // On rend la colonne obligatoire
        $this->addSql('
            ALTER TABLE etape_fabrication
            CHANGE type_etape_id type_etape_id INT NOT NULL
        ');

        // On recrée la clé étrangère
        $this->addSql('
            ALTER TABLE etape_fabrication
            ADD CONSTRAINT FK_CCE6EE4987738551
            FOREIGN KEY (type_etape_id)
            REFERENCES type_etape (id)
        ');
    }

    public function down(Schema $schema): void
    {
        // On supprime la clé étrangère
        $this->addSql('
            ALTER TABLE etape_fabrication
            DROP FOREIGN KEY FK_CCE6EE4987738551
        ');

        // On rend à nouveau la colonne nullable
        $this->addSql('
            ALTER TABLE etape_fabrication
            CHANGE type_etape_id type_etape_id INT DEFAULT NULL
        ');

        // On recrée la clé étrangère
        $this->addSql('
            ALTER TABLE etape_fabrication
            ADD CONSTRAINT FK_CCE6EE4987738551
            FOREIGN KEY (type_etape_id)
            REFERENCES type_etape (id)
        ');
    }
}

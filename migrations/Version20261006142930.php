<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006142930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du catalogue des types d’étapes et relation avec les étapes de fabrication';
    }

    public function up(Schema $schema): void
    {
        // Création du catalogue des types d'étapes.
        $this->addSql('
            CREATE TABLE type_etape (
                id INT AUTO_INCREMENT NOT NULL,
                nom VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // Ajout de la relation avec EtapeFabrication.
        // Elle est temporairement nullable car les anciennes étapes
        // n'ont pas encore de TypeEtape.
        $this->addSql('
            ALTER TABLE etape_fabrication
            ADD type_etape_id INT DEFAULT NULL
        ');

        // Création de la clé étrangère.
        $this->addSql('
            ALTER TABLE etape_fabrication
            ADD CONSTRAINT FK_CCE6EE4987738551
            FOREIGN KEY (type_etape_id)
            REFERENCES type_etape (id)
        ');

        // Index attendu par Doctrine.
        $this->addSql('
            CREATE INDEX IDX_CCE6EE4987738551
            ON etape_fabrication (type_etape_id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE etape_fabrication
            DROP FOREIGN KEY FK_CCE6EE4987738551
        ');

        $this->addSql('
            DROP INDEX IDX_CCE6EE4987738551
            ON etape_fabrication
        ');

        $this->addSql('
            ALTER TABLE etape_fabrication
            DROP type_etape_id
        ');

        $this->addSql('DROP TABLE type_etape');
    }
}
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
        // 1. On crée d'abord la table type_etape
        $this->addSql('
            CREATE TABLE type_etape (
                id INT AUTO_INCREMENT NOT NULL,
                nom VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // 2. On ajoute la colonne nullable dans les étapes existantes
        $this->addSql('
            ALTER TABLE etape_fabrication
            ADD type_etape_id INT DEFAULT NULL
        ');

        // 3. Une fois la table créée, on peut créer la clé étrangère
        $this->addSql('
            ALTER TABLE etape_fabrication
            ADD CONSTRAINT FK_ETAPE_TYPE
            FOREIGN KEY (type_etape_id)
            REFERENCES type_etape (id)
        ');

        // 4. Index pour la clé étrangère
        $this->addSql('
            CREATE INDEX IDX_ETAPE_TYPE
            ON etape_fabrication (type_etape_id)
        ');
    }

    public function down(Schema $schema): void
    {
        // On supprime d'abord la contrainte et l'index
        $this->addSql('
            ALTER TABLE etape_fabrication
            DROP FOREIGN KEY FK_ETAPE_TYPE
        ');

        $this->addSql('
            DROP INDEX IDX_ETAPE_TYPE
            ON etape_fabrication
        ');

        // Puis la colonne
        $this->addSql('
            ALTER TABLE etape_fabrication
            DROP type_etape_id
        ');

        // Et enfin la table
        $this->addSql('DROP TABLE type_etape');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007080140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la relation entre les ordres de fabrication et les matières premières';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE ordre_fabrication_matiere_premiere (
                ordre_fabrication_id INT NOT NULL,
                matiere_premiere_id INT NOT NULL,
                INDEX IDX_9C316EF36A91B091 (ordre_fabrication_id),
                INDEX IDX_9C316EF35B42BE3C (matiere_premiere_id),
                PRIMARY KEY (ordre_fabrication_id, matiere_premiere_id)
            ) DEFAULT CHARACTER SET utf8mb4'
        );

        $this->addSql(
            'ALTER TABLE ordre_fabrication_matiere_premiere
             ADD CONSTRAINT FK_9C316EF36A91B091
             FOREIGN KEY (ordre_fabrication_id)
             REFERENCES ordre_fabrication (id)
             ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE ordre_fabrication_matiere_premiere
             ADD CONSTRAINT FK_9C316EF35B42BE3C
             FOREIGN KEY (matiere_premiere_id)
             REFERENCES matiere_premiere (id)
             ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE matiere_premiere
             CHANGE reference reference VARCHAR(255) NOT NULL,
             CHANGE description description LONGTEXT DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE ordre_fabrication_matiere_premiere
             DROP FOREIGN KEY FK_9C316EF36A91B091'
        );

        $this->addSql(
            'ALTER TABLE ordre_fabrication_matiere_premiere
             DROP FOREIGN KEY FK_9C316EF35B42BE3C'
        );

        $this->addSql(
            'DROP TABLE ordre_fabrication_matiere_premiere'
        );

        $this->addSql(
            'ALTER TABLE matiere_premiere
             CHANGE reference reference VARCHAR(100) NOT NULL,
             CHANGE description description LONGTEXT NOT NULL'
        );
    }
}
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005091707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE etape_fabrication (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, ordre INT NOT NULL, statut VARCHAR(30) NOT NULL, date_debut DATETIME DEFAULT NULL, date_fin DATETIME DEFAULT NULL, ordre_fabrication_id INT NOT NULL, INDEX IDX_CCE6EE496A91B091 (ordre_fabrication_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE matiere_premiere (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, reference VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ordre_fabrication (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(50) NOT NULL, quantite INT NOT NULL, statut VARCHAR(30) NOT NULL, date_creation DATETIME NOT NULL, date_debut DATETIME DEFAULT NULL, date_fin DATETIME DEFAULT NULL, produit_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_7FB222D2F347EFB (produit_id), INDEX IDX_7FB222D2A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, reference VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit_matiere_premiere (produit_id INT NOT NULL, matiere_premiere_id INT NOT NULL, INDEX IDX_E40C7A3FF347EFB (produit_id), INDEX IDX_E40C7A3F5B42BE3C (matiere_premiere_id), PRIMARY KEY (produit_id, matiere_premiere_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE etape_fabrication ADD CONSTRAINT FK_CCE6EE496A91B091 FOREIGN KEY (ordre_fabrication_id) REFERENCES ordre_fabrication (id)');
        $this->addSql('ALTER TABLE ordre_fabrication ADD CONSTRAINT FK_7FB222D2F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE ordre_fabrication ADD CONSTRAINT FK_7FB222D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE produit_matiere_premiere ADD CONSTRAINT FK_E40C7A3FF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_matiere_premiere ADD CONSTRAINT FK_E40C7A3F5B42BE3C FOREIGN KEY (matiere_premiere_id) REFERENCES matiere_premiere (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE etape_fabrication DROP FOREIGN KEY FK_CCE6EE496A91B091');
        $this->addSql('ALTER TABLE ordre_fabrication DROP FOREIGN KEY FK_7FB222D2F347EFB');
        $this->addSql('ALTER TABLE ordre_fabrication DROP FOREIGN KEY FK_7FB222D2A76ED395');
        $this->addSql('ALTER TABLE produit_matiere_premiere DROP FOREIGN KEY FK_E40C7A3FF347EFB');
        $this->addSql('ALTER TABLE produit_matiere_premiere DROP FOREIGN KEY FK_E40C7A3F5B42BE3C');
        $this->addSql('DROP TABLE etape_fabrication');
        $this->addSql('DROP TABLE matiere_premiere');
        $this->addSql('DROP TABLE ordre_fabrication');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE produit_matiere_premiere');
    }
}

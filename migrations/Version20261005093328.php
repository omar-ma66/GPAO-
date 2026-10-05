<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005093328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
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
    }
}

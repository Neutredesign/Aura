<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251028111236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE garment ADD user_id INT NOT NULL, ADD image_url VARCHAR(255) NOT NULL, ADD category VARCHAR(32) NOT NULL, ADD color VARCHAR(24) DEFAULT NULL, ADD season VARCHAR(24) DEFAULT NULL, ADD style_tags JSON DEFAULT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE name name VARCHAR(120) NOT NULL');
        $this->addSql('ALTER TABLE garment ADD CONSTRAINT FK_B881175CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B881175CA76ED395 ON garment (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE garment DROP FOREIGN KEY FK_B881175CA76ED395');
        $this->addSql('DROP INDEX IDX_B881175CA76ED395 ON garment');
        $this->addSql('ALTER TABLE garment DROP user_id, DROP image_url, DROP category, DROP color, DROP season, DROP style_tags, DROP created_at, CHANGE name name VARCHAR(255) NOT NULL');
    }
}

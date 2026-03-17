<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260316215405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE post_alert (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_80E6CA2EA76ED395 (user_id), INDEX IDX_80E6CA2EF675F31B (author_id), UNIQUE INDEX user_author_unique (user_id, author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE post_alert ADD CONSTRAINT FK_80E6CA2EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE post_alert ADD CONSTRAINT FK_80E6CA2EF675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE post CHANGE status status VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post_alert DROP FOREIGN KEY FK_80E6CA2EA76ED395');
        $this->addSql('ALTER TABLE post_alert DROP FOREIGN KEY FK_80E6CA2EF675F31B');
        $this->addSql('DROP TABLE post_alert');
        $this->addSql('ALTER TABLE post CHANGE status status VARCHAR(20) DEFAULT \'published\' NOT NULL');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010131155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE csv_dataset (id INT AUTO_INCREMENT NOT NULL, uploaded_by_id INT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, uploaded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', file_size INT NOT NULL, row_count INT NOT NULL, is_configured TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_9ABCBAD5A2B28FE8 (uploaded_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE csv_variable (id INT AUTO_INCREMENT NOT NULL, dataset_id INT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, column_index INT NOT NULL, sample_values JSON DEFAULT NULL, INDEX IDX_BC0A6FF9D47C2D1B (dataset_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE csv_dataset ADD CONSTRAINT FK_9ABCBAD5A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE csv_variable ADD CONSTRAINT FK_BC0A6FF9D47C2D1B FOREIGN KEY (dataset_id) REFERENCES csv_dataset (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE csv_dataset DROP FOREIGN KEY FK_9ABCBAD5A2B28FE8');
        $this->addSql('ALTER TABLE csv_variable DROP FOREIGN KEY FK_BC0A6FF9D47C2D1B');
        $this->addSql('DROP TABLE csv_dataset');
        $this->addSql('DROP TABLE csv_variable');
    }
}

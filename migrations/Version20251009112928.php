<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009112928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article_chart ADD x_axis_label VARCHAR(255) DEFAULT NULL, ADD y_axis_label VARCHAR(255) DEFAULT NULL, DROP x_axis_column, DROP y_axis_column');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article_chart ADD x_axis_column VARCHAR(255) DEFAULT NULL, ADD y_axis_column VARCHAR(255) DEFAULT NULL, DROP x_axis_label, DROP y_axis_label');
    }
}

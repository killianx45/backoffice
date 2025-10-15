<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015135704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create rating table to store user ratings for articles with unique constraint per user per article';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rating (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, article_id INT NOT NULL, stars SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D8892622A76ED395 (user_id), INDEX IDX_D88926227294869C (article_id), UNIQUE INDEX unique_user_article_rating (user_id, article_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D88926227294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D8892622A76ED395');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D88926227294869C');
        $this->addSql('DROP TABLE rating');
    }
}

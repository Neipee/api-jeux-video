<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Création des tables category, editor, user et video_game.
 */
final class Version20261017140452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables category, editor, user et video_game';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE editor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, subcription_to_newsletter TINYINT NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE video_game (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, release_date DATE NOT NULL, description LONGTEXT NOT NULL, cover_image VARCHAR(255) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, category_id INT NOT NULL, editor_id INT NOT NULL, INDEX IDX_24BC6C5012469DE2 (category_id), INDEX IDX_24BC6C506995AC4C (editor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE video_game ADD CONSTRAINT FK_24BC6C5012469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE video_game ADD CONSTRAINT FK_24BC6C506995AC4C FOREIGN KEY (editor_id) REFERENCES editor (id)');
    }

    public function down(Schema $schema): void
    {
    }
}

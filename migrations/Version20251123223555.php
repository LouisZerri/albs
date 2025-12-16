<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251123223555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE badge (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, icon VARCHAR(50) NOT NULL, type VARCHAR(20) NOT NULL, criteria JSON NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE badge_user (badge_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_299D3A50F7A2C2FC (badge_id), INDEX IDX_299D3A50A76ED395 (user_id), PRIMARY KEY (badge_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE line (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(10) NOT NULL, name VARCHAR(100) NOT NULL, color VARCHAR(10) NOT NULL, text_color VARCHAR(20) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE station (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, position INT NOT NULL, branch VARCHAR(30) DEFAULT NULL, line_id INT NOT NULL, INDEX IDX_9F39F8B14D7B7542 (line_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(100) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, displayed_badges JSON NOT NULL, favorite_line_id INT DEFAULT NULL, INDEX IDX_8D93D649102FDB2C (favorite_line_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_station (id INT AUTO_INCREMENT NOT NULL, passed TINYINT(1) NOT NULL, stopped TINYINT(1) NOT NULL, updated_at DATETIME NOT NULL, first_passed_at DATETIME DEFAULT NULL, first_stopped_at DATETIME DEFAULT NULL, user_id INT NOT NULL, station_id INT NOT NULL, INDEX IDX_C734E6BBA76ED395 (user_id), INDEX IDX_C734E6BB21BDB235 (station_id), UNIQUE INDEX user_station_unique (user_id, station_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE badge_user ADD CONSTRAINT FK_299D3A50F7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE badge_user ADD CONSTRAINT FK_299D3A50A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B14D7B7542 FOREIGN KEY (line_id) REFERENCES line (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649102FDB2C FOREIGN KEY (favorite_line_id) REFERENCES line (id)');
        $this->addSql('ALTER TABLE user_station ADD CONSTRAINT FK_C734E6BBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_station ADD CONSTRAINT FK_C734E6BB21BDB235 FOREIGN KEY (station_id) REFERENCES station (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE badge_user DROP FOREIGN KEY FK_299D3A50F7A2C2FC');
        $this->addSql('ALTER TABLE badge_user DROP FOREIGN KEY FK_299D3A50A76ED395');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B14D7B7542');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649102FDB2C');
        $this->addSql('ALTER TABLE user_station DROP FOREIGN KEY FK_C734E6BBA76ED395');
        $this->addSql('ALTER TABLE user_station DROP FOREIGN KEY FK_C734E6BB21BDB235');
        $this->addSql('DROP TABLE badge');
        $this->addSql('DROP TABLE badge_user');
        $this->addSql('DROP TABLE line');
        $this->addSql('DROP TABLE station');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_station');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

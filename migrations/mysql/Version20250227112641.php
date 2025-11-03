<?php

declare(strict_types=1);

namespace DoctrineMigrations\MySQL;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250227112641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tip_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tip_user (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, tip_id INT NOT NULL, read_at DATETIME DEFAULT NULL, INDEX IDX_4EA015BBA76ED395 (user_id), INDEX IDX_4EA015BB476C47F6 (tip_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tip_user ADD CONSTRAINT FK_4EA015BBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tip_user ADD CONSTRAINT FK_4EA015BB476C47F6 FOREIGN KEY (tip_id) REFERENCES tip (id)');
        $this->addSql('ALTER TABLE tip ADD tip_category_id INT NOT NULL, ADD creator_id INT NOT NULL, ADD creation_date DATE NOT NULL, CHANGE message message LONGTEXT NOT NULL, CHANGE role title VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tip ADD CONSTRAINT FK_4883B84CF4A259B6 FOREIGN KEY (tip_category_id) REFERENCES tip_category (id)');
        $this->addSql('ALTER TABLE tip ADD CONSTRAINT FK_4883B84C61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_4883B84CF4A259B6 ON tip (tip_category_id)');
        $this->addSql('CREATE INDEX IDX_4883B84C61220EA6 ON tip (creator_id)');
        $this->addSql('ALTER TABLE user CHANGE learning_level learning_level VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tip DROP FOREIGN KEY FK_4883B84CF4A259B6');
        $this->addSql('ALTER TABLE tip_user DROP FOREIGN KEY FK_4EA015BBA76ED395');
        $this->addSql('ALTER TABLE tip_user DROP FOREIGN KEY FK_4EA015BB476C47F6');
        $this->addSql('DROP TABLE tip_category');
        $this->addSql('DROP TABLE tip_user');
        $this->addSql('ALTER TABLE tip DROP FOREIGN KEY FK_4883B84C61220EA6');
        $this->addSql('DROP INDEX IDX_4883B84CF4A259B6 ON tip');
        $this->addSql('DROP INDEX IDX_4883B84C61220EA6 ON tip');
        $this->addSql('ALTER TABLE tip DROP tip_category_id, DROP creator_id, DROP creation_date, CHANGE message message VARCHAR(255) NOT NULL, CHANGE title role VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE learning_level learning_level VARCHAR(255) NOT NULL');
    }
}

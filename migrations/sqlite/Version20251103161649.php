<?php

declare(strict_types=1);

namespace DoctrineMigrations\SQLite;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251103161649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, location_id INTEGER DEFAULT NULL, appointment_category_id INTEGER NOT NULL, creator_id INTEGER NOT NULL, room_id INTEGER DEFAULT NULL, teacher_id INTEGER DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, all_day BOOLEAN NOT NULL, color VARCHAR(7) NOT NULL, notes CLOB DEFAULT NULL --(DC2Type:json)
        , attendance CLOB DEFAULT NULL --(DC2Type:json)
        , homework CLOB DEFAULT NULL --(DC2Type:json)
        , CONSTRAINT FK_FE38F84464D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_FE38F844698EFF7E FOREIGN KEY (appointment_category_id) REFERENCES appointment_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_FE38F84461220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_FE38F84454177093 FOREIGN KEY (room_id) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_FE38F84441807E1D FOREIGN KEY (teacher_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_FE38F84464D218E ON appointment (location_id)');
        $this->addSql('CREATE INDEX IDX_FE38F844698EFF7E ON appointment (appointment_category_id)');
        $this->addSql('CREATE INDEX IDX_FE38F84461220EA6 ON appointment (creator_id)');
        $this->addSql('CREATE INDEX IDX_FE38F84454177093 ON appointment (room_id)');
        $this->addSql('CREATE INDEX IDX_FE38F84441807E1D ON appointment (teacher_id)');
        $this->addSql('CREATE TABLE appointment_user (appointment_id INTEGER NOT NULL, user_id INTEGER NOT NULL, PRIMARY KEY(appointment_id, user_id), CONSTRAINT FK_9E501E88E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9E501E88A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_9E501E88E5B533F9 ON appointment_user (appointment_id)');
        $this->addSql('CREATE INDEX IDX_9E501E88A76ED395 ON appointment_user (user_id)');
        $this->addSql('CREATE TABLE appointment_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE conversation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, last_message_id INTEGER DEFAULT NULL, initiator_id INTEGER DEFAULT NULL, recipient_id INTEGER DEFAULT NULL, CONSTRAINT FK_8A8E26E9BA0E79C3 FOREIGN KEY (last_message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8A8E26E97DB3B714 FOREIGN KEY (initiator_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8A8E26E9E92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8A8E26E9BA0E79C3 ON conversation (last_message_id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E97DB3B714 ON conversation (initiator_id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E9E92F8F78 ON conversation (recipient_id)');
        $this->addSql('CREATE TABLE exam (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, subject_id INTEGER DEFAULT NULL, user_taking_exam_id INTEGER DEFAULT NULL, exam_name VARCHAR(255) NOT NULL, date DATE DEFAULT NULL, CONSTRAINT FK_38BBA6C623EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_38BBA6C6590A418B FOREIGN KEY (user_taking_exam_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_38BBA6C623EDC87 ON exam (subject_id)');
        $this->addSql('CREATE INDEX IDX_38BBA6C6590A418B ON exam (user_taking_exam_id)');
        $this->addSql('CREATE TABLE location (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, street VARCHAR(255) DEFAULT NULL, house_number VARCHAR(255) DEFAULT NULL, apartment_number VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, conversation_id INTEGER NOT NULL, sender_id INTEGER DEFAULT NULL, content CLOB NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B6BD307F9AC0396 ON message (conversation_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FF624B39D ON message (sender_id)');
        $this->addSql('CREATE TABLE notification (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, message VARCHAR(255) NOT NULL, is_read BOOLEAN NOT NULL, date_created DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE notification_user (notification_id INTEGER NOT NULL, user_id INTEGER NOT NULL, PRIMARY KEY(notification_id, user_id), CONSTRAINT FK_35AF9D73EF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_35AF9D73A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_35AF9D73EF1A9D84 ON notification_user (notification_id)');
        $this->addSql('CREATE INDEX IDX_35AF9D73A76ED395 ON notification_user (user_id)');
        $this->addSql('CREATE TABLE notification_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, hex_color VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE notification_tag_notification (notification_tag_id INTEGER NOT NULL, notification_id INTEGER NOT NULL, PRIMARY KEY(notification_tag_id, notification_id), CONSTRAINT FK_95461D357E759D4C FOREIGN KEY (notification_tag_id) REFERENCES notification_tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_95461D35EF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_95461D357E759D4C ON notification_tag_notification (notification_tag_id)');
        $this->addSql('CREATE INDEX IDX_95461D35EF1A9D84 ON notification_tag_notification (notification_id)');
        $this->addSql('CREATE TABLE room (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, in_location_id INTEGER NOT NULL, room_number VARCHAR(255) NOT NULL, CONSTRAINT FK_729F519B24008A5F FOREIGN KEY (in_location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_729F519B24008A5F ON room (in_location_id)');
        $this->addSql('CREATE TABLE subject (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, color_hex_code VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE tip (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, tip_category_id INTEGER NOT NULL, creator_id INTEGER NOT NULL, message CLOB NOT NULL, title VARCHAR(255) NOT NULL, creation_date DATE NOT NULL, CONSTRAINT FK_4883B84CF4A259B6 FOREIGN KEY (tip_category_id) REFERENCES tip_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4883B84C61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4883B84CF4A259B6 ON tip (tip_category_id)');
        $this->addSql('CREATE INDEX IDX_4883B84C61220EA6 ON tip (creator_id)');
        $this->addSql('CREATE TABLE tip_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE tip_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, tip_id INTEGER NOT NULL, read_at DATETIME DEFAULT NULL, CONSTRAINT FK_4EA015BBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4EA015BB476C47F6 FOREIGN KEY (tip_id) REFERENCES tip (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4EA015BBA76ED395 ON tip_user (user_id)');
        $this->addSql('CREATE INDEX IDX_4EA015BB476C47F6 ON tip_user (tip_id)');
        $this->addSql('CREATE TABLE todo (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, creator_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, due_date DATETIME DEFAULT NULL, CONSTRAINT FK_5A0EB6A061220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5A0EB6A061220EA6 ON todo (creator_id)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, guardian_id INTEGER DEFAULT NULL, private_location_id INTEGER DEFAULT NULL, company_location_id INTEGER DEFAULT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, learning_level VARCHAR(255) DEFAULT NULL, birthday DATE NOT NULL, working_hours CLOB DEFAULT NULL --(DC2Type:json)
        , pfp_path VARCHAR(255) DEFAULT NULL, is_deleted BOOLEAN DEFAULT NULL, deletion_request_token VARCHAR(255) DEFAULT NULL, date_created DATE NOT NULL, medical_information VARCHAR(2000) DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, gender SMALLINT NOT NULL, first_login BOOLEAN NOT NULL, CONSTRAINT FK_8D93D64911CC8B0A FOREIGN KEY (guardian_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8D93D649C82100BB FOREIGN KEY (private_location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8D93D6491F9C9916 FOREIGN KEY (company_location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8D93D64911CC8B0A ON user (guardian_id)');
        $this->addSql('CREATE INDEX IDX_8D93D649C82100BB ON user (private_location_id)');
        $this->addSql('CREATE INDEX IDX_8D93D6491F9C9916 ON user (company_location_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TABLE user_subject (user_id INTEGER NOT NULL, subject_id INTEGER NOT NULL, PRIMARY KEY(user_id, subject_id), CONSTRAINT FK_A3C32070A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A3C3207023EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A3C32070A76ED395 ON user_subject (user_id)');
        $this->addSql('CREATE INDEX IDX_A3C3207023EDC87 ON user_subject (subject_id)');
        $this->addSql('CREATE TABLE user_todo (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, todo_id INTEGER NOT NULL, is_checked BOOLEAN NOT NULL, CONSTRAINT FK_208FFA69A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_208FFA69EA1EBC33 FOREIGN KEY (todo_id) REFERENCES todo (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_208FFA69A76ED395 ON user_todo (user_id)');
        $this->addSql('CREATE INDEX IDX_208FFA69EA1EBC33 ON user_todo (todo_id)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP TABLE appointment_user');
        $this->addSql('DROP TABLE appointment_category');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE exam');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE notification_user');
        $this->addSql('DROP TABLE notification_tag');
        $this->addSql('DROP TABLE notification_tag_notification');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE subject');
        $this->addSql('DROP TABLE tip');
        $this->addSql('DROP TABLE tip_category');
        $this->addSql('DROP TABLE tip_user');
        $this->addSql('DROP TABLE todo');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_subject');
        $this->addSql('DROP TABLE user_todo');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

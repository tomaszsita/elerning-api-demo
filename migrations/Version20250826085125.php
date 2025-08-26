<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826085125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE courses (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, max_seats INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN courses.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE enrollments (id SERIAL NOT NULL, user_id INT NOT NULL, course_id INT NOT NULL, enrolled_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CCD8C132A76ED395 ON enrollments (user_id)');
        $this->addSql('CREATE INDEX IDX_CCD8C132591CC992 ON enrollments (course_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_course ON enrollments (user_id, course_id)');
        $this->addSql('COMMENT ON COLUMN enrollments.enrolled_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN enrollments.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE lessons (id SERIAL NOT NULL, course_id INT NOT NULL, title VARCHAR(255) NOT NULL, content TEXT DEFAULT NULL, order_index INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3F4218D9591CC992 ON lessons (course_id)');
        $this->addSql('COMMENT ON COLUMN lessons.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE prerequisites (id SERIAL NOT NULL, lesson_id INT NOT NULL, required_lesson_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3349E337CDF80196 ON prerequisites (lesson_id)');
        $this->addSql('CREATE INDEX IDX_3349E337A3084252 ON prerequisites (required_lesson_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_lesson_required ON prerequisites (lesson_id, required_lesson_id)');
        $this->addSql('CREATE TABLE progress (id SERIAL NOT NULL, user_id INT NOT NULL, lesson_id INT NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, request_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2201F246A76ED395 ON progress (user_id)');
        $this->addSql('CREATE INDEX IDX_2201F246CDF80196 ON progress (lesson_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_lesson ON progress (user_id, lesson_id)');
        $this->addSql('COMMENT ON COLUMN progress.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE users (id SERIAL NOT NULL, email VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE enrollments ADD CONSTRAINT FK_CCD8C132A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrollments ADD CONSTRAINT FK_CCD8C132591CC992 FOREIGN KEY (course_id) REFERENCES courses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE lessons ADD CONSTRAINT FK_3F4218D9591CC992 FOREIGN KEY (course_id) REFERENCES courses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prerequisites ADD CONSTRAINT FK_3349E337CDF80196 FOREIGN KEY (lesson_id) REFERENCES lessons (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prerequisites ADD CONSTRAINT FK_3349E337A3084252 FOREIGN KEY (required_lesson_id) REFERENCES lessons (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE progress ADD CONSTRAINT FK_2201F246A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE progress ADD CONSTRAINT FK_2201F246CDF80196 FOREIGN KEY (lesson_id) REFERENCES lessons (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE enrollments DROP CONSTRAINT FK_CCD8C132A76ED395');
        $this->addSql('ALTER TABLE enrollments DROP CONSTRAINT FK_CCD8C132591CC992');
        $this->addSql('ALTER TABLE lessons DROP CONSTRAINT FK_3F4218D9591CC992');
        $this->addSql('ALTER TABLE prerequisites DROP CONSTRAINT FK_3349E337CDF80196');
        $this->addSql('ALTER TABLE prerequisites DROP CONSTRAINT FK_3349E337A3084252');
        $this->addSql('ALTER TABLE progress DROP CONSTRAINT FK_2201F246A76ED395');
        $this->addSql('ALTER TABLE progress DROP CONSTRAINT FK_2201F246CDF80196');
        $this->addSql('DROP TABLE courses');
        $this->addSql('DROP TABLE enrollments');
        $this->addSql('DROP TABLE lessons');
        $this->addSql('DROP TABLE prerequisites');
        $this->addSql('DROP TABLE progress');
        $this->addSql('DROP TABLE users');
    }
}

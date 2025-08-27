<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create initial database schema for e-learning platform
 */
final class Version20250826085125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database schema for e-learning platform';
    }

    public function up(Schema $schema): void
    {
        // Create users table
        $this->addSql('
            CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ');

        // Create courses table
        $this->addSql('
            CREATE TABLE courses (
                id SERIAL PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                max_seats INT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ');

        // Create lessons table
        $this->addSql('
            CREATE TABLE lessons (
                id SERIAL PRIMARY KEY,
                course_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                order_index INT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                FOREIGN KEY (course_id) REFERENCES courses(id)
            )
        ');

        // Create enrollments table
        $this->addSql('
            CREATE TABLE enrollments (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL,
                course_id INT NOT NULL,
                enrolled_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                completed_at TIMESTAMP(0) WITHOUT TIME ZONE,
                status VARCHAR(20) NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (course_id) REFERENCES courses(id),
                UNIQUE(user_id, course_id)
            )
        ');

        // Create progress table
        $this->addSql('
            CREATE TABLE progress (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL,
                lesson_id INT NOT NULL,
                completed_at TIMESTAMP(0) WITHOUT TIME ZONE,
                request_id VARCHAR(255),
                status VARCHAR(20) NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (lesson_id) REFERENCES lessons(id),
                UNIQUE(user_id, lesson_id)
            )
        ');

        // Create progress_history table for audit trail
        $this->addSql('
            CREATE TABLE progress_history (
                id SERIAL PRIMARY KEY,
                progress_id INT NOT NULL,
                user_id INT NOT NULL,
                lesson_id INT NOT NULL,
                old_status VARCHAR(20),
                new_status VARCHAR(20) NOT NULL,
                changed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                request_id VARCHAR(255),
                FOREIGN KEY (progress_id) REFERENCES progress(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (lesson_id) REFERENCES lessons(id)
            )
        ');

        // Create indexes for better performance
        $this->addSql('CREATE INDEX idx_enrollments_user ON enrollments(user_id)');
        $this->addSql('CREATE INDEX idx_enrollments_course ON enrollments(course_id)');
        $this->addSql('CREATE INDEX idx_lessons_course ON lessons(course_id)');
        $this->addSql('CREATE INDEX idx_progress_user ON progress(user_id)');
        $this->addSql('CREATE INDEX idx_progress_lesson ON progress(lesson_id)');
        $this->addSql('CREATE INDEX idx_progress_history_progress ON progress_history(progress_id)');
        $this->addSql('CREATE INDEX idx_progress_history_user ON progress_history(user_id)');
        $this->addSql('CREATE INDEX idx_progress_history_lesson ON progress_history(lesson_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop tables in reverse order (respecting foreign key constraints)
        $this->addSql('DROP TABLE progress_history');
        $this->addSql('DROP TABLE progress');
        $this->addSql('DROP TABLE enrollments');
        $this->addSql('DROP TABLE lessons');
        $this->addSql('DROP TABLE courses');
        $this->addSql('DROP TABLE users');
    }
}

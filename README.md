# E-Learning API

RESTful API for e-learning platform with user enrollment, progress tracking, and course management.

## Tech Stack

- **Framework**: Symfony 7 + PHP 8.4
- **Database**: PostgreSQL + Doctrine ORM
- **Testing**: PHPUnit
- **Code Quality**: PHPStan, PHP CS Fixer, PHP Code Sniffer
- **Containerization**: Docker + Docker Compose

## Quick Start

```bash
# Clone and setup
git clone <repo>
cd pwc-elerning-api

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Setup database
docker-compose exec app bin/console doctrine:migrations:migrate
docker-compose exec app bin/console app:load-test-data

# Run tests
docker-compose exec app bin/phpunit

# Check code quality
make code-quality
```

## API Endpoints

### Courses
- `GET /courses` - List courses with remaining seats
- `POST /courses/{id}/enroll` - Enroll user in course

### Progress
- `POST /progress` - Create/update progress
- `GET /progress/{user_id}/courses/{course_id}` - Get user progress summary
- `DELETE /progress/{user_id}/lessons/{lesson_id}` - Reset progress
- `GET /progress/{user_id}/lessons/{lesson_id}/history` - Get progress history

### Users
- `GET /users/{id}/courses` - Get user's enrolled courses

## Development Tools

### Code Quality
```bash
# Run all quality checks
make code-quality

# Fix formatting
make fix

# Individual tools
make phpcs          # PHP Code Sniffer
make phpstan        # Static analysis
make php-cs-fixer   # Code formatting
```

### Testing
```bash
# Run all tests
make test

# Run with coverage
make test-coverage
```

### Database
```bash
# Load test data
docker-compose exec app bin/console app:load-test-data

# Show database content
docker-compose exec app bin/console app:show-data

# Reset database
docker-compose exec app bin/console doctrine:query:sql "TRUNCATE TABLE users, courses, lessons, enrollments, progress, progress_history CASCADE"
```

## Architecture

- **Controllers**: Handle HTTP requests/responses
- **Services**: Business logic with single responsibility
- **Repositories**: Data access with interfaces
- **Entities**: Doctrine ORM models
- **Events**: Progress history tracking

## Key Features

- User enrollment with capacity limits
- Progress tracking with status transitions
- Prerequisites (order-based)
- Idempotent operations via request_id
- Progress history via events
- Comprehensive test coverage
- Clean architecture with SOLID principles

# PWC E-Learning API

A Symfony-based e-learning API for managing courses, users, enrollments, and progress tracking.

## Features

- Course management
- User enrollment system
- Progress tracking
- Prerequisites validation
- RESTful API endpoints
- Comprehensive test coverage

## Requirements

- PHP 8.4+
- Docker and Docker Compose
- PostgreSQL

## Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd pwc-elerning-api
   ```

2. **Start the application**
   ```bash
   docker-compose up -d
   ```

3. **Install dependencies**
   ```bash
   docker-compose exec app composer install
   ```

4. **Run migrations**
   ```bash
   docker-compose exec app bin/console doctrine:migrations:migrate
   ```

5. **Load test data**
   ```bash
   docker-compose exec app bin/console app:load-test-data
   ```

6. **Access the API**
   - API Base URL: http://localhost:8080
   - Health Check: http://localhost:8080/health

## Code Quality Tools

This project includes comprehensive code quality tools to maintain high code standards:

### Available Tools

- **PHP Code Sniffer (PHPCS)** - Detects coding standard violations
- **PHP Code Beautifier and Fixer (PHPCBF)** - Automatically fixes coding standards
- **PHP CS Fixer** - Advanced code style fixer
- **PHPStan** - Static analysis tool

### Usage

#### Using Make Commands (Recommended)

```bash
# Run all code quality checks
make code-quality

# Run individual tools
make phpcs          # Check coding standards
make phpcbf         # Fix coding standards automatically
make php-cs-fixer   # Run PHP CS Fixer
make phpstan        # Run static analysis

# Fix all code style issues
make fix

# Show all available commands
make help
```

#### Using Composer Scripts

```bash
# Run PHPCS
docker-compose exec app composer phpcs

# Fix with PHPCBF
docker-compose exec app composer phpcs:fix

# Run PHP CS Fixer
docker-compose exec app composer php-cs-fixer

# Check with PHP CS Fixer (dry run)
docker-compose exec app composer php-cs-fixer:check
```

#### Using Direct Scripts

```bash
# Run PHPCS
./scripts/phpcs.sh

# Fix with PHPCBF
./scripts/phpcbf.sh

# Run PHP CS Fixer
./scripts/php-cs-fixer.sh
```

### Configuration Files

- `phpcs.xml` - PHP Code Sniffer configuration (PSR-12 + Symfony standards)
- `.php-cs-fixer.php` - PHP CS Fixer configuration
- `phpstan.dist.neon` - PHPStan configuration

## API Endpoints

### Health Check
- `GET /health` - Application health status

### Courses
- `GET /courses` - List all courses
- `POST /courses/{id}/enroll` - Enroll user in course

### Progress
- `POST /progress` - Create progress record
- `GET /progress/{userId}/courses/{courseId}` - Get user progress for course
- `DELETE /progress/{userId}/lessons/{lessonId}` - Delete progress record

### Users
- `GET /users/{id}/courses` - Get user's enrolled courses

## Testing

```bash
# Run all tests
make test

# Run tests with coverage
make test-coverage

# Run tests in Docker
docker-compose exec app composer test
```

## Development

### Docker Commands

```bash
# Start containers
make up

# Stop containers
make down

# View logs
make logs

# Build containers
make build
```

### Database

```bash
# Run migrations
docker-compose exec app bin/console doctrine:migrations:migrate

# Load test data
docker-compose exec app bin/console app:load-test-data

# Show data
docker-compose exec app bin/console app:show-data
```

## Code Quality Standards

This project follows:
- **PSR-12** - PHP Standards Recommendations
- **Symfony Coding Standards** - Symfony-specific conventions
- **PHPStan Level 8** - Maximum static analysis coverage

All code quality tools are configured to run in Docker containers, ensuring consistent environments across development and CI/CD pipelines.

For detailed information about code quality tools, see [CODE_QUALITY.md](CODE_QUALITY.md).

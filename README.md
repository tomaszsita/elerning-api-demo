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

# Complete setup (install deps, create DBs, run migrations, load data)
make setup

# Run tests
make test

# Check code quality
make code-quality
```

## API Endpoints

### Courses
- `GET /courses` - List all courses with remaining seats and lessons
  - **Response**: courses with `max_seats`, `remaining_seats`, and `lessons` array
  - **Example Response**:
    ```json
    {
      "courses": [
        {
          "id": 1,
          "title": "PHP Fundamentals",
          "description": "Learn the basics of PHP programming",
          "max_seats": 5,
          "remaining_seats": 3,
          "lessons": [
            {
              "id": 1,
              "title": "Introduction to PHP",
              "order_index": 1
            }
          ]
        }
      ]
    }
    ```

- `POST /courses/{id}/enroll` - Enroll user in course
  - **Payload**:
    ```json
    {
      "user_id": 123
    }
    ```
  - **Response**: enrollment details with status "enrolled"
  - **Example Response**:
    ```json
    {
      "id": 1,
      "user_id": 123,
      "course_id": 1,
      "course_title": "PHP Fundamentals",
      "status": "enrolled",
      "enrolled_at": "2025-08-28 13:39:36"
    }
    ```

### Progress
- `POST /progress` - Create/update lesson progress
  - **Payload**:
    ```json
    {
      "user_id": 123,
      "lesson_id": 456,
      "action": "complete",
      "request_id": "abc-123"
    }
    ```
  - **Actions**: `complete`, `failed`, `pending`
  - **Idempotent**: same `request_id` returns 200 OK with existing result
  - **Example Response**:
    ```json
    {
      "id": 1,
      "user_id": 123,
      "lesson_id": 456,
      "lesson_title": "Introduction to PHP",
      "status": "complete",
      "request_id": "abc-123",
      "completed_at": "2025-08-28 13:39:41"
    }
    ```

- `GET /progress/{user_id}/courses/{course_id}` - Get user progress summary
  - **Response**: course progress with completion percentage and lesson statuses
  - **Example Response**:
    ```json
    {
      "course_id": 1,
      "course_title": "PHP Fundamentals",
      "progress_percent": 67,
      "total_lessons": 3,
      "completed_lessons": 2,
      "lessons": [
        {
          "id": 1,
          "title": "Introduction to PHP",
          "status": "complete"
        },
        {
          "id": 2,
          "title": "Variables and Data Types",
          "status": "complete"
        },
        {
          "id": 3,
          "title": "Control Structures",
          "status": "pending"
        }
      ]
    }
    ```

- `DELETE /progress/{user_id}/lessons/{lesson_id}` - Reset lesson progress
  - **Response**: 204 No Content on success
  - **Restrictions**: Only allowed from `failed` status
  - **Example**: `DELETE /progress/123/lessons/456`

- `GET /progress/{user_id}/lessons/{lesson_id}/history` - Get progress history
  - **Response**: array of status changes with timestamps
  - **Example Response**:
    ```json
    {
      "lesson_id": 456,
      "lesson_title": "Introduction to PHP",
      "history": [
        {
          "id": 1,
          "old_status": null,
          "new_status": "pending",
          "changed_at": "2025-08-28 13:30:00",
          "request_id": "req-123"
        },
        {
          "id": 2,
          "old_status": "pending",
          "new_status": "complete",
          "changed_at": "2025-08-28 13:39:41",
          "request_id": "req-456"
        }
      ]
    }
    ```

### Users
- `GET /users/{id}/courses` - Get user's enrolled courses
  - **Response**: list of courses user is enrolled in
  - **Example Response**:
    ```json
    {
      "user_id": 123,
      "courses": [
        {
          "id": 1,
          "title": "PHP Fundamentals",
          "description": "Learn the basics of PHP programming",
          "enrolled_at": "2025-08-28 13:39:36",
          "status": "enrolled"
        }
      ]
    }
    ```

## Development Tools

### Setup & Installation
```bash
# Complete project setup
make setup

# Install dependencies only
make install

# Start/stop containers
make up
make down
```

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
  - `ProgressCreationService`: Creates/updates progress with idempotency
  - `ProgressStatusService`: Manages status transitions and events
  - `ProgressQueryService`: Retrieves progress data and summaries
- **Repositories**: Data access with interfaces
- **Entities**: Doctrine ORM models
- **Events**: Progress history tracking via `ProgressChangedEvent`

## Key Features

- **User Enrollment**: Capacity limits with optimistic locking
- **Progress Tracking**: Status transitions (pending → complete/failed, failed → complete/pending)
- **Prerequisites**: Order-based lesson completion requirements
- **Idempotency**: Safe retry via `request_id` parameter
- **Progress History**: Automatic tracking of all status changes
- **Comprehensive Testing**: 100% test coverage with feature and unit tests
- **Clean Architecture**: SOLID principles with dependency injection
- **Code Quality**: Static analysis, formatting, and style checks

## Business Rules

- Users must be enrolled to access course lessons
- Lessons must be completed in order (prerequisites)
- Progress status transitions follow defined rules
- Course enrollment respects capacity limits
- All progress changes are tracked in history

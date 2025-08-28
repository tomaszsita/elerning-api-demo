# Implementation Decisions

## Tech Stack
- Symfony 7 + PHP 8.4
- PostgreSQL + Doctrine ORM
- PHPUnit for testing
- PHPStan for static analysis

## Key Decisions

### Service Architecture
Split `ProgressService` into three focused services:
- `ProgressCreationService`: Progress creation and idempotency
- `ProgressStatusService`: Status changes and events
- `ProgressQueryService`: Queries and summaries

### Event-Driven History
Progress history tracked via Symfony EventDispatcher. Keeps services focused and allows easy extension.

### Factory Pattern
Factories for Progress, Enrollment, ProgressHistory, ProgressChangedEvent. Cleaner testing and DI.

### Repository Interfaces
All repositories implement interfaces for better testing and DI.

### API Design
- POST /progress: Omitted redundant `course_id`, added `action` parameter
- RESTful with proper HTTP codes (200, 201, 400, 404, 409)
- Idempotent operations via `request_id`

### Database Design
- Order-based prerequisites (lesson index)
- Progress reset to 'pending' instead of delete (preserves audit trail)
- Pessimistic locking for concurrent enrollments

### Code Quality
- PHPStan level 8, 0 errors
- 100% test coverage
- SOLID principles throughout

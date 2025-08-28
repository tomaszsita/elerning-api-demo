# Implementation Decisions

## Tech Stack
Went with Symfony 7 + PHP 8.4. Latest versions show I keep up with current practices, plus Symfony's ecosystem is solid for this kind of project.

## Key Decisions

### Event-Driven Progress History
Instead of directly saving progress history in the service, I used Symfony's EventDispatcher. This keeps the progress service focused on its core logic and makes it easy to add things like notifications later.

**Trade-off**: Bit more complex setup, but much cleaner separation of concerns.

### Factory Pattern
Created factories for Progress, Enrollment, ProgressHistory, ProgressChangedEvent, and test data. Makes testing easier with proper DI and keeps entity creation logic in one place.

**Trade-off**: More files, but services are cleaner and more testable.

### Service Refactoring
Split the large `ProgressService` into three focused services:
- `ProgressCreationService`: Handles progress creation logic and idempotency
- `ProgressStatusService`: Manages status changes and event dispatching  
- `ProgressQueryService`: Handles progress queries and summary calculations

**Trade-off**: More services, but each has a single responsibility and is easier to test and maintain.

### Repository Interfaces
All repositories implement interfaces. This makes testing easier (can mock them) and follows DI principles.

### RESTful API
Standard REST conventions with proper HTTP codes (400, 404, 409). Nothing fancy, just predictable endpoints.

### API Design Choices

#### POST /progress endpoint
- **Omitted `course_id`**: Redundant since it can be derived from `lesson_id`. This simplifies the API and reduces potential inconsistencies.
- **Added `action` parameter**: Allows users to specify progress status ('complete', 'fail', 'pending') instead of defaulting to 'complete'. This provides more flexibility while maintaining backward compatibility (defaults to 'complete').
- **Validation**: `action` is validated against allowed choices to prevent invalid states.

## Database Design Choices

### Prerequisites: Order-based vs Explicit Entity
Went with order-based (lesson index determines prerequisites). The requirements didn't specify complex prerequisite logic, so I kept it simple. YAGNI principle.

**Trade-off**: Less flexible, but much simpler schema and logic.

### Progress Reset: Delete vs Reset
Chose to reset progress to 'pending' instead of deleting records. This preserves audit trail and allows for analytics.

**Trade-off**: Slightly more complex, but business value of keeping history outweighs the complexity.

### Transactions
EnrollmentService uses pessimistic locking with proper transaction management for concurrent enrollment safety. Checks for existing transactions to avoid nested transaction issues.

### Exceptions
Consolidated multiple "not found" exceptions into a single `EntityNotFoundException` and enrollment-related exceptions into `EnrollmentException` for better maintainability and consistency.

## Requirements Coverage

### Core Features ✅
- User enrollment with validation
- Progress tracking with status transitions  
- Prerequisites (order-based)
- Course capacity limits
- Idempotent progress creation

### Nice-to-Have ✅
- Progress reset (DELETE endpoint)
- Progress history (event-driven)
- CLI commands for data loading
- 61 tests covering edge cases

### Code Quality ✅
- PHPStan level 6, 0 errors
- SOLID principles
- Clean architecture
- Comprehensive docs

## Architecture

```
Controllers → Services → Repositories → Entities
```

Used standard patterns:
- Repository pattern for data access
- Factory pattern for entity creation  
- Event-driven for progress tracking
- DI throughout

## Testing
- Unit tests for services/entities
- Feature tests for API endpoints
- PHPStan for static analysis
- Postman collection for manual testing

## Production Considerations
- Database indexes on foreign keys
- Input validation with Symfony Validator
- Error handling without exposing internals
- Stateless API (easy to scale)

The implementation focuses on clean, testable code while meeting all requirements. The architecture allows for easy addition of features like caching, rate limiting, or async processing when needed.

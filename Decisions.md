# Architectural Decisions

## Data Model & Domain Design

### 1. Entity Validation
**Decision:** Add Symfony Validator constraints to all entities
**Rationale:** 
- Input validation at entity level
- Better API error responses
- Type safety and data integrity
- Consistent validation across the application

**Implementation:** Using `#[Assert\*]` attributes on entity properties

### 2. Repository Interfaces
**Decision:** Create interfaces for all repositories
**Rationale:**
- Dependency Inversion Principle
- Better testability (easy mocking)
- Clear contracts between layers
- SOLID compliance

**Implementation:** `src/Repository/Interfaces/` with `*RepositoryInterface`

### 3. DTOs/Request Objects
**Decision:** Implement DTOs for all API requests
**Rationale:**
- Input validation and type safety
- API documentation through code
- Separation of concerns
- Immutable request objects

**Implementation:** `src/Request/` with `*Request` classes

### 3.1. Response Serialization
**Decision:** Use JsonSerializable interface for entity serialization
**Rationale:**
- Clean controller code (no manual array mapping)
- Reusable serialization logic
- Consistent response format
- Easy testing and maintenance

**Implementation:** `toArray()` method in entities + `JsonSerializable` interface

**Potential Improvements:**
- **Response DTOs:** Create dedicated response objects for better API versioning and documentation
- **Serializer Component:** Use Symfony Serializer for more advanced serialization features (groups, normalization, etc.)
- **API Resources:** Implement Laravel-style API resources for complex response transformations

### 3.2. Event Listeners
**Decision:** Remove unused domain events for now
**Rationale:**
- YAGNI principle - no current need for event-driven architecture
- Reduce complexity and maintenance burden
- Avoid performance overhead from unused event dispatching
- Keep codebase focused on current requirements

**Implementation:** Removed `EnrollmentCreatedEvent` and `ProgressCompletedEvent`

**Potential Future Improvements:**
- **Email Notifications:** Add listeners for enrollment/progress events to send welcome emails, completion certificates
- **Audit Logging:** Implement listeners to log all enrollment and progress changes for compliance
- **Real-time Notifications:** Add WebSocket listeners for live progress updates
- **Analytics Integration:** Create listeners to track user behavior and course completion metrics
- **Cache Invalidation:** Implement listeners to clear relevant caches when data changes
- **External Integrations:** Add listeners for third-party service integrations (LMS, CRM, etc.)

### 4. Value Objects
**Decision:** Create Value Objects for domain concepts
**Rationale:**
- Type safety for domain concepts
- Encapsulation of domain logic
- Immutability
- Self-validating objects

**Implementation:** `src/ValueObject/` with Email, CourseTitle, etc.

### 5. Domain Events
**Decision:** Implement domain events for key operations
**Rationale:**
- Decoupling of domain logic
- Extensibility without modifying existing code
- Event-driven architecture
- Audit trail capabilities

**Implementation:** `src/Event/` with `*Event` classes

### 6. Custom Exceptions
**Decision:** Create domain-specific exceptions
**Rationale:**
- Better error handling
- Specific exception types
- Clear error messages
- Proper HTTP status codes

**Implementation:** `src/Exception/` with `*Exception` classes

## Database Design

### 7. Unique Constraints
**Decision:** Database-level unique constraints
**Rationale:**
- Data integrity at database level
- Prevents race conditions
- Clear business rules enforcement

**Implementation:** 
- `unique_user_course` on enrollments
- `unique_user_lesson` on progress
- `unique_lesson_required` on prerequisites

### 8. Idempotency via request_id
**Decision:** Use request_id for idempotent operations
**Rationale:**
- Prevents duplicate operations
- Safe retry mechanisms
- Distributed system compatibility

**Implementation:** `request_id` field in Progress entity

### 15. Progress Reset Strategy
**Decision:** Reset progress to 'pending' instead of deleting records
**Rationale:**
- Preserves audit trail and learning history
- Enables analytics on user learning patterns
- Maintains data integrity for business intelligence
- Better user experience with learning history

**Implementation:** 
- DELETE endpoint resets status to PENDING instead of removing record
- ProgressStatus enum allows COMPLETE → PENDING and FAILED → PENDING transitions
- CompletedAt timestamp is cleared on reset
- Both completed and failed progress can be reset to allow retry

### 16. Data Providers in Tests
**Decision:** Use PHPUnit 12 data providers with attributes for better test maintainability
**Rationale:**
- Reduces code duplication in tests
- Makes test cases more readable and maintainable
- Easier to add new test scenarios
- Better test organization and structure

**Implementation:**
- Use `#[DataProvider('providerName')]` attributes (PHPUnit 12 syntax)
- Data provider methods must be `static`
- Provider methods return arrays with descriptive keys
- Applied to: ProgressStatusTest, HttpExceptionMappingTest, ProgressControllerTest, CourseControllerTest

**Benefits:**
- Reduced test methods from 15+ to 8 in ProgressControllerTest
- Clearer test intent with descriptive provider names
- Easier to maintain and extend test coverage
- Better test organization and readability

### 17. Prerequisites Implementation Strategy
**Decision:** Use order-based prerequisites instead of explicit Prerequisite entity
**Rationale:**
- Simpler implementation - prerequisites based on lesson order index
- No need for complex many-to-many relationships
- Easier to maintain and understand
- Follows natural course progression

**Implementation:**
- Removed Prerequisite entity and PrerequisiteRepository
- PrerequisitesService uses `findByCourseAndOrderLessThan()` to check previous lessons
- All lessons with lower orderIndex are considered prerequisites
- Cleaner database schema without prerequisites table

**Benefits:**
- Reduced complexity and code maintenance
- Simpler database schema
- Natural course progression logic
- Easier to understand and debug

### 18. Database Migration Simplification
**Decision:** Simplify Doctrine-generated migrations for better readability
**Rationale:**
- Doctrine-generated migrations are overly verbose and hard to read
- Manual SQL is more readable and maintainable
- Easier to understand database schema at a glance
- Better developer experience

**Implementation:**
- Combined multiple migrations into one clean migration
- Used readable SQL with proper formatting and comments
- Organized tables creation in logical order (users → courses → lessons → enrollments → progress)
- Added descriptive comments for each table
- Used meaningful index names instead of auto-generated ones

**Benefits:**
- Much more readable and maintainable migrations
- Easier to understand database schema
- Better developer experience
- Cleaner git history

## API Design

### 9. RESTful Endpoints
**Decision:** Follow REST conventions
**Rationale:**
- Standard HTTP methods
- Predictable URL structure
- Proper status codes
- Stateless operations

### 10. Error Handling
**Decision:** Consistent error response format
**Rationale:**
- Standardized API responses
- Clear error messages
- Proper HTTP status codes
- Client-friendly error handling

## Testing Strategy

### 11. Unit Tests
**Decision:** Test services and domain logic
**Rationale:**
- Fast feedback loop
- Isolated testing
- High coverage
- Regression prevention

### 12. Integration Tests
**Decision:** Test API endpoints
**Rationale:**
- End-to-end validation
- Database integration
- Real HTTP requests
- API contract validation

### 13. Static Code Analysis
**Decision:** Use PHPStan for static code analysis
**Rationale:**
- Catch type errors before runtime
- Enforce coding standards
- Improve code quality
- Prevent bugs early

**Implementation:** 
- PHPStan level 6 (strict)
- Exclude tests from analysis
- Ignore Doctrine-specific patterns
- Custom ignore patterns for common false positives

**Ignored Patterns:**
- **Doctrine repositories** - `$_em` property access and `find()` method calls
- **PHPUnit mocks** - `expects()` method calls and test properties
- **Doctrine collections** - Generic type specifications
- **Array return types** - Missing value type specifications (can be improved with PHP 8.4+)
- **Entity ID properties** - Unused int type in nullable properties
- **Value objects** - Undefined `$value` property access
- **Bootstrap** - `method_exists()` function calls

## 14. Factory Pattern

### Decision
Use Factory Pattern for entity creation to improve code organization and testability.

### Implemented Factories
- `ProgressFactory` - creates Progress entities
- `EnrollmentFactory` - creates Enrollment entities  
- `TestDataFactory` - creates test data entities (User, Course, Lesson)

### Benefits
- Single Responsibility Principle
- Easier testing with mocks
- Consistent entity creation
- Centralized creation logic
- Better maintainability

### Usage
```php
// Before
$progress = new Progress();
$progress->setUser($user);
$progress->setLesson($lesson);
// ... more setters

// After
$progress = $this->progressFactory->create($user, $lesson, $requestId, $status);
```

### Future Improvements
- Add validation to factories
- Create DTOs for factory input
- Add factory interfaces for better abstraction

**Commands:**
- `composer phpstan` - Run static analysis
- `composer phpstan:baseline` - Generate baseline for new errors

**Future Improvements:**
- **PHP 8.4+ attributes** - Replace PHPDoc with `#[method]` attributes when supported
- **Generic collections** - Add proper generic types to Doctrine collections
- **Array return types** - Specify value types in array return types

## 15. Progress Reset Strategy

**Decision**: Reset progress to 'pending' instead of deleting records to preserve audit trail.

**Rationale**:
- Maintains complete history of progress changes
- Allows for analytics and reporting
- Preserves data integrity
- Enables progress recovery if needed

**Implementation**:
- `deleteProgress()` method resets status to `PENDING` instead of removing record
- Updates `ProgressStatus` enum to allow `COMPLETE` → `PENDING` and `FAILED` → `PENDING` transitions
- Clears `completedAt` timestamp when resetting to pending
- Maintains all existing progress history

**Benefits**:
- Complete audit trail preserved
- Analytics and reporting capabilities
- Data recovery options
- Consistent with business requirements

## 16. Data Providers in Tests

**Decision**: Use PHPUnit 12 data providers with attributes for better test organization.

**Rationale**:
- Reduces test code duplication
- Improves test readability
- Makes test maintenance easier
- Follows PHPUnit 12 best practices

**Implementation**:
- Use `#[DataProvider]` attributes on test methods
- Create `static` provider methods for data sets
- Group related test cases in single test methods
- Maintain descriptive test names with data set labels

**Benefits**:
- Cleaner test code
- Better test coverage
- Easier maintenance
- Modern PHPUnit practices

## 17. Prerequisites Implementation Strategy

**Decision**: Use order-based prerequisites instead of explicit Prerequisite entity.

**Rationale**:
- Simpler data model
- Easier to understand and maintain
- Reduces database complexity
- Follows YAGNI principle

**Implementation**:
- Remove `Prerequisite` entity and related code
- Use lesson `order_index` for prerequisite checking
- Lessons with lower order must be completed before higher order
- Update `PrerequisitesService` to use order-based logic

**Benefits**:
- Simpler database schema
- Easier to understand business logic
- Reduced complexity
- Better performance

## 18. Database Migration Simplification

**Decision**: Simplify Doctrine-generated migrations for better readability and maintainability.

**Rationale**: 
- Doctrine auto-generated migrations are verbose and hard to read
- Manual SQL provides better control over table structure
- Cleaner migration history with consolidated changes

**Implementation**:
- Rewrite migrations using raw SQL instead of Doctrine's Schema API
- Combine multiple migrations into single comprehensive migration
- Use proper SQL formatting with comments and logical table order
- Replace auto-generated index names with meaningful ones (idx_*)
- Organize tables in dependency order: users → courses → lessons → enrollments → progress

**Benefits**:
- Much more readable and maintainable database schema
- Easier to understand table relationships and constraints
- Better developer experience when reviewing migrations
- Cleaner migration history

## 19. Event-Driven Progress History

**Decision**: Implement event-driven architecture for tracking progress changes using Symfony's EventDispatcher.

**Rationale**:
- Decouples progress tracking from business logic
- Allows for easy extension of history tracking features
- Follows Symfony's event-driven patterns
- Provides audit trail for compliance and analytics

**Implementation**:
- `ProgressChangedEvent`: Event dispatched when progress status changes
- `ProgressHistoryListener`: Listener that records history entries
- `ProgressHistory` entity: Stores audit trail with old/new status, timestamps, request IDs
- Event registration in `services.yaml` with proper tags
- Foreign key with CASCADE DELETE to maintain referential integrity

**Benefits**:
- Complete audit trail of all progress changes
- Easy to extend with additional listeners (notifications, analytics, etc.)
- Maintains data integrity with proper foreign key constraints
- Follows SOLID principles and Symfony best practices
- Request ID tracking for debugging and correlation

## Performance & Scalability

### 13. Database Indexing
**Decision:** Strategic database indexes
**Rationale:**
- Query performance
- Scalability
- User experience

**Implementation:** Indexes on foreign keys and unique constraints

### 14. Connection Pooling
**Decision:** Use PostgreSQL connection pooling
**Rationale:**
- Resource efficiency
- Connection reuse
- Better performance under load

## Security

### 15. Input Validation
**Decision:** Multi-layer validation
**Rationale:**
- Security at multiple levels
- Defense in depth
- Data integrity

**Implementation:** Entity validation + DTO validation + API validation

## Trade-offs

### 16. Complexity vs Maintainability
**Trade-off:** Added complexity for better maintainability
**Decision:** Accept complexity for long-term benefits
**Rationale:** Clean architecture pays off in maintenance

### 17. Performance vs Features
**Trade-off:** Some performance overhead for features
**Decision:** Optimize where needed, features first
**Rationale:** Premature optimization is evil

### 18. Flexibility vs Simplicity
**Trade-off:** More flexible but complex design
**Decision:** Flexible design with clear abstractions
**Rationale:** Future extensibility is important

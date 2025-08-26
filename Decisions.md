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

**Commands:**
- `composer phpstan` - Run static analysis
- `composer phpstan:baseline` - Generate baseline for new errors

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

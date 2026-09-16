# Portfolio Manager — Architecture Rules

## Technology Stack
* **Backend:** PHP, Laravel, PostgreSQL, Laravel authentication and authorization, PHPUnit/Pest-compatible Laravel testing.
* **Environment:** Laravel backend runs inside Docker. PostgreSQL is the primary database. SQLite in-memory database is used for feature tests where appropriate.
* **Future Tech:** Do not introduce future technologies (Redis, Queues, Scheduled jobs, Market-data providers, Caching, Notifications, AWS, CI/CD, Frontend app) unless the current feature actually requires them.

## Domain Model
The core domain currently follows:
```
User
→ Portfolio
→ Holdings
→ Transactions
→ CashTransactions
→ PortfolioAllocation
→ Portfolio Performance
```
* **Position Calculations:** Position calculations derive holding state from transaction history.

## Ownership & Authorization
This application is multi-user. Every user-owned resource must be protected.
* A user may access only their own portfolios.
* A portfolio's holdings must respect portfolio ownership.
* A portfolio's transactions must respect portfolio ownership.
* Never trust IDs supplied by the client.
* Authorization must be enforced server-side.
* Existing policies and ownership mechanisms must not be bypassed.
* When adding a new resource, determine its ownership relationship before implementing the API.

## Database Rules
Use PostgreSQL-compatible design.
* Use Laravel migrations for schema changes.
* Define appropriate foreign keys and preserve referential integrity.
* Add indexes where justified.
* Use soft deletes only where the domain requires them.
* Do not modify existing migrations that have already been applied/shared unless explicitly instructed. Create a new migration for schema changes.
* Test important database behavior.
* Never delete or rename existing database fields merely to simplify an implementation.

## API Rules
* API controllers are versioned under App\Http\Controllers\Api\V1\
* New API endpoints must follow the existing V1 convention unless a new API version is explicitly required.
* Follow existing API Resources and response conventions.
* Validate incoming data.
* Authorize the authenticated user.
* Return appropriate HTTP status codes.
* Use Laravel API Resources where the project already uses them.
* Do not expose internal implementation details.
* Keep response structures consistent.
* Add feature tests for successful and unauthorized requests.

## Financial Calculation Rules
Financial calculations are high-risk business logic.
* Do not guess business rules or silently change formulas.
* Do not round values without an explicit requirement.
* Avoid floating-point calculations when precision can affect financial correctness.
* Preserve currency information where applicable.
* Add tests for normal cases and important edge cases.
* Make formulas explicit in code and documentation.
* If a financial rule is ambiguous, stop and ask for clarification rather than inventing a rule.

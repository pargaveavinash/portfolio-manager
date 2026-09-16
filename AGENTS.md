# Portfolio Manager — Agent Instructions

## 1. Project Overview

Portfolio Manager is a personal investment portfolio management application.

The application is being developed incrementally with a strong focus on:

* Correct financial calculations
* Secure multi-user data ownership
* Test-driven development
* Clean architecture
* Maintainable Laravel code
* Explicit business rules
* Reliable automated verification

The long-term goal is to evolve the application into a production-ready portfolio management SaaS platform.

---

## 2. Current Technology Stack

### Backend

* PHP
* Laravel
* PostgreSQL
* Laravel authentication and authorization
* PHPUnit/Pest-compatible Laravel testing
* Docker / Docker Compose

### Development Environment

* Laravel backend runs inside Docker.
* PostgreSQL is the primary development/production database.
* SQLite in-memory database is used for feature tests where appropriate.
* Git is used for version control.

### Future Technology

The application may later introduce:

* Redis
* Queues
* Scheduled jobs
* Market-data providers
* Caching
* Notifications
* AWS infrastructure
* CI/CD
* Frontend application

Do not introduce future technologies unless the current feature actually requires them.

---

# 3. Current Development Status

Completed:

* Repository and infrastructure
* Authentication foundation
* User ownership
* Portfolio CRUD
* Portfolio authorization
* Portfolio soft deletes
* Holding CRUD
* Holding authorization
* Holding soft deletes
* Transaction CRUD
* Transaction authorization
* BUY validation
* SELL validation
* Prevention of selling more than the available quantity
* Holding position calculation
* Current quantity calculation
* Current average cost calculation

Current phase:

**Phase 6 — Portfolio Performance**

Immediate next feature:

**6.1 Current Invested Cost**

Current calculation:

```text
Current Quantity × Current Average Cost
= Current Invested Cost
```

---

# 4. Product Roadmap

The product roadmap is:

```text
Phase 0  Infrastructure          COMPLETE
Phase 1  Authentication          COMPLETE
Phase 2  Portfolio               COMPLETE
Phase 3  Holdings                COMPLETE
Phase 4  Transactions            COMPLETE
Phase 5  Holding Position        COMPLETE
Phase 6  Portfolio Performance   CURRENT
Phase 7  Portfolio Summary
Phase 8  Asset Allocation
Phase 9  Rebalancing Engine
Phase 10 SIP Planner
Phase 11 Market Data
Phase 12 Dashboard
Phase 13 Alerts & Automation
Phase 14 Production Engineering
```

Do not skip ahead to future phases unless explicitly instructed.

---

# 5. Core Development Philosophy

Follow these principles:

1. Understand before modifying.
2. Inspect existing code before creating new code.
3. Reuse existing domain logic where appropriate.
4. Prefer the smallest correct implementation.
5. Avoid speculative abstractions.
6. Do not introduce unnecessary dependencies.
7. Keep business logic explicit and testable.
8. Preserve existing behavior unless the requirement explicitly changes it.
9. Never weaken existing authorization or validation.
10. Every feature must have automated tests.

---

# 6. Agent Workflow

For every non-trivial feature, follow this sequence:

```text
Requirement
    ↓
Understand
    ↓
Inspect existing code
    ↓
Identify affected components
    ↓
Check relevant documentation
    ↓
Create implementation plan
    ↓
Write failing tests
    ↓
Run tests
    ↓
Implement minimum solution
    ↓
Run targeted tests
    ↓
Refactor if necessary
    ↓
Run complete test suite
    ↓
Review changes
    ↓
Update documentation
    ↓
Check git diff
    ↓
Prepare commit
```

Do not skip investigation or testing merely because the change appears simple.

---

# 7. TDD Requirement

Use:

```text
RED
 ↓
GREEN
 ↓
REFACTOR
```

For new business behavior:

1. Create a test describing the expected behavior.
2. Run the test and confirm it fails for the expected reason.
3. Implement the minimum code required.
4. Run the test again.
5. Add relevant edge cases.
6. Run the complete test suite.

Do not write implementation first and tests afterward unless modifying existing behavior where a characterization test is required.

---

# 8. Financial Calculation Rules

Financial calculations are high-risk business logic.

When implementing financial calculations:

* Do not guess business rules.
* Do not silently change formulas.
* Do not round values without an explicit requirement.
* Avoid floating-point calculations when precision can affect financial correctness.
* Preserve currency information where applicable.
* Add tests for normal cases and important edge cases.
* Make formulas explicit in code and documentation.

If a financial rule is ambiguous, stop and ask for clarification rather than inventing a rule.

---

# 9. Domain Model

The core domain currently follows:

```text
User
 │
 └── Portfolio
       │
       ├── Holdings
       │
       └── Transactions
                │
                ├── BUY
                └── SELL
```

Position calculations derive holding state from transaction history.

Concepts already implemented include:

* Current quantity
* Current average cost
* BUY transactions
* SELL transactions
* Soft-deleted transactions being ignored

Future domain concepts include:

```text
Position
Performance
Market Value
Realized P&L
Unrealized P&L
Portfolio Summary
Asset Allocation
Rebalancing
SIP Planning
Market Data
```

Do not implement future concepts prematurely.

---

# 10. Ownership & Authorization

This application is multi-user.

Every user-owned resource must be protected.

Rules:

* A user may access only their own portfolios.
* A portfolio's holdings must respect portfolio ownership.
* A portfolio's transactions must respect portfolio ownership.
* Never trust IDs supplied by the client.
* Authorization must be enforced server-side.
* Existing policies and ownership mechanisms must not be bypassed.

When adding a new resource, determine its ownership relationship before implementing the API.

---

# 11. Database Rules

Use PostgreSQL-compatible design.

When modifying the database:

* Use Laravel migrations.
* Define appropriate foreign keys.
* Add indexes where justified.
* Preserve referential integrity.
* Use soft deletes only where the domain requires them.
* Do not modify existing migrations that have already been applied/shared unless explicitly instructed.
* Create a new migration for schema changes.
* Test important database behavior.

Never delete or rename existing database fields merely to simplify an implementation.

---

# 12. API Rules

Follow the existing API conventions.

For API endpoints:

* Validate incoming data.
* Authorize the authenticated user.
* Return appropriate HTTP status codes.
* Use Laravel API Resources where the project already uses them.
* Do not expose internal implementation details.
* Keep response structures consistent.
* Add feature tests for successful and unauthorized requests.

---

# 13. Testing Rules

Before considering a feature complete:

### Targeted tests

Run the tests directly related to the feature.

### Complete suite

Run the complete application test suite.

### Minimum expectation

There must be:

```text
0 failures
```

Do not claim a feature is complete if tests are failing.

When reporting completion, provide:

* Tests executed
* Number of tests
* Number of assertions
* Failures
* Relevant verification commands

---

# 14. Git Workflow

Do not develop new features directly on `main`.

Use:

```text
main
  ↓
feature/<feature-name>
  ↓
TDD
  ↓
Implementation
  ↓
Tests
  ↓
Full test suite
  ↓
Review
  ↓
Commit
  ↓
Push
```

Before committing:

```bash
git status
git diff --check
```

Do not commit unrelated changes.

Use clear conventional commit messages where appropriate.

Example:

```text
feat: add holding invested cost calculation
```

---

# 15. Change Management

Before modifying existing behavior:

1. Identify the current behavior.
2. Identify existing tests.
3. Determine whether the requirement changes the behavior.
4. Add or modify tests accordingly.
5. Implement the change.
6. Run regression tests.

Never remove tests simply because they conflict with a new implementation.

If an existing test appears incorrect, explain why before changing it.

---

# 16. Agent Safety Rules

The agent must NOT:

* Delete production data.
* Drop databases.
* Reset databases without explicit approval.
* Remove tests to make the suite pass.
* Disable authorization.
* Bypass validation.
* Modify unrelated features.
* Introduce dependencies without justification.
* Rewrite large portions of the application unnecessarily.
* Commit secrets or credentials.
* Commit `.env` files containing secrets.
* Push destructive changes without explicit approval.

For destructive or potentially irreversible operations, ask for confirmation first.

---

# 17. Planning Rules

For non-trivial features, produce a plan before implementation.

The plan should identify:

* Requirement
* Existing code involved
* Files likely to change
* Database changes
* Business rules
* Tests required
* Risks
* Verification steps

Do not create files simply because they seem architecturally interesting.

Prefer modifying the smallest number of files necessary.

---

# 18. Documentation Rules

Documentation is part of the implementation.

When a feature introduces or changes:

* Business rules
* Architecture
* API behavior
* Database structure
* Financial formulas
* Development workflow

update the appropriate documentation.

Documentation must describe the actual implemented behavior, not an imagined future design.

---

# 19. Agentic Development Roadmap

The project is transitioning toward agentic development using Google Antigravity.

The agentic development progression is:

```text
Level 1 — Assisted
Agent plans
    ↓
Human approves
    ↓
Agent implements

Level 2 — Controlled Autonomous
Agent plans
    ↓
Agent implements
    ↓
Agent tests
    ↓
Human reviews

Level 3 — Autonomous
Issue / Requirement
    ↓
Agent plans
    ↓
Agent implements
    ↓
Agent tests
    ↓
Agent reviews
    ↓
Pull Request
    ↓
Human approves merge
```

Start at Level 1.

Increase autonomy only after the agent consistently demonstrates reliable behavior.

---

# 20. Agent Skills

Project-specific skills will be maintained under:

```text
.agents/skills/
```

Planned skills include:

```text
feature-development
tdd
code-review
database-change
api-development
portfolio-domain
```

Use the appropriate skill when available.

Do not duplicate the same instructions across multiple skills unnecessarily.

---

# 21. Agent Rules

Project-specific rules will be maintained under:

```text
.agents/rules/
```

Planned rules include:

```text
project
architecture
laravel
database
api
testing
git
```

Rules should remain focused.

General project behavior belongs here.

Detailed repeatable procedures belong in Skills.

---

# 22. Definition of Done

A feature is considered complete only when:

```text
Requirement understood
        ↓
Plan completed
        ↓
Tests added
        ↓
Implementation completed
        ↓
Targeted tests pass
        ↓
Full test suite passes
        ↓
Authorization verified
        ↓
Edge cases considered
        ↓
Code reviewed
        ↓
Documentation updated when required
        ↓
git diff --check passes
        ↓
Changes are ready for commit
```

"Code written" does not mean "feature complete."

---

# 23. Current Immediate Task

The next feature is:

## Phase 6.1 — Current Invested Cost

Expected business formula:

```text
Current Quantity
×
Current Average Cost
=
Current Invested Cost
```

Before implementing:

1. Inspect the existing Holding position calculation.
2. Inspect current Holding and Transaction models/services.
3. Inspect existing tests.
4. Determine the best location for the new calculation.
5. Create an implementation plan.
6. Write failing tests.
7. Implement the minimum solution.
8. Run targeted tests.
9. Run the complete test suite.
10. Review the final diff.

Do not implement Phase 6.2 or later as part of this task.

---

# 24. Golden Rule

When uncertain:

```text
Do not guess.

Inspect.
Understand.
Plan.
Test.
Implement.
Verify.
```

Correctness is more important than speed.

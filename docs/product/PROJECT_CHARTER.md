# Portfolio Manager — Project Charter

## Project Name
Portfolio Manager

## Purpose
A personal investment portfolio management application.

## Problem Being Solved
Providing a secure, multi-user platform for users to track and manage their investment portfolios with guaranteed financial calculation correctness and strict data ownership.

## Core Product Capabilities
* Multi-user authentication and authorization
* Portfolio creation and management
* Holdings and asset tracking
* Transaction management (BUY/SELL)
* Cash transactions
* Portfolio and holding position calculations
* Asset allocation tracking

## Long-Term Vision
To evolve the application into a production-ready portfolio management Software as a Service (SaaS) platform, featuring comprehensive market data integration, alerts, automation, and advanced portfolio analysis.

## Core Principles
1. Understand before modifying.
2. Inspect existing code before creating new code.
3. Reuse existing domain logic where appropriate.
4. Prefer the smallest correct implementation.
5. Avoid speculative abstractions.
6. Do not introduce unnecessary dependencies.
7. Keep business logic explicit and testable.
8. Preserve existing behavior unless explicitly requested.
9. Never weaken existing authorization or validation.
10. Test-Driven Development (TDD) is required; every feature must have automated tests.

## Financial Correctness Expectations
Financial calculations are treated as high-risk business logic.
* Business rules and formulas must not be guessed; they must be explicit in code and documentation.
* No silent rounding of values unless explicitly required.
* Floating-point calculations must be avoided when precision impacts financial correctness.
* Currency information must be preserved where applicable.
* Comprehensive testing of normal cases, boundary values, zero states, and precision issues is mandatory.

## Security & Ownership Expectations
* The application is strictly multi-user.
* Every user-owned resource is protected; a user may only access their own portfolios, holdings, and transactions.
* Client-supplied IDs are never trusted.
* Authorization must be enforced exclusively server-side.
* Existing policies and ownership mechanisms must not be bypassed.

## High-Level Technology Stack
* **Backend:** PHP, Laravel framework
* **Database:** PostgreSQL (production/development), SQLite in-memory (testing)
* **Testing:** Native PHPUnit
* **Infrastructure:** Docker and Docker Compose

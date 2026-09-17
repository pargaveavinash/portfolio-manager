# Portfolio Manager — Architecture

## System Overview
Portfolio Manager is built as a RESTful JSON API using the Laravel PHP framework. The backend is containerized using Docker, with PostgreSQL serving as the primary database for development and production, and SQLite used in-memory for testing.

## Current Backend Architecture
The application follows standard Laravel MVC (Model-View-Controller) architecture, adapted for API-only responses:
* **Controllers:** Handle HTTP requests and responses.
* **Models:** Represent database entities and encapsulate business/domain logic.
* **Policies:** Enforce authorization and data ownership.
* **API Resources:** Transform models into JSON responses.
* **Migrations:** Manage database schema changes.
* **Tests:** Validate application behavior using feature and unit tests.

## Major Domain Entities
* **User:** The core authenticated entity holding ownership of portfolios.
* **Portfolio:** A container for an individual user's investments.
* **Holding:** A specific asset owned within a portfolio.
* **Transaction:** A record of asset movement (BUY/SELL) against a holding.
* **CashTransaction:** A record of cash movement (deposits/withdrawals) within a portfolio.
* **PortfolioAllocation:** Represents the asset allocation breakdown of a portfolio.
* **MutualFund:** Represents a mutual fund scheme identity master record (AMFI code, AMC, etc.).
* **MutualFundNav:** Represents historical Net Asset Value (NAV) records for a mutual fund.

## Market Data & Valuation Architecture
* **Market Data Providers:** External integration logic (e.g., AMFI) is encapsulated behind `MutualFundDataProviderInterface` to keep external structures from leaking into the domain.
* **Valuation Boundary:** The `MarketDataValuationService` handles the resolution of market prices. The `Holding` model delegates market valuation for supported assets (like `MUTUAL_FUND`) to this service rather than duplicating valuation lookup logic.
* **Synchronization:** A `MutualFundNavSyncService` acts as the boundary for ingesting, validating, and upserting large market-data sets atomically.

## Important Relationships
* `User` has many `Portfolio`s.
* `Portfolio` has many `Holding`s, `Transaction`s, `CashTransaction`s, and `PortfolioAllocation`s.
* `Holding` has many `Transaction`s.
* **Calculation Flow:** Position calculations dynamically derive holding state (current quantity, average cost) directly from the transaction history.

## Ownership and Authorization Model
* **Multi-User Security:** The application strictly enforces that users can only interact with their own resources.
* **Laravel Policies:** Authorization is explicitly defined in policy classes (`PortfolioPolicy`, `HoldingPolicy`, `TransactionPolicy`).
* **Server-Side Enforcement:** Ownership is validated on the server. IDs provided by the client are inherently distrusted and validated against the authenticated user's scope.

## API Architecture
* **Versioning:** Endpoints are versioned (currently `Api\V1`), located in `app/Http/Controllers/Api/V1`.
* **Responses:** Laravel API Resources (`PortfolioResource`, `HoldingResource`, etc.) format the outgoing JSON structure.
* **Validation:** Standard Laravel FormRequests or controller-level validation is used for input sanitization and verification.

## Database Approach
* **PostgreSQL:** Primary RDBMS for storing all domain entities.
* **Migrations:** Strictly version-controlled via Laravel database migrations.
* **Soft Deletes:** Used for `Portfolio` and `Holding` entities to preserve historical integrity while hiding deleted resources from standard queries.
* **Integrity:** Foreign keys are heavily utilized to enforce referential integrity across related entities.

## Testing Architecture
* **Framework:** Native PHPUnit (`Tests\TestCase`).
* **Feature Tests (`tests/Feature/`):** Verify HTTP endpoints, full user flows, database interactions, and authorization mechanisms (e.g., `PortfolioTest`, `HoldingTest`).
* **Unit Tests (`tests/Unit/`):** Isolate and verify complex domain logic and financial calculations (e.g., `HoldingPositionTest`, `PortfolioPerformanceTest`).
* **Database:** Feature tests use an in-memory SQLite database for rapid execution and isolation.

## Important Financial-Domain Considerations
* **Explicit Logic:** Financial formulas are explicitly coded and must not rely on ambiguous defaults.
* **Precision:** Float-related precision loss is avoided.
* **Immutability:** Transaction history is treated as the source of truth for all downstream position and performance calculations.

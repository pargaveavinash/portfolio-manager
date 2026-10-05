# Portfolio Manager — Roadmap

## Implementation Status
*Note: This roadmap reflects the actual repository implementation state as discovered by repository inspection.*
*Product phases do not necessarily have to be implemented in numerical order. The actual repository implementation state determines current development priority. The original roadmap is a planning document, not the source of truth for code state.*

## Current Project Position

### Completed
The following phases have been fully implemented, tested, and merged into the main branch:
* **Phase 0 — Infrastructure**: Docker/Docker Compose, PostgreSQL, PHPUnit/Pest.
* **Phase 1 — Authentication**: User model, Auth controllers.
* **Phase 2 — Portfolio**: Portfolio CRUD, authorization, soft deletes.
* **Phase 3 — Holdings**: Holding CRUD, ownership.
* **Phase 4 — Transactions**: Transaction CRUD, Buy/Sell validation.
* **Phase 5 — Holding Position**: Holding position calculation (Quantity, Avg Cost).
* **Phase 6 — Portfolio Performance**: Invested Cost, Unrealized/Realized P&L, Total P&L.
* **Phase 7 — Portfolio Summary**: Aggregated financial summary endpoint.
* **Phase 8 — Asset Allocation**: Target percentages and portfolio allocation.
* **Phase 9 — Rebalancing Engine**: Suggesting BUY/SELL/HOLD and rebalancing amounts.
* **Cash Management**: Cash deposits, withdrawals, and cash balance calculation.
* **Phase 10 — SIP Planner (MVP Calculation Logic)**: SIP optimization strategy, calculating ideal distribution to minimize allocation drift.
* **SIP Persistence & Scheduling MVP**: Persistent SIP scheduling, Pause/Resume, Next schedule date calculation.
* **Phase 11 — Market Data (Phase 11A Mutual Funds MVP)**: Fetching, storing, and utilizing NAVs from AMFI.
* **Phase 12 — Dashboard**: Dashboard summary, historical trends, and Portfolio Snapshotting.
* **Phase 13 — Alerts & Automation (In-App Alerts MVP)**: In-app/database notifications for threshold breaches, scheduled evaluation jobs.

### Current / Next
* **Phase 14A — Application Caching**: 
  - Portfolio Summary caching implemented (14A.1)
  - Portfolio Performance caching implemented (14A.2)
  - Asset Allocation caching implemented (14A.3)
  - Dashboard caching implemented (14A.4)
  - Remaining caching slices (Rebalancing, etc.) still pending

### Pending
* **Phase 14 — Production Engineering**: Preparing the application for real-world scaling, deployment, and performance.

## Deferred Backlog
These items are intentionally deferred and are NOT considered incomplete bugs. They were explicitly scoped out of their respective MVP phases to focus on core business correctness and to prevent scope creep:
* **External Alert Notifications (Email/SMS/Push)**: Excluded from Phase 13 MVP to focus on business correctness and evaluation idempotency.
* **Phase 11B+ — Generalized Instrument Identity / Stocks / ETFs**: Expanding market data to handle non-Mutual Fund assets.
* **Additional SIP Strategies**: Implementation of `deficit_proportional` and `largest_deficit` allocation strategies.
* **Automatic SIP Execution**: Automatic execution of BUY transactions and automatic cash deduction from `cashBalance()`.
* **Intraday / Real-time Market Data**: Along with FX, fund recommendation, and fund scoring.
* **Other advanced market-data features already documented in the repository**.

## Phase 14 Status
This section reflects the actual repository inspection regarding Production Engineering (Phase 14). Do not automatically make Phase 14 the next implementation task merely because it is the next numbered phase. The roadmap clearly separates the product backlog, production engineering, and deferred enhancements.

* **Caching (Phase 14A)**: Portfolio Summary, Portfolio Performance, Asset Allocation, Dashboard, and Rebalancing caching implemented with model-event based invalidation and portfolio isolation. Remaining slices (if any) pending.
* **Redis**: Configured for Redis Clustered Production Scaling (via `REDIS_CLUSTER_MODE`), incorporating cache tag/key hash-tagging to ensure cluster affinity.
* **Queues**: Application infrastructure implemented (Redis driver). Production worker management implemented (Laravel Horizon installed and configured, explicit authorization gate added, and dedicated Docker worker service defined).
* **Scheduled Jobs**: Already implemented as application infrastructure (`routes/console.php` contains scheduled tasks). No production cron setup yet.
* **Rate Limiting**: Not implemented for production. Relies on default Laravel settings without explicit API hardening.
* **Logging**: Not implemented for production. Uses default Laravel local file logging. No centralized logging setup.
* **Monitoring**: Not implemented. No APM or metrics (like Datadog, Prometheus, or Sentry) configured.
* **Error Handling**: Not implemented for production. Relies on default JSON error handling without customized production formatting/alerts.
* **CI/CD**: Not implemented. No GitHub Actions or CI/CD pipeline definitions exist.
* **Production Docker**: Not implemented. Only development `docker-compose.yml` exists. No `Dockerfile.prod`.
* **AWS Deployment**: Not implemented. No AWS scripts or Terraform.
* **Security Hardening**: Not implemented. No explicit configuration for production security (e.g. strict CORS, WAF).


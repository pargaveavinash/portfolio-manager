# Mutual Fund Market Data Specification (Phase 11A)

## 1. Objective
To introduce market data integration for Indian Mutual Funds, allowing the Portfolio Manager application to automatically fetch, store, and utilize accurate Net Asset Values (NAVs) for portfolio valuation, performance tracking, and asset allocation, using a reliable and abstractable data provider approach.

## 2. Phase 11A Scope
* Establishing the core domain models for Indian Mutual Funds and their NAV history.
* Tracking identity (AMFI code, ISIN, AMC, Scheme name, Plan, Option, Category).
* Fetching and storing NAV data from an external provider into an internal historical timeseries.
* Using AMFI (Association of Mutual Funds in India) as the reference/authoritative source for scheme data.
* Creating an abstraction layer for the market data provider.
* Updating Holding valuation logic to use the fetched Mutual Fund NAV.
* Providing the foundation for Asset Allocation and Rebalancing based on real market values.

## 3. Explicit Out-of-Scope Items
To prevent scope creep, the following are explicitly out of scope for Phase 11A:
* Stocks
* ETFs
* Intraday prices
* Real-time market data
* FX (Foreign Exchange)
* Automatic SIP execution
* Fund recommendation
* Fund switching recommendation
* Fund scoring/ranking
* Portfolio dashboard
* Alerts

## 4. Holding → Mutual Fund Identity
For Phase 11A, we will maintain the existing generic `Holding` model. We will NOT introduce a universal `Instrument` model/table yet, nor will we redesign the `Holding` table unnecessarily.

For Indian Mutual Funds, the domain convention will strictly be:
* `asset_type = 'MUTUAL_FUND'`
* `symbol = AMFI scheme code` (e.g., `120503`)

*Future Note:* Stocks and ETFs may require a more generalized instrument identity model in a future phase (Phase 11B+).

## 5. Source Model
* **External Reference Source:** AMFI is the external reference source for Indian mutual-fund NAV data.
* **Operational Source:** Our PostgreSQL database acts as the operational source used by all internal portfolio calculations after successful ingestion.
* **Provider/Adapter:** The provider serves strictly as a boundary/adapter between the external AMFI data and our domain. Portfolio calculations must never couple directly to AMFI or any third-party API schema.

## 6. Provider Abstraction
We will define a focused provider interface (e.g., `MutualFundDataProviderInterface`) suitable specifically for Phase 11A. It must support:
* Fetching mutual-fund scheme metadata (name, AMC, etc.).
* Fetching latest NAV data.
* Fetching historical NAV data (if needed).

We will NOT design a generic stock/ETF/crypto provider abstraction at this time.

## 7. Sync Architecture
The synchronization of NAVs must **NOT** be exposed as a user-facing API in the MVP. Instead, it will be defined as an internal application service. Conceptually:

```text
MutualFundNavSyncService
    ↓
MutualFundDataProvider
    ↓
Parse
    ↓
Validate
    ↓
Upsert mutual_funds
    ↓
Upsert mutual_fund_navs
```
*(Scheduling/job execution can be added later).*

## 8. Database Design & Constraints
We will define two tables/entities conceptually to separate the master data from the time-series data.

### `mutual_funds`
* `id` (PK)
* `amfi_code` (string, **UNIQUE**)
* `isin` (string, nullable)
* `amc_name` (string)
* `scheme_name` (string)
* `plan_type` (string - 'DIRECT', 'REGULAR')
* `option_type` (string - 'GROWTH', 'IDCW')
* `category` (string)
* `timestamps`

*(Note: `latest_nav` is purposefully excluded from this table to prevent data duplication. The latest NAV is strictly derived from the most recent `nav_date` in the history table).*

### `mutual_fund_navs`
* `id` (PK)
* `mutual_fund_id` (FK to `mutual_funds`, cascade on delete)
* `nav` (decimal 20,6)
* `nav_date` (date)
* `source` (string, e.g., 'AMFI')
* `fetched_at` (timestamp)
* `timestamps`

**Required Constraints & Indexes:**
* `UNIQUE(mutual_fund_id, nav_date)` to prevent duplicate entries for the same day.
* Appropriate indexes for latest NAV lookup (e.g., index on `mutual_fund_id` and `nav_date` DESC) and historical date lookups.

## 9. Current vs. Historical NAV & Stale Handling
Historical NAV must be persisted independently of the current/latest NAV to support accurate point-in-time valuation.

### Current Valuation
* Use the **latest available NAV**.
* **Stale NAV Behavior:** A temporarily missing new NAV (e.g., weekend, holiday, delay) may use the most recent available NAV for current valuation/display.
* The response/domain model must make the `nav_date` available so consumers (API/UI) can determine exactly how current the valuation is.

### Historical Valuation
* **Applicable NAV** = the most recent NAV where `nav_date <= requested valuation date`. This correctly handles weekends and market holidays.
* *Example:* If the requested valuation date is Sunday and the latest available NAV is Friday, use Friday's NAV. Do NOT require an exact NAV record on the requested calendar date.
* Historical calculations must **never** silently substitute today's latest NAV for a past date.

## 10. NAV Precision
* **Database Precision:** Use `decimal(20, 6)` for storing NAVs.
* **No Premature Rounding:** Do not round incoming NAVs from the provider prematurely.
* **Calculations:** Perform all intermediate financial calculations using the stored database precision.
* **Output:** Round only at the appropriate financial/API output boundary.

## 11. Holding Valuation
The existing transaction/position calculation rules remain unchanged. Only the market value resolution changes.

* **Formula:** `current_quantity × applicable NAV = current market value`
* For current valuation, "applicable NAV" strictly means the latest available NAV from `mutual_fund_navs`.

## 12. Existing Feature Integration
The NAV-derived market value will eventually feed into existing modules:
* **Holding Position / Valuation:** Derives current real-world value automatically.
* **Portfolio Performance:** Calculates Unrealized P&L using actual market values.
* **Asset Allocation:** Uses the `mutual_funds.category` to group holdings.
* **Rebalancing:** Uses the true market value of the mutual funds to calculate actual allocation weights vs target weights.
* **SIP Planner:** Uses the master data to project future states based on historical/current NAVs.
*(Note: These features will integrate the new NAV data, but their underlying calculation logic must not change during Phase 11A discovery).*

## 13. API Scope
Define only the APIs genuinely required for Phase 11A:
* **Allowed:** A user-facing `GET /api/v1/mutual-funds/search` API to allow users to look up AMFI schemes when adding a holding.
* **Prohibited:** Public or user-facing Sync APIs. The sync operation must remain an internal service/job.

## 14. Acceptance Criteria
* **Master Creation/Update:** The system must successfully create or update `mutual_funds` records from the provider metadata without duplicating records.
* **AMFI Code Identity:** The system must strictly uniquely identify funds using the AMFI code.
* **NAV Ingestion:** The system must successfully parse and ingest NAV records into `mutual_fund_navs`.
* **NAV History:** The system must retain full ingested history in `mutual_fund_navs`, never overwriting past days with new data.
* **Duplicate Prevention:** The database and application must prevent duplicate NAV entries for the same `(mutual_fund_id, nav_date)`.
* **Latest NAV Retrieval:** The system must be able to efficiently query the most recent NAV for a given mutual fund.
* **Historical NAV Retrieval:** The system must be able to fetch the applicable NAV for a specific historical `nav_date`.
* **Stale NAV Handling:** For current valuation, the system must fallback to the most recent known NAV if today's NAV is missing, while exposing the `nav_date` so clients are aware of the staleness.
* **Holding Valuation:** `Holding::currentMarketValue()` must calculate accurately using `current_quantity * latest_nav` when `asset_type = 'MUTUAL_FUND'`.
* **Authorization:** User-facing search APIs must enforce standard application authorization.
* **Provider Failure Handling:** The sync service must gracefully catch timeouts, 500s, or unreachable hosts without crashing the application or wiping existing data.
* **Invalid Provider Data:** The sync service must handle malformed provider lines (e.g., missing NAV, unexpected characters) by skipping or logging the invalid row without failing the entire batch.
* **Financial Precision:** NAV values must use fixed-point decimal arithmetic with defined storage precision decimal(20,6). Incoming values must not be prematurely rounded, and floating-point arithmetic must not be used for financial calculations. Output rounding must occur only at the defined financial/API boundary.
* **Regression Safety:** Full application test suite must pass with 0 failures, ensuring existing transaction/holding logic is uncompromised.

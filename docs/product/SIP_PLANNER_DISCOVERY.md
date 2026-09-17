# SIP Planner — Discovery & Specification Proposal

## 1. Approved MVP Requirements
* **Calculation-Only Tool:** The SIP Planner is purely a stateless planning/calculation tool. It will not deduct cash, create BUY transactions, or store plans persistently.
* **Explicit SIP Amount:** The SIP amount must be provided explicitly by the user, must be strictly greater than 0, and has no automatic fallback to `cashBalance()`.
* **Allocation Dependency:** The portfolio must have target allocations defined, and they must sum to exactly 100%.
* **Strategy `optimization` Only:** The MVP will only implement the mathematical post-contribution optimization strategy.

## 2. Future Strategy Architecture
The SIP Planner must be built with a clean extensibility concept to support multiple allocation strategies in the future.
* **Extensibility Concept:** We will implement the Strategy Design Pattern. The core `SipPlanner` service will accept a `SipAllocationStrategy` interface.
* **Supported Strategies:**
  * **`optimization` (MVP Strategy):** Mathematical Post-Contribution Optimization. Allocates funds such that the post-contribution portfolio is as close to mathematical optimality as possible without selling.
  * **`deficit_proportional` (Future):** Allocates simply based on the current proportional deficit, ignoring post-contribution totals.
  * **`largest_deficit` (Future):** Allocates funds sequentially to the single most underweight asset until it matches the next most underweight, and so on (waterfall approach).

## 3. Business Rules
* The portfolio must have a valid allocation (`hasValidAllocation() === true`).
* The requested SIP amount must be > 0.
* The tool strictly suggests BUY actions (positive allocation amounts). It will never suggest a negative amount (SELL).
* Total suggested SIP amounts must equal the requested SIP amount exactly.
* Output amounts must be rounded to 2 decimal places.

## 4. Financial Formulas (Optimization Strategy)
The `optimization` strategy calculates the ideal distribution to minimize allocation drift.

1. **Calculate Post-SIP Portfolio Value:**
   `New Portfolio Value = Current Total Market Value + SIP Amount`
2. **Calculate Ideal Target Value per Asset:**
   `Target Asset Value = New Portfolio Value * (Asset Target Percentage / 100)`
3. **Calculate Initial Deficit (Ideal Contribution):**
   `Deficit = Target Asset Value - Current Asset Market Value`
4. **Enforce BUY-only Constraint:**
   If `Deficit < 0` (asset is already overweight), set `Deficit = 0`.
5. **Normalize Contributions:**
   Because overweight assets were capped at 0, the sum of all remaining positive deficits may exceed the actual SIP amount.
   `Suggested Amount = (Asset Deficit / Sum of All Positive Deficits) * Total SIP Amount`
6. **Rounding & Remainder Management:**
   * Round each `Suggested Amount` to 2 decimal places.
   * Calculate the `Remainder = Total SIP Amount - Sum of Rounded Suggested Amounts`.
   * Identify the asset with the **largest remaining allocation deficit** (the highest proportional deviation from its target).
   * **Tie-Breaker:** If multiple assets share the exact same deficit, use the asset symbol in ascending alphabetical order.
   * Assign the `Remainder` to that specific asset.

## 5. API Proposal
* **Endpoint:** `GET /api/v1/portfolios/{portfolio}/sip-plan`
* **Query Parameters:**
  * `amount` (numeric, required, > 0)
  * `strategy` (string, optional, defaults to `optimization`)
* **Response:** `200 OK`
  ```json
  {
    "data": {
      "sip_amount": 20000.00,
      "strategy": "optimization",
      "plan": [
        {
          "symbol": "AAPL",
          "suggested_amount": 12000.00
        },
        {
          "symbol": "MSFT",
          "suggested_amount": 8000.00
        }
      ]
    }
  }
  ```

## 6. Data Model
* **No new tables or models are needed.**
* Logic will rely on existing `Portfolio`, `Holding`, and `PortfolioAllocation` models.
* The implementation will introduce a new strategy-based architecture within the application domain (e.g., `Contracts\SipAllocationStrategy` and `Strategies\OptimizationStrategy`).

## 7. Edge Cases
* **Empty Portfolio:** If current market value is 0, the math naturally falls back to distributing the SIP exactly by the target percentages.
* **Extremely Overweight Asset:** An asset's value is so high that its post-SIP target value is still less than its current value. Its deficit is set to 0, and the entire SIP amount is proportionally distributed among the other underweight assets.
* **Rounding Pennies & Ties:** Handled deterministically by applying the remainder to the asset with the largest remaining allocation deficit, falling back to ascending symbol ordering.

## 8. Error Handling
* `403 Forbidden` for unauthorized portfolio access.
* `422 Unprocessable Entity` if the portfolio lacks 100% target allocation.
* `422 Unprocessable Entity` if `strategy` is provided but is not `optimization` (as `deficit_proportional` and `largest_deficit` are not implemented in MVP).

## 9. Out of Scope
* Implementation of `deficit_proportional` and `largest_deficit` strategies.
* Automatic execution of BUY transactions.
* Persistent storage of SIP plans in the database.
* Scheduled or automatic SIP execution (cron jobs).
* Automatic cash deduction from `cashBalance()`.

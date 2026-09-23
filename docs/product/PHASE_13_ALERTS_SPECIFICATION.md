# Phase 13 — Alerts & Automation Specification

## 1. Objective
Create a minimal but extensible Alert Engine MVP that notifies users when important portfolio events occur, specifically focusing on allocation deviations, market price/NAV thresholds, and SIP reminders.

## 2. Scope
The Phase 13 MVP restricts alert delivery exclusively to in-app/database notifications. Real-time evaluation and external delivery mechanisms (email, SMS, push) are explicitly excluded to focus on business correctness and evaluation idempotency.

## 3. Alert Types
1. **Allocation Deviation Alert:** Triggers when an asset's allocation deviates from its target beyond the existing portfolio rebalancing tolerance (currently defined as 5.0% in `Portfolio->rebalancingAction()`).
2. **Market Price / NAV Threshold Alert:** Triggers when the price or NAV of a specific holding crosses above or below a user-defined threshold.
3. **SIP Reminder Alert:** Generates a reminder when a configured SIP is due. *(Note: This remains at the conceptual integration level pending resolution of SIP persistence — see Section 18)*.

## 4. Alert Rule Model
An `AlertRule` represents the user's configuration detailing *when* an alert should trigger.
Fields conceptually include:
- `user_id`
- `type` (ALLOCATION, PRICE_ABOVE, PRICE_BELOW, SIP_REMINDER)
- `reference_id` (Portfolio ID for allocation/SIP, Holding ID or Symbol for price)
- `threshold_value` (Decimal for price thresholds; nullable for logic-driven alerts like allocation/SIP)
- `is_active` (boolean)

## 5. Notification/Event Model
An `AlertNotification` (or Event) is the actual generated record when an `AlertRule` condition is met.
Fields conceptually include:
- `alert_rule_id`
- `user_id`
- `message` (Text detailing the event, e.g., "Reliance NAV crossed 1500")
- `triggered_at` (Timestamp)
- `is_read` (boolean)
- `resolved_at` (Nullable timestamp, if applicable)

## 6. Trigger Conditions
- **Allocation:** Reuses `Portfolio->rebalancingAction(symbol)`. If the action evaluates to `BUY` or `SELL` (meaning deviation > 5.0%), the condition is met.
- **Price/NAV:** Fetches `currentMarketPrice()` (or NAV via `MarketDataValuationService`). Meets condition if `current >= threshold` (for PRICE_ABOVE) or `current <= threshold` (for PRICE_BELOW).
- **SIP Reminder:** Dependent on persisted SIP scheduling. Currently kept at a conceptual integration level until a persistent SIP scheduling model is implemented.

## 7. Duplicate/Idempotency Rules
To prevent notification spam, the system uses **state-transition based triggering**, NOT a time-based cooldown:
- **Allocation & Price/NAV Alerts:**
  - Condition transitions from false → true: Create one notification.
  - Condition remains true: Do NOT create duplicate notifications.
  - Condition transitions from true → false: Reset the alert condition.
  - If it later transitions false → true again: Create a new notification.
  - The implementation must be deterministic and idempotent without relying on arbitrary time-based cooldowns.
- **SIP Alerts:** Evaluation conceptually depends on persistent SIP scheduling (see Section 18).

## 8. Evaluation Schedule
- Evaluation occurs via scheduled background jobs (e.g., Laravel Scheduler).
- Initial frequency is **Daily**.
- **Execution Boundary:** Evaluation of price/NAV and allocation alerts MUST execute *after* the daily market data (NAV synchronization) process completes to ensure alerts act on fresh data.

## 9. Missing Data Rules
- **Missing NAV / Unavailable Price:** The evaluation skips the rule for that cycle without failing. It does *not* treat the missing value as zero.
- **Deleted Holding / Portfolio:** If the reference entity is soft-deleted or permanently deleted, the associated `AlertRule` should be automatically marked inactive or ignored during evaluation.
- **Inactive Alert:** Skipped immediately during evaluation.
- **Missing SIP Configuration:** Evaluation skips the SIP alert rule.

## 10. Precision Rules
- All threshold comparisons must utilize `bcmath` or identical float-comparison precision rules already established in the application.
- No arbitrary rounding is permitted before comparison (e.g., if threshold is 10.50, and NAV is 10.499999, it has not crossed above 10.50).

## 11. Authorization Rules
- Users can only create, view, update, enable, disable, and delete `AlertRule`s that belong to them.
- Users can only view and read `AlertNotification`s that belong to them.
- When creating an `AlertRule` tied to a specific `Portfolio` or `Holding`, the system must strictly verify that the user owns the referenced entity.

## 12. API Specification
All endpoints are authenticated and strictly authorized. Prefix: `/api/v1/alerts`

- `GET /rules` - List user's alert rules.
- `POST /rules` - Create a new alert rule.
  - *Validation:* type, reference_id, threshold_value. Ensures ownership of references.
- `GET /rules/{rule}` - View specific rule details.
- `PUT /rules/{rule}` - Update a rule (e.g., change threshold).
- `PATCH /rules/{rule}/toggle` - Enable or disable an alert rule.
- `DELETE /rules/{rule}` - Delete a rule.
- `GET /notifications` - List user's triggered notifications (supports filtering by `is_read`).
- `PATCH /notifications/{notification}/read` - Mark a notification as read.

## 13. Database Model
**Table: `alert_rules`**
- `id` (PK)
- `user_id` (FK to users)
- `type` (enum)
- `reference_type` (polymorphic or explicit: e.g., 'portfolio', 'holding', 'symbol')
- `reference_id` (ID/String)
- `threshold_value` (decimal, nullable)
- `is_active` (boolean, default true)
- `last_evaluated_state` (boolean, default false - used for state-transition idempotency)
- `timestamps` & `softDeletes`

**Table: `alert_notifications`**
- `id` (PK)
- `alert_rule_id` (FK to alert_rules)
- `user_id` (FK to users)
- `message` (string)
- `is_read` (boolean, default false)
- `triggered_at` (timestamp)
- `timestamps`

## 14. Architecture
Follows standard MVC + Service layered architecture:
`Api/V1/AlertRuleController` → handles CRUD for rules.
`Api/V1/AlertNotificationController` → handles viewing/reading notifications.
`Console/Commands/EvaluateAlertsCommand` → Triggered by Scheduler.
`Services/AlertEngineService` → Dispatches evaluation.
`Services/Evaluators/*` → Specific evaluators (e.g., `AllocationEvaluator`) that consume existing domain models (`Portfolio->rebalancingAction()`, `MarketDataValuationService`) and write to `AlertNotification`.

## 15. Test Requirements
**Alert Rules:**
- CRUD operations succeed for owner.
- Unauthorized access fails (403/404).
- Creating a rule for another user's portfolio fails.

**Allocation Alert:**
- Rule triggers when `rebalancingAction()` returns BUY/SELL.
- Rule does not trigger when `rebalancingAction()` returns HOLD.

**Price/NAV Alert:**
- Triggers correctly when crossing above/below.
- Skips evaluation when NAV is missing (no false positives).

**SIP Alert:**
- Triggers when SIP is due.
- Does not trigger when SIP is not due.

**Notifications & Scheduler:**
- Duplicate prevention (idempotency) behaves correctly over multiple scheduler runs.
- Inactive rules are ignored.

## 16. Out of Scope
The Phase 13 MVP explicitly excludes:
- Email, SMS, Mobile push, WhatsApp, or real-time WebSocket deliveries.
- AI-generated financial recommendations.
- Buy/sell recommendations beyond existing rebalancing logic.
- Automatic portfolio rebalancing or SIP execution.
- New portfolio performance or allocation formulas.
- New market-data providers.

## 17. Definition of Done
- `alert_rules` and `alert_notifications` tables created via migrations.
- API endpoints implemented and documented.
- `AlertEngineService` and daily Scheduler command implemented.
- Evaluators reuse existing financial logic without duplication.
- Targeted tests (Rule CRUD, Evaluators, Idempotency, Scheduler) pass.
- Full test suite passes with 0 failures.
- Financial calculation boundaries strictly respected.
- Specification verified against implementation.

## 18. Open Product Decisions
*(None currently blocking Phase 13 MVP - Prior decisions resolved).*

### Resolved Decisions
1. **Continuous Alert Idempotency:** Resolved. We will use **state-transition based triggering** (false → true) rather than any time-based cooldowns to evaluate idempotency.
2. **SIP Persistence:** Resolved. Persistent SIP scheduling does NOT currently exist in the database (only a dynamic calculation via `SipPlannerService`). We will NOT invent a new SIP persistence model in Phase 13. SIP Reminder evaluation conceptually depends on persisted SIP scheduling, and SIP persistence is identified as a prerequisite product decision for full SIP Alert implementation. Phase 13 scope is not expanded to build SIP persistence.

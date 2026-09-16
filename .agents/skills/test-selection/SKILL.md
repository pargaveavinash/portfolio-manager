---
name: test-selection
description: Reusable procedure for selecting and mapping the correct tests for an approved feature before and during TDD execution.
---

# Test Selection & Test Mapping

## 1. Purpose
This skill provides a reusable procedure for selecting and mapping the correct tests for an approved feature before and during Test-Driven Development (TDD) execution. It teaches the agent how to translate Acceptance Criteria into executable tests by defining the Observable Behavior, Test Type, Test Location, Scenario, and Expected Result.

## 2. Preconditions
* This procedure must start from an **Approved Feature Specification** and an **Approved Implementation Plan**.
* Do not invent expected values, business rules, or test infrastructure.
* Do not write test implementation code during the mapping phase.
* Do not modify existing tests during the mapping phase.
* Do not stage, commit, or push.

## 3. Required Inputs
To begin Test Selection, you must load:
1. The Approved Feature Specification (including Acceptance Criteria and Edge Cases).
2. The relevant Business Rules.
3. The Approved Implementation Plan.

## 4. Repository Test Inspection
* Inspect the actual repository's existing tests before proposing new tests.
* Understand the current native PHPUnit conventions, `Tests\TestCase` structure, and existing factory/fixture usage.
* Do not introduce unnecessary fixtures or helper abstractions.

## 5. Acceptance Criterion Classification
A single Acceptance Criterion may require multiple tests when it contains multiple observable behaviors or security/financial boundaries. Classify the behaviors to be tested:
1. **Happy path:** The normal, expected successful outcome.
2. **Validation failure:** Rejection of invalid input data.
3. **Authorization failure:** Rejection of unauthenticated or unauthorized requests.
4. **Ownership isolation:** Rejection of cross-user data access.
5. **Boundary condition:** Behavior at the edges of acceptable input ranges.
6. **Zero/null state:** Behavior when data is empty, null, or zero.
7. **Financial precision:** Correct calculation, decimal handling, and explicit rounding (if specified).
8. **Regression behavior:** Ensuring existing functionality is not broken.

## 6. Test Type Selection
Distinguish between Feature tests and Unit tests:
* **Feature Tests:** Prefer Feature tests for API endpoints, authentication/authorization, ownership boundaries, validation behavior, and externally observable workflows (e.g., database persistence).
* **Unit Tests:** Use Unit tests where isolated domain/business calculations warrant them (e.g., complex financial math, isolated domain services). Do not create Unit tests merely to duplicate Feature tests.

## 7. Test Location Selection
* **Feature Tests:** Must be placed in `tests/Feature/` (e.g., `tests/Feature/Api/V1/PortfolioTest.php`).
* **Unit Tests:** Must be placed in `tests/Unit/` (e.g., `tests/Unit/Domain/HoldingPositionTest.php`).

## 8. Acceptance Criterion → Test Mapping
Every Acceptance Criterion must be mapped to at least one appropriate test. Use the following mapping model:

| AC | Observable Behavior | Test Type | Test Location | Scenario | Expected Result |
|----|---------------------|-----------|---------------|----------|-----------------|
| AC-1 | Creating a portfolio succeeds | Feature | `tests/Feature/PortfolioTest` | Valid payload | 201 Created & DB record |
| AC-1 | Cannot access other user portfolio | Feature | `tests/Feature/PortfolioTest` | Another user's ID | 403 Forbidden |

## 9. Business Rule → Test Mapping
Every Business Rule that has observable behavior must have explicit test coverage mapped in the same way.

## 10. Authorization / Security Test Selection
Authorization-sensitive features must include cross-user isolation tests where applicable. Explicitly define tests that prove a user cannot view, edit, or delete another user's resources.

## 11. Financial Test Selection
Financial calculations must have explicit tests for:
* Normal expected values
* Zero values
* Boundary values
* Precision and decimal behavior
* Explicit rounding behavior (where specified in the rules)

## 12. Validation / Error Test Selection
Validation rules must include rejection/error scenarios, specifying the expected HTTP status code (e.g., 422 Unprocessable Entity) and error structure.

## 13. Edge Case Test Selection
Edge cases identified in the specification or implementation plan must be explicitly mapped to tests.

## 14. Regression Test Selection
Existing regression-sensitive behavior must be considered. Identify any existing tests that cover related areas to ensure they are not inadvertently broken.

## 15. Test Coverage Gaps
Identify coverage gaps explicitly rather than silently ignoring them. If an Acceptance Criterion cannot be meaningfully tested, mark it as a gap and explain why. If requirements are ambiguous, STOP and request clarification.

## 16. Test Naming
Test names must clearly describe behavior rather than implementation details (e.g., `test_user_can_view_own_portfolio` instead of `test_portfolio_controller_index`).

## 17. Test Independence
Tests must be independent and deterministic. Do not rely on test execution order. State setup must be contained within each test or standard `setUp` methods.

## 18. Test Data / Fixtures
Use appropriate factories/fixtures already present in the repository (e.g., `User::factory()->create()`). Do not invent new factory structures unless explicitly required and approved.

## 19. Mapping Evidence
Provide the complete mapping table (as defined in section 8) as evidence of the test selection process.

## 20. Completion Criteria
The mapping phase is complete when the following checklist is satisfied:

- [ ] Repository tests inspected
- [ ] Every Acceptance Criterion mapped
- [ ] Relevant Business Rules mapped
- [ ] Feature vs Unit classification completed
- [ ] Authorization/security tests identified
- [ ] Financial tests identified where applicable
- [ ] Validation/error tests identified
- [ ] Edge cases mapped
- [ ] Regression coverage considered
- [ ] Test gaps documented
- [ ] Test names defined
- [ ] Existing fixtures/factories identified
- [ ] No unnecessary test duplication
- [ ] No implementation code written
- [ ] No requirements invented or changed

### Explicit Anti-Patterns to Prevent
The skill mapping must explicitly prevent:
* **Test duplication:** Do not write a Unit test and Feature test that test the exact same observable boundary.
* **Testing implementation details unnecessarily:** Test public observable behavior, not private methods.
* **Weak assertions:** Do not simply assert `true`. Assert specific database states, response codes, or calculated values.
* **Overly broad tests:** A single test should ideally assert one behavior.
* **Tests that pass without actually proving the Acceptance Criterion:** Ensure the setup actually triggers the code under test.
* **Modifying requirements to fit existing tests:** Tests serve the specification, not the other way around.

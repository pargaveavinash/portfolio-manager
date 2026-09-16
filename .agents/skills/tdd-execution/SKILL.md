---
name: tdd-execution
description: Reusable procedure for executing TDD for an approved feature in the Portfolio Manager project.
---

# TDD Execution Protocol

## 1. Purpose
This skill defines the reusable procedure an agent must follow when executing Test-Driven Development (TDD) for an approved feature in the Portfolio Manager project. It establishes a disciplined RED → GREEN → REFACTOR execution loop based on the Approved Feature Specification, Business Rules, Acceptance Criteria, and the Approved Implementation Plan.

## 2. Preconditions
* TDD execution must start only after the Implementation Plan is Approved by a human.
* The agent must read the approved specification, business rules, acceptance criteria, and implementation plan before writing any tests.

## 3. Required Inputs
To begin TDD Execution, you must load:
* Approved Feature Specification
* Relevant Business Rules
* Relevant Acceptance Criteria
* Approved Implementation Plan

## 4. Repository Inspection
* The agent must inspect the existing repository before modifying tests or code.
* Understand the current testing structure, naming conventions, and setup in `tests/Feature` and `tests/Unit`.
* Verify existing models, controllers, and services relevant to the feature.

## 5. Acceptance Criteria to Test Mapping
* Map every Acceptance Criterion to one or more specific tests.
* Ensure no Acceptance Criterion is left untested.

## 6. Test Selection
* Follow existing repository testing conventions.
* Use native PHPUnit as established by the repository.
* Prefer **Feature tests** for externally observable API behavior, user flows, and endpoint authorization.
* Use **Unit tests** where isolated business logic (e.g., complex financial calculations) warrants them.

## 7. RED Phase
* Write the smallest meaningful failing test first for the mapped Acceptance Criterion.
* Do not write application implementation code before the test.
* Ensure the test focuses on the specific missing behavior.

## 8. RED Failure Verification
* Run the targeted test.
* Confirm that the test fails.
* Verify the failure is for the expected reason (e.g., missing class, method, or assertion failure), not a syntax error in the test itself.
* Never weaken a test just to make it pass.

## 9. GREEN Phase
* Implement the minimum production code required to make the test pass.
* Do not introduce speculative architecture, unrequested features, or unnecessary dependencies.
* Do not redesign the architecture during this phase.
* Preserve authorization, ownership boundaries, and financial precision.

## 10. GREEN Verification
* Run the targeted test again.
* Confirm the test passes (GREEN).
* Ensure you are testing the actual implementation.

## 11. REFACTOR Phase
* Refactor only after the relevant tests pass.
* Clean up the implementation to improve quality, readability, and adherence to project standards without changing externally observable behavior.
* Re-run tests after refactoring to ensure behavior is preserved.
* Repeat the RED → GREEN → REFACTOR cycle incrementally for additional acceptance criteria.

## 12. Edge Case Tests
* Add explicit edge-case tests where required (e.g., boundaries, nulls, missing data, zero-states).
* Follow the RED → GREEN → REFACTOR loop for each edge case.

## 13. Authorization / Security Tests
* Add ownership and cross-user authorization tests where applicable.
* Ensure tests verify that users cannot access other users' portfolios, holdings, or transactions.

## 14. Financial Precision Tests
* Add tests specifically for financial precision, rounding, zero-state, and boundary values where applicable.
* Validate that formulas execute exactly as described in the Business Rules.

## 15. Regression Testing
* Run the complete application test suite after the feature's targeted tests pass.
* Ensure 0 failures across the suite.

## 16. Failure Handling
* **Expected RED failure:** Proceed to the GREEN phase.
* **Unexpected RED failure:** Fix the test setup or syntax. Ensure the test fails because the feature logic is missing.
* **Test infrastructure failure:** Diagnose and fix the test environment. Do not change application logic.
* **Implementation failure:** Re-evaluate the minimum implementation. Check for boundary issues or logic errors.
* **Regression failure:** Never delete or weaken an existing test to solve a failure. Inspect the regression. If the regression indicates a flaw in the new implementation, fix the new implementation. If an existing test contradicts the approved specification, do not silently rewrite it; STOP and report the conflict.
* **Ambiguous requirement:** If a specification or business rule is ambiguous, STOP and request clarification. Do not invent rules.
* **Business rule conflict:** STOP and report the conflict for human review.
* **Scope deviation:** If implementation requires a change outside the approved Implementation Plan, STOP and report the deviation before proceeding unless it is clearly a mechanical consequence already covered by the plan.

## 17. Test Evidence
For each TDD cycle, you must record and present:
* The test command executed (e.g., `php artisan test --filter=ExampleTest`).
* The failure result and reason for failure during RED.
* The implementation change made.
* The test result during GREEN.
* The refactoring result.
* Relevant final test output showing test/assertion counts and pass status.

## 18. TDD Completion Criteria
* All mapped Acceptance Criteria have corresponding tests.
* Required Business Rules are covered by tests.
* Targeted tests pass (0 failures).
* Edge cases are covered where applicable.
* Authorization/security tests pass where applicable.
* Financial correctness tests pass where applicable.
* Complete test suite passes (0 failures).
* No tests were weakened or removed.
* No unresolved specification ambiguity remains.
* No unapproved scope deviation remains.
* Verification evidence is recorded and reported.

## 19. Human Approval Boundary
* Do not stage, commit, or push your changes automatically.
* Present the TDD Completion Criteria and Test Evidence.
* Human approval remains required before commit/push.

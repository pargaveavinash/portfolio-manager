---
name: incremental-tdd
description: Reusable procedure for executing TDD incrementally, one logically isolated Acceptance Criterion or small related group of Acceptance Criteria at a time.
---

# Incremental TDD Loop

## 1. Purpose
This skill establishes a reusable procedure for executing TDD incrementally—handling one logically isolated Acceptance Criterion (or a small, tightly coupled group) at a time. The objective is to prevent large uncontrolled agentic changes, ensuring that every TDD cycle is independently understandable, testable, and recoverable.

## 2. Preconditions
* Start only from an Approved Implementation Plan.
* Complete and verify one cycle before beginning the next.
* Do not accumulate large unverified changes.
* Do not hide failures by weakening tests.
* Do not change requirements to make the cycle easier.
* Do not introduce speculative architecture.
* Do not make unrelated refactors.
* Do not expand scope without explicit approval.
* Preserve repository architecture and conventions.
* Preserve authorization and ownership boundaries.
* Preserve financial correctness and precision.

## 3. Required Inputs
* The approved Feature Specification.
* The relevant Business Rules.
* The Acceptance Criteria.
* The Approved Implementation Plan.

## 4. TDD Unit of Work
Each cycle represents a single unit of work defined by:
* **Selected Acceptance Criterion(s)**
* **Related Business Rule(s)**
* **Observable behavior**
* **Required tests** (Identified using the `test-selection` skill)
* **Expected RED condition**
* **Minimal implementation**
* **Expected GREEN condition**
* **Refactoring opportunity**
* **Verification commands**
* **Completion state**

## 5. Acceptance Criterion Selection
* Prefer one Acceptance Criterion per TDD cycle when practical.
* Allow a small group of Acceptance Criteria in one cycle only when they are tightly coupled and cannot be meaningfully implemented independently. Explicitly record why they are grouped.
* Do not implement unrelated Acceptance Criteria in the same cycle.

## 6. Dependency Ordering
Order the Acceptance Criteria logically based on feature dependencies. Examples of ordering logic:
1. Data model / persistence prerequisite
2. Domain/business logic
3. API behavior
4. Authorization boundaries
5. Edge cases
6. Aggregation/reporting behavior

Do not assume this exact ordering is mandatory for every feature; inspect the actual dependencies.

## 7. RED Cycle
* Use the `tdd-execution` skill to write the failing test.
* Ensure the failure corresponds exactly to the missing required behavior.

## 8. GREEN Cycle
* Implement the minimum code required to make the test pass.
* If the implementation requires a change outside the approved Implementation Plan, stop and report the deviation unless it is a clear mechanical consequence already covered.

## 9. REFACTOR Cycle
* Perform refactoring only after the cycle is GREEN.
* Re-run the cycle tests after refactoring to ensure nothing broke.

## 10. Cycle Verification
* After each cycle, run the relevant targeted tests.
* Do not proceed to the next cycle if the current cycle has unresolved failures.

## 11. Next-Cycle Selection
* Evaluate remaining un-implemented Acceptance Criteria and pick the next logical unit of work.

## 12. Failure Isolation
* Use the `failure-analysis` skill whenever a test or verification step fails unexpectedly.
* Do not proceed until the failure is fully analyzed and resolved.

## 13. Scope Control
* Ensure the changes align with the Approved Implementation Plan.
* Do not expand scope.

## 14. Shared Code Changes
* If multiple Acceptance Criteria require the same production code, identify the shared component.
* Implement only the minimum shared foundation necessary for the current cycle.
* Do not prematurely implement behavior belonging to later Acceptance Criteria.
* Ensure the current cycle still has a meaningful failing test before implementation.

## 15. Database / Migration Changes
* If a cycle requires a migration, identify it before implementation.
* Do not modify existing applied migrations.
* Verify migration safety and relationships.
* Run relevant database tests.
* Do not make unrelated schema changes.

## 16. Authorization / Security Changes
* Include authorization tests in the appropriate cycle.
* Verify authenticated access.
* Verify ownership.
* Verify cross-user isolation where applicable.
* Do not defer critical authorization behavior until after feature completion.

## 17. Financial Calculation Changes
* Introduce financial formulas incrementally.
* Verify normal, zero, boundary, precision, and rounding cases where applicable.
* Do not silently change expected financial values.
* If financial behavior is ambiguous, stop for clarification.

## 18. Regression Checks
* After each meaningful cycle, run the targeted feature tests.
* Run related existing tests when shared code is changed.
* Run the complete test suite after all cycles are complete.
* If a regression occurs, invoke `failure-analysis` before making further changes.

## 19. Cycle Evidence
Generate a TDD Cycle Record at the end of each cycle.

### TDD Cycle Record

#### Cycle
[Cycle number]

#### Acceptance Criteria
[AC IDs]

#### Business Rules
[BR IDs]

#### Tests
[Test names/locations]

#### RED
[Command + expected failure]

#### GREEN
[Implementation summary + command/result]

#### REFACTOR
[Changes + command/result]

#### Regression Check
[Command/result]

#### Scope Check
[Files changed and whether they match the approved plan]

#### Completion
[Complete | Blocked | Escalated]

## 20. Completion Criteria
A cycle is complete when:
- [ ] Current Acceptance Criterion(s) are fully implemented.
- [ ] Required Business Rules are covered.
- [ ] Tests pass.
- [ ] Refactoring is complete or explicitly unnecessary.
- [ ] Relevant regression tests pass.
- [ ] No unresolved failure remains.
- [ ] No unapproved scope expansion occurred.
- [ ] Evidence is recorded.

Only then may the next cycle begin.

**Final feature completion requires:**
- [ ] All Acceptance Criteria completed.
- [ ] All required Business Rules covered.
- [ ] Targeted tests pass.
- [ ] Related regression tests pass.
- [ ] Complete test suite passes.
- [ ] Specification Verification can be performed.
- [ ] No unresolved ambiguity or scope deviation remains.

## 21. Human Approval Boundary
* Do not stage, commit, or push automatically.
* Present cycle evidence and final test results.
* Human approval remains required before Git commit/push.

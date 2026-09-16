---
name: tdd-quality-gate
description: Reusable quality gate that determines whether TDD for an approved feature is complete and whether the feature is ready to proceed to Code Review and Specification Verification.
---

# TDD Quality Gate

## 1. Purpose
Create a reusable quality gate that determines whether TDD for an approved feature is complete and whether the feature is ready to proceed to Code Review and Specification Verification.

The quality gate must verify more than "tests are green". Passing tests alone must NOT be sufficient for the gate to pass.

## 2. Preconditions
* Quality gate starts only from an Approved Feature Specification and Approved Implementation Plan.
* Do not modify code while performing the quality gate.
* Do not automatically fix findings.
* Do not invent business rules.
* Do not change requirements.
* Do not redesign architecture.
* Do not stage, commit, or push.
* If any critical requirement is missing or ambiguous, the gate must FAIL or BLOCK rather than silently accepting it.

## 3. Required Inputs
* Approved Feature Specification
* Approved Implementation Plan
* Business Rules
* Acceptance Criteria
* Test Results and TDD Cycle Records

## 4. Specification Completeness
Verify the Approved Feature Specification and Approved Implementation Plan exist. Ensure there is no unresolved ambiguity.

## 5. Business Rule Coverage
Verify every applicable Business Rule has evidence of implementation and testing.

## 6. Acceptance Criteria Coverage
Verify every Acceptance Criterion is implemented and tested.

## 7. Test Coverage
Verify targeted tests pass. Verify related regression tests pass. Verify the complete test suite passes with 0 failures. Verify no tests were weakened, deleted, skipped, or suppressed.

## 8. TDD Cycle Completion
Verify every TDD cycle is complete.

## 9. RED/GREEN/REFACTOR Evidence
Verify RED → GREEN → REFACTOR evidence exists for each cycle. Verify exact commands and results are recorded.

## 10. Authorization / Security Gate
Verify authorization and ownership boundaries where applicable. Verify cross-user isolation where applicable.

## 11. Financial Correctness Gate
Verify financial formulas, precision, rounding, zero states, and boundaries where applicable.

## 12. Database Gate
Verify database migrations and schema changes are safe.

## 13. API Gate
Verify API behavior follows repository conventions.

## 14. Edge Case Gate
Verify edge cases from the approved specification are covered.

## 15. Regression Gate
Verify no regressions have been introduced into the existing application.

## 16. Scope Gate
Verify no unapproved scope expansion occurred.

## 17. Documentation Gate
Verify documentation impact has been addressed.

## 18. Failure / Ambiguity Gate
Verify no unresolved failure remains. Verify no unresolved ambiguity remains.

## 19. Verification Evidence
Create a checklist for verification evidence:

### Specification
- [ ] Approved Feature Specification exists
- [ ] Approved Implementation Plan exists
- [ ] All Acceptance Criteria identified
- [ ] All relevant Business Rules identified
- [ ] No unresolved ambiguity

### TDD
- [ ] Every Acceptance Criterion has mapped tests
- [ ] Every TDD cycle completed
- [ ] RED evidence recorded
- [ ] GREEN evidence recorded
- [ ] REFACTOR evidence recorded
- [ ] No test weakened/deleted/skipped

### Testing
- [ ] Targeted tests pass
- [ ] Related regression tests pass
- [ ] Full test suite passes
- [ ] 0 failures
- [ ] Edge cases covered

### Security
- [ ] Authentication verified where applicable
- [ ] Authorization verified
- [ ] Ownership verified
- [ ] Cross-user isolation verified where applicable

### Financial
- [ ] Formula correctness verified
- [ ] Precision verified
- [ ] Rounding verified where applicable
- [ ] Zero-state verified
- [ ] Boundary values verified

### Technical
- [ ] Database changes verified
- [ ] Migration safety verified
- [ ] API behavior verified
- [ ] Architecture conventions preserved
- [ ] No unnecessary dependencies

### Scope
- [ ] Only approved files/components changed
- [ ] No unrelated refactoring
- [ ] No unapproved feature behavior
- [ ] Deviations documented and approved where applicable

### Documentation
- [ ] Required documentation updated
- [ ] API/domain/architecture documentation updated where required

### Evidence
- [ ] Exact commands recorded
- [ ] Results recorded
- [ ] Git status reviewed
- [ ] `git diff --check` clean

## 20. Quality Gate Result

Define the gate result states exactly as:
* **PASS**: All required checks pass, all acceptance criteria and business rules are covered, all relevant tests pass, no unresolved findings exist, and the feature is ready for Code Review and Specification Verification.
* **PASS WITH FINDINGS**: The feature meets all acceptance criteria and business rules, but has non-blocking findings that are explicitly documented and accepted for follow-up.
* **FAIL**: One or more required checks fail, a requirement is missing, a business rule is violated, tests fail, security is inadequate, financial correctness is wrong, or unapproved scope is present.
* **BLOCKED**: The gate cannot be completed because required inputs, environment, clarification, or verification evidence is unavailable.

### Gate Logic:
1. If required input is missing → **BLOCKED**.
2. If specification/business rule is ambiguous → **BLOCKED**.
3. If required Acceptance Criterion is missing → **FAIL**.
4. If required Business Rule is violated → **FAIL**.
5. If security boundary is violated → **FAIL**.
6. If financial calculation is materially incorrect → **FAIL**.
7. If required tests fail → **FAIL**.
8. If unapproved scope expansion exists → **FAIL**.
9. If only non-blocking findings remain → **PASS WITH FINDINGS**.
10. If all checks pass with no findings → **PASS**.

### TDD Quality Gate Report
Produce the following reusable Quality Gate Report:

## TDD Quality Gate Report

### Result
[PASS | PASS WITH FINDINGS | FAIL | BLOCKED]

### Feature
[Feature name]

### Specification
[Specification reference]

### Implementation Plan
[Implementation plan reference]

### Acceptance Criteria
[Coverage summary]

### Business Rules
[Coverage summary]

### TDD Cycles
[Cycle completion summary]

### Test Results
[Targeted / Regression / Full Suite]

### Security Gate
[Result]

### Financial Gate
[Result]

### Database Gate
[Result]

### API Gate
[Result]

### Edge Case Gate
[Result]

### Scope Gate
[Result]

### Documentation Gate
[Result]

### Findings
[Severity + evidence]

### Verification Evidence
[Exact commands and results]

### Final Recommendation
[Ready for Code Review and Specification Verification / Continue TDD / Blocked / Reject]

## 21. Human Approval Boundary
* The quality gate may determine readiness.
* It must NOT stage, commit, or push.
* Human approval remains required before Git commit/push.
* The quality gate must not silently accept unresolved critical findings.

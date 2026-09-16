---
name: specification-verification
description: Reusable agent procedure for verifying that an implemented feature matches its approved specification, business rules, acceptance criteria, implementation plan, and repository standards.
---

# Specification Verification

## 1. Specification Verification Purpose
The purpose of Specification Verification is to confirm that an implemented feature exactly matches its approved specification, business rules, acceptance criteria, implementation plan, repository architecture, security requirements, financial rules, and tests.

- **Specification** is the source of product requirements.
- **Business Rules** define domain behavior.
- **Acceptance Criteria** define observable outcomes.
- **Implementation Plan** defines the approved technical approach.
- **Repository architecture** defines existing technical conventions.
- **Tests** provide executable evidence.
- **Specification Verification** determines whether all of these remain aligned.

**Crucial:** The procedure must verify the actual implementation rather than merely checking whether tests pass. Passing tests alone must NOT be considered sufficient.

## 2. Required Inputs
To perform this verification, you must load:
1. The Approved Feature Specification
2. The relevant Business Rules
3. The relevant Acceptance Criteria
4. The approved Implementation Plan

## 3. Verification Preconditions
- Verification must begin from an Approved Feature Specification.
- Do not modify code while performing verification.
- Do not automatically fix findings during verification.
- Do not invent business rules.
- Do not redesign architecture during verification.
- Do not stage, commit, or push.
- If a requirement is ambiguous, mark it as unresolved rather than inventing behavior.

## 4. Specification Traceability
Build a traceability matrix linking:
`Specification → Business Rules → Acceptance Criteria → Implementation Plan → Source Code → Tests`
Ensure no requirements are orphaned and no undocumented features exist.

## 5. Business Rule Verification
- Verify every referenced Business Rule individually.
- Inspect the actual implementation to confirm the logic explicitly reflects the business rules.

## 6. Acceptance Criteria Verification
- Verify every Acceptance Criterion individually.
- Ensure the observable outcomes match the criteria exactly.

## 7. Implementation Plan Verification
- Verify that the implementation follows the approved Implementation Plan.
- Document any deviations. Justified deviations are allowed but must be explicitly called out in the report.
- Detect missing requirements or unapproved scope expansion.
- Detect implementation behavior that contradicts the specification.

## 8. Repository / Architecture Verification
- Inspect the repository state.
- Ensure the implementation adheres to existing architecture and technical conventions.

## 9. Authorization / Security Verification
- Verify ownership and cross-user isolation where applicable.
- Confirm that resources are properly scoped and protected.

## 10. Financial Calculation Verification
- Verify financial formulas, precision, rounding, zero states, and boundary conditions where applicable.

## 11. Database Verification
- Verify database changes and migration safety.
- Confirm proper types, constraints, and relational integrity.

## 12. API Verification
- Verify API behavior against existing API conventions.
- Ensure appropriate request/response structures and status codes.

## 13. Test Verification
- Check that tests provide evidence for the Acceptance Criteria.
- Ensure tests validate both the happy path and failure modes.

## 14. Edge Case Verification
- Verify that edge cases and boundaries (e.g., limits, empty states, nulls) are correctly handled and tested.

## 15. Regression Verification
- Verify that targeted tests and the complete test suite pass.

## 16. Documentation Verification
- Verify that necessary documentation updates (e.g., architecture docs, API docs) have been made.

## 17. Git / Change Scope Verification
- Verify that only intended files changed.
- Run `git status` and `git diff --check` to ensure no unrelated modifications or whitespace errors.

## 18. Verification Evidence
- Require exact verification commands and their results as evidence.
- Never claim verification passed unless the relevant checks were actually executed.

## 19. Findings and Severity
Classify any issues discovered during verification using the following severity levels:
- **BLOCKER:** Critical failure preventing feature acceptance (e.g., data loss, security flaw, complete feature failure).
- **HIGH:** Major deviation from specification or business rules.
- **MEDIUM:** Moderate deviation or missing non-critical functionality/tests.
- **LOW:** Minor issues (e.g., formatting, small edge cases).
- **INFO:** Observations or recommendations for future improvement.

## 20. Final Verification Result
Determine the final verification result based on the findings. Require human review of verification findings before changes are accepted.

- **PASS:** All criteria met; no findings above INFO.
- **PASS WITH FINDINGS:** Minor or justified deviations that do not block acceptance.
- **FAIL:** Significant deviations, missing requirements, or failing tests.
- **BLOCKED:** Verification cannot proceed due to missing inputs, ambiguous requirements, or environmental issues.

---

## Reusable Verification Workflow

Follow these steps sequentially:
1. Load approved specification
2. Load business rules
3. Load acceptance criteria
4. Load implementation plan
5. Inspect repository
6. Build traceability matrix
7. Verify implementation
8. Verify tests
9. Execute required verification commands
10. Review findings
11. Determine final result
12. Report evidence

---

## Recommended Final Report Format

When reporting the results, strictly use the following format:

```markdown
## Specification Verification Report

### Result
[PASS | PASS WITH FINDINGS | FAIL | BLOCKED]

### Scope
[Feature and specification reviewed]

### Traceability
[Specification → BR → AC → Implementation → Tests]

### Findings
[Severity, requirement/reference, evidence, impact]

### Verification Evidence
[Exact commands executed and results]

### Deviations
[Approved or justified deviations from implementation plan]

### Missing Requirements
[Any requirements not implemented]

### Security Verification
[Authorization/ownership results]

### Financial Verification
[Formula/precision results where applicable]

### Regression Verification
[Targeted and full-suite results]

### Documentation Verification
[Documentation status]

### Final Recommendation
[Accept / Accept with follow-up / Reject / Block pending clarification]
```

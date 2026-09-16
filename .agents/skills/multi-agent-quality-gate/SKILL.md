---
name: multi-agent-quality-gate
description: D6 - Multi-Agent Quality Gate for verifying the complete output of a multi-agent workflow before human approval.
---

# Multi-Agent Quality Gate

## 1. Purpose

This gate validates the complete output of a multi-agent workflow before human approval.

Passing tests alone are not sufficient.

The gate verifies workflow integrity, artifact ownership, requirements, quality, security, financial correctness, and evidence.

## 2. Gate Preconditions

The gate may run only when:

- required specification is approved
- business rules are confirmed where applicable
- implementation plan is approved
- required agent roles have completed their work
- required handoffs are accepted
- no required stage is BLOCKED or REJECTED

If a prerequisite is missing, status is BLOCKED.

## 3. Gate Inputs

The expected inputs are:

- Feature Specification
- Acceptance Criteria
- Business Rules
- Implementation Plan
- Agent Handoff Records
- Changed files
- Test results
- Code Review report
- Specification Verification report
- Financial Review report when applicable
- Git status/diff evidence
- Known issues and risks

## 4. Quality Gate Checks

### A. Requirement Compliance
- Every required AC is addressed.
- No out-of-scope behavior was introduced.
- No requirement was silently changed.

### B. Business Rule Compliance
- Relevant BRs are implemented.
- No conflicting interpretation exists.
- Financial formulas remain aligned with approved rules.

### C. Agent Ownership
- Each artifact was modified by the correct role.
- No agent silently modified another role's artifact.
- Cross-role changes were properly handed off.

### D. Handoff Integrity
- Required handoffs exist.
- Handoffs contain sufficient evidence.
- Receiving agents accepted the handoffs.
- No unresolved handoff exists.

### E. Parallel Work Safety
- Parallel work was classified according to D4.
- No unresolved file conflicts exist.
- No unresolved migration/schema conflicts exist.
- No unresolved business-rule conflicts exist.
- No work was silently overwritten.

### F. TDD / Testing
- Relevant AC/BR are covered by tests.
- Required RED → GREEN → REFACTOR cycles were completed.
- Targeted tests passed.
- Full suite passed where required.
- No tests were weakened, deleted, skipped, or altered merely to make implementation pass.
- Financial edge cases and precision cases are covered where applicable.

*Use the existing TDD Quality Gate for detailed TDD validation.*

### G. Code Review
- Code Review was completed when required.
- No BLOCKER/HIGH findings remain unresolved.
- Required remediation was completed.
- Review evidence is recorded.

### H. Specification Verification
- Specification Verification was completed.
- AC traceability passed.
- BR traceability passed.
- No unresolved specification mismatch remains.

### I. Financial Correctness
For financial features:
- Financial Review was completed when required.
- formulas were reviewed
- precision/rounding was reviewed
- boundary/zero cases were considered
- financial discrepancies are resolved

For non-financial features:
- mark this check Not Applicable.

### J. Security / Authorization
- ownership authorization is preserved
- authentication requirements are preserved
- authorization policies are preserved
- no security-critical unresolved findings exist

### K. Database
- migrations are correct
- migration conflicts are resolved
- schema changes match the approved plan
- destructive changes have required approval
- database-related tests pass

### L. API
When applicable:
- endpoint behavior matches specification
- validation is correct
- authorization is correct
- response/resource structure matches project conventions
- API tests pass

### M. Scope / Change Hygiene
- only intended files changed
- unexpected changes are reported
- no unrelated refactoring was introduced
- no unnecessary dependencies were added
- Git working tree was inspected
- `git diff --check` passed

## 5. Evidence Requirements

Every gate result must contain exact evidence.

Record:
- commands executed
- relevant outputs/results
- test counts/results
- review result
- verification result
- financial review result when applicable
- Git status/diff evidence

*Never claim PASS without actual evidence.*

## 6. Gate Status

- **PASS:** All required checks passed and no blocking findings remain.
- **PASS WITH FINDINGS:** The feature is substantially complete but has non-blocking findings that must be documented.
- **FAIL:** One or more required quality checks failed.
- **BLOCKED:** The gate cannot be completed because required input, approval, dependency, or evidence is missing.

## 7. Blocking Conditions

The gate MUST fail or remain blocked when:

- required AC is not implemented
- required business rule is missing
- financial formula is ambiguous
- financial calculation is incorrect
- authorization is missing or incorrect
- security-critical issue remains
- required tests fail
- tests were weakened/deleted to make implementation pass
- specification verification fails
- BLOCKER/HIGH code-review finding remains
- migration/schema conflict remains
- unresolved ownership conflict exists
- required handoff was not accepted
- destructive operation lacks approval
- evidence cannot be verified

## 8. Multi-Agent Traceability Matrix

| Check | Required Evidence | Result | Findings |
|------|-------------------|--------|----------|
| Requirements | Approved feature spec | | |
| Business Rules | Approved BR docs | | |
| Acceptance Criteria | Approved AC lists | | |
| Handoffs | Handoff records | | |
| Ownership | Git history / diffs | | |
| Parallel Safety | Parallel assessment record | | |
| TDD | Test execution logs | | |
| Code Review | Review report | | |
| Specification Verification | Traceability matrix/report | | |
| Financial Review | Financial verification report | | |
| Security | Policy/auth test results | | |
| Database | Migration logs, tests | | |
| API | API tests, docs | | |
| Change Scope | `git status` / `git diff` | | |

## 9. Gate Execution Process

1. Collect all required artifacts.
2. Confirm prerequisites.
3. Validate handoffs.
4. Validate ownership.
5. Validate parallel-work safety.
6. Review test/TDD gate result.
7. Review Code Review result.
8. Review Specification Verification result.
9. Review Financial Review when applicable.
10. Check security, database, API, and scope.
11. Verify exact evidence.
12. Determine PASS / PASS WITH FINDINGS / FAIL / BLOCKED.
13. Produce final gate report.
14. If PASS, hand off to Human Approval.
15. If not PASS, return work to the responsible role.

*Do not modify implementation during gate execution.*

## 10. Findings and Remediation

- findings must have severity
- every blocking finding must have an owner
- remediation must return through the appropriate agent
- after remediation, affected gates must be rerun
- do not mark a finding resolved without verification evidence

Severity levels:
- **BLOCKER**
- **HIGH**
- **MEDIUM**
- **LOW**
- **INFO**

## 11. Financial Feature Gate

For financial features, require all applicable checks:

```text
Approved Business Rules
        ↓
Approved Implementation Plan
        ↓
TDD Quality Gate
        ↓
Implementation
        ↓
Financial Review
        ↓
Code Review
        ↓
Specification Verification
        ↓
Multi-Agent Quality Gate
        ↓
Human Approval
```

## 12. Relationship With Other Skills

- **TDD Quality Gate:** Ensures tests and code coverage are robust.
- **Code Review:** Checks code quality, architecture, and correctness.
- **Specification Verification:** Ensures ACs and BRs are met.
- **Agent Handoff / Ownership / Parallel Safety (D2/D3/D4):** Provide the workflow integrity checks this gate validates.
- **Multi-Agent Quality Gate (D6):** The final coordinator gate that consumes the outputs of the above skills.

## 13. Definition of Done

D6 is complete when:
- gate preconditions are defined
- gate inputs are defined
- requirement, quality, ownership, safety, and financial checks are explicitly stated
- evidence requirements are defined
- allowed statuses (PASS, FAIL, etc.) are defined
- blocking conditions are explicit
- traceability matrix is created
- gate execution process is defined
- findings and remediation are defined
- financial feature gate is defined
- no existing project behavior is changed

---
name: failure-analysis
description: Reusable procedure for analyzing test failures during TDD and verification without blindly changing tests, requirements, or production code.
---

# Failure Analysis Protocol

## 1. Purpose
Create a reusable procedure for analyzing test failures during TDD and verification without blindly changing tests, requirements, or production code. The skill must establish a disciplined process:

Test Failure → Capture Evidence → Classify Failure → Identify Root Cause → Determine Permitted Action → Re-run Relevant Checks → Record Result

## 2. Preconditions
* Never assume every test failure is a production-code defect.
* Do not modify production code to make an incorrectly written test pass.
* Do not modify or weaken a test merely to make it pass.
* Do not change Acceptance Criteria, Business Rules, or Specification to resolve a failing test.

## 3. Failure Evidence
Capture the exact test command and relevant failure output. This evidence is necessary before any analysis begins.

## 4. Failure Classification
Every failure must be assigned to one of the following categories:

## 5. Expected RED Failure
The new test fails because the required feature behavior does not yet exist.
* **Action:** Proceed to GREEN only after confirming the failure is caused by the missing behavior. For expected RED, verify the test fails specifically because the required behavior is missing.

## 6. Test Defect
The test itself is incorrect, such as invalid setup, wrong assertion, incorrect endpoint, incorrect expected result, or syntax problem.
* **Action:** Correct the test only if the correction is supported by the approved specification and acceptance criteria.

## 7. Infrastructure Failure
The test cannot execute because of database, Docker, framework bootstrapping, filesystem, configuration, or test-runner infrastructure problems.
* **Action:** Fix or diagnose infrastructure separately from application logic. Infrastructure failures must be separated from application failures.

## 8. Implementation Defect
The test correctly expresses approved behavior but production code produces the wrong result.
* **Action:** Fix production code using the smallest change consistent with the approved implementation plan.

## 9. Regression Failure
Previously passing behavior is broken by the current change.
* **Action:** Investigate whether the new implementation caused the regression. Fix the new implementation if appropriate. Do not weaken the existing regression test.

## 10. Specification / Business Rule Conflict
The test, implementation, and approved requirement disagree.
* **Action:** STOP. Do not choose a business interpretation. Request human clarification. If the implementation contradicts an approved business rule, stop and report the conflict. If an existing test contradicts the approved specification, stop and report the conflict rather than silently rewriting it.

## 11. Environment / Dependency Failure
The failure is caused by external dependency, unavailable service, incompatible version, environment configuration, or similar issue.
* **Action:** Separate environmental evidence from application correctness and escalate when necessary. Environment/dependency failures must not be misclassified as application defects.

## 12. Root Cause Analysis
Determine the cause of the failure using this decision process:
1. Did the test execute?
2. Is the test itself valid?
3. Is the failure expected for the current TDD phase?
4. Does the failure correspond to missing required behavior?
5. Is infrastructure healthy?
6. Is the environment correct?
7. Does production code violate the Acceptance Criterion?
8. Does production code violate a Business Rule?
9. Does the change cause a regression?
10. Is the required behavior ambiguous?

If the root cause is unclear, stop and escalate rather than guessing.

## 13. Permitted Actions
* Fix infrastructure/environment configuration.
* Fix production code to match specification.
* Fix tests to match specification.
* Re-run tests.

## 14. Forbidden Actions
* Changing requirements to fit implementation.
* Weakening assertions.
* Deleting tests.
* Skipping tests.
* Suppressing errors.
* Making unrelated refactors.
* Introducing speculative architecture.
* Silently changing financial expected values.
* Silently changing authorization expectations.
* Claiming a failure is resolved without re-running the relevant check.
* Never delete tests to resolve failures.
* Never skip failing tests without explicit human approval.
* Never suppress errors or reduce assertions to hide failures.
* Do not introduce unrelated changes while fixing a failure.
* Do not redesign architecture to solve a localized test failure.

## 15. Re-test Procedure
* Re-run only the smallest relevant check first after a correction.
* Re-run the complete relevant test suite after the targeted test passes.
* Preserve the RED → GREEN → REFACTOR discipline.

## 16. Escalation Rules
When escalating, use these severity guidelines:
* **BLOCKER** — security breach, data corruption/loss, materially incorrect financial result, or complete inability to verify.
* **HIGH** — major business-rule violation, unauthorized access, significant regression.
* **MEDIUM** — meaningful functional defect without critical security/financial impact.
* **LOW** — minor defect or non-critical test/documentation issue.
* **INFO** — observation only.

## 17. Financial Failure Handling
* Verify exact expected financial values against approved formulas.
* Check decimal precision.
* Check rounding rules.
* Check zero states.
* Check negative/positive boundaries where applicable.
* Never "fix" a financial test by changing expected values without approved business-rule evidence.
* Do not introduce floating-point approximations merely to make tests pass.
* If expected financial behavior is ambiguous, STOP.

## 18. Authorization / Security Failure Handling
* Verify authenticated identity.
* Verify ownership relationships.
* Verify cross-user isolation.
* Verify correct HTTP status.
* Never weaken authorization assertions to make a test pass.
* Treat unexpected access to another user's financial data as a HIGH or BLOCKER issue depending on impact.

## 19. Evidence Requirements
Generate and record the following reusable failure analysis report for the failure:

### Failure Analysis Report

#### Test
[Exact command]

#### Failure
[Observed failure]

#### Classification
[Expected RED | Test Defect | Infrastructure Failure | Implementation Defect | Regression | Specification Conflict | Environment/Dependency]

#### Evidence
[Relevant output and observations]

#### Root Cause
[Determined cause]

#### Required Action
[Next permitted action]

#### Verification
[Command and result after action]

#### Escalation
[Human decision required? Yes/No]

#### Final Status
[Resolved | Escalated | Blocked]

## 20. Completion Criteria
The failure analysis process is complete when:
- [ ] Failure captured with exact evidence.
- [ ] Failure correctly classified.
- [ ] Root cause identified or explicitly marked unknown.
- [ ] No requirements invented or changed.
- [ ] No tests weakened or deleted.
- [ ] Appropriate action taken.
- [ ] Relevant targeted test re-run.
- [ ] Relevant regression/full suite re-run when applicable.
- [ ] Evidence recorded.
- [ ] Human escalation performed when required.
